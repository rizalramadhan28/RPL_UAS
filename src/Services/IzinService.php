<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class IzinService
{
    public function __construct(
        private UploadService $uploads = new UploadService(),
        private PengaturanService $pengaturan = new PengaturanService(),
    ) {}

    public function ajukan(int $pegawaiId, array $input, ?array $lampiran): array
    {
        $errors = [];
        $jenis = $input['jenis'] ?? '';
        if (!in_array($jenis, ['Izin', 'Sakit'], true)) {
            $errors['jenis'] = 'Jenis tidak valid';
        }
        $mulai = $input['tanggal_mulai'] ?? '';
        $selesai = $input['tanggal_selesai'] ?? '';
        if (!$this->isValidDate((string)$mulai)) $errors['tanggal_mulai'] = 'Tanggal mulai tidak valid';
        if (!$this->isValidDate((string)$selesai)) $errors['tanggal_selesai'] = 'Tanggal selesai tidak valid';
        $keterangan = trim((string)($input['keterangan'] ?? ''));
        $klen = mb_strlen($keterangan);
        if ($klen < 10 || $klen > 500) $errors['keterangan'] = 'Keterangan harus 10-500 karakter';

        if ($errors) return ['ok' => false, 'errors' => $errors];

        if ($mulai > $selesai) {
            return ['ok' => false, 'errors' => ['tanggal_mulai' => 'Tanggal mulai harus sebelum atau sama dengan tanggal selesai']];
        }

        // cek tumpang tindih
        $stmt = Db::pdo()->prepare(
            "SELECT id FROM izin
             WHERE pegawai_id = ?
               AND status IN ('Menunggu','Disetujui')
               AND NOT (tanggal_selesai < ? OR tanggal_mulai > ?)
             LIMIT 1"
        );
        $stmt->execute([$pegawaiId, $mulai, $selesai]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'errors' => ['_overlap' => 'Rentang tanggal beririsan dengan pengajuan lain']];
        }

        $lampiranPath = null;
        if ($lampiran) {
            $r = $this->uploads->save($lampiran, UploadService::TYPE_LAMPIRAN, $pegawaiId);
            if (!$r['ok']) return ['ok' => false, 'errors' => ['lampiran' => $r['error']]];
            $lampiranPath = $r['path'];
        }

        $nomor = 'IZN' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        $stmt = Db::pdo()->prepare(
            "INSERT INTO izin (pegawai_id, jenis, tanggal_mulai, tanggal_selesai, keterangan, status, lampiran_path, nomor_referensi, created_at)
             VALUES (?, ?, ?, ?, ?, 'Menunggu', ?, ?, ?)"
        );
        $stmt->execute([
            $pegawaiId, $jenis, $mulai, $selesai, $keterangan, $lampiranPath, $nomor,
            Time::now()->format('Y-m-d H:i:s'),
        ]);
        $id = (int) Db::pdo()->lastInsertId();

        return ['ok' => true, 'id' => $id, 'nomor_referensi' => $nomor];
    }

    public function listMenunggu(int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $count = (int) Db::pdo()->query(
            "SELECT COUNT(*) FROM izin WHERE status = 'Menunggu'"
        )->fetchColumn();

        $stmt = Db::pdo()->prepare(
            "SELECT i.*, u.nama AS pegawai_nama, u.jabatan
             FROM izin i JOIN users u ON u.id = i.pegawai_id
             WHERE i.status = 'Menunggu'
             ORDER BY i.created_at ASC, i.id ASC
             LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(),
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) ceil($count / $perPage),
        ];
    }

    public function setujui(int $izinId, int $adminId): array
    {
        return Db::tx(function () use ($izinId, $adminId) {
            $pdo = Db::pdo();
            $stmt = $pdo->prepare("SELECT * FROM izin WHERE id = ? FOR UPDATE");
            $stmt->execute([$izinId]);
            $izin = $stmt->fetch();
            if (!$izin) return ['ok' => false, 'error' => 'Pengajuan tidak ditemukan'];
            if ($izin['status'] !== 'Menunggu') return ['ok' => false, 'error' => 'Pengajuan sudah diputuskan'];

            $now = Time::now()->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare(
                "UPDATE izin SET status = 'Disetujui', decided_by = ?, decided_at = ? WHERE id = ?"
            );
            $stmt->execute([$adminId, $now, $izinId]);

            // Propagasi ke absensi tiap hari kerja
            $start = new \DateTimeImmutable($izin['tanggal_mulai'], Time::tz());
            $end = new \DateTimeImmutable($izin['tanggal_selesai'], Time::tz());
            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1 day'));

            $insert = $pdo->prepare(
                "INSERT INTO absensi (pegawai_id, tanggal, status, keterangan, sumber)
                 VALUES (?, ?, ?, ?, 'manual')
                 ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan), updated_at = CURRENT_TIMESTAMP"
            );
            foreach ($period as $d) {
                if ($this->pengaturan->isHariKerja($d)) {
                    $insert->execute([
                        (int)$izin['pegawai_id'], $d->format('Y-m-d'),
                        $izin['jenis'], 'Pengajuan ' . $izin['jenis'] . ' #' . $izin['nomor_referensi'],
                    ]);
                }
            }

            (new AuditLogService())->record(
                action: 'izin_approve',
                targetType: 'izin',
                targetId: $izinId,
                before: ['status' => 'Menunggu'],
                after: ['status' => 'Disetujui', 'decided_by' => $adminId]
            );

            return ['ok' => true];
        });
    }

    public function tolak(int $izinId, int $adminId, string $alasan): array
    {
        $alasan = trim($alasan);
        $len = mb_strlen($alasan);
        if ($len < 3 || $len > 500) {
            return ['ok' => false, 'error' => 'Alasan penolakan harus 3-500 karakter'];
        }
        return Db::tx(function () use ($izinId, $adminId, $alasan) {
            $pdo = Db::pdo();
            $stmt = $pdo->prepare("SELECT * FROM izin WHERE id = ? FOR UPDATE");
            $stmt->execute([$izinId]);
            $izin = $stmt->fetch();
            if (!$izin) return ['ok' => false, 'error' => 'Pengajuan tidak ditemukan'];
            if ($izin['status'] !== 'Menunggu') return ['ok' => false, 'error' => 'Pengajuan sudah diputuskan'];

            $now = Time::now()->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare(
                "UPDATE izin SET status = 'Ditolak', decided_by = ?, decided_at = ?, alasan_penolakan = ? WHERE id = ?"
            );
            $stmt->execute([$adminId, $now, $alasan, $izinId]);

            (new AuditLogService())->record(
                action: 'izin_reject',
                targetType: 'izin',
                targetId: $izinId,
                before: ['status' => 'Menunggu'],
                after: ['status' => 'Ditolak', 'alasan' => $alasan]
            );
            return ['ok' => true];
        });
    }

    public function listByPegawai(int $pegawaiId, ?int $bulan = null, ?int $tahun = null): array
    {
        $sql = "SELECT * FROM izin WHERE pegawai_id = ?";
        $params = [$pegawaiId];
        if ($bulan && $tahun) {
            $sql .= " AND ((YEAR(tanggal_mulai) = ? AND MONTH(tanggal_mulai) = ?)
                          OR (YEAR(tanggal_selesai) = ? AND MONTH(tanggal_selesai) = ?))";
            array_push($params, $tahun, $bulan, $tahun, $bulan);
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function isValidDate(string $s): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) return false;
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
