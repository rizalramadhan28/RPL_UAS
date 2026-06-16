<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class KegiatanService
{
    public function __construct(
        private PengaturanService $pengaturan = new PengaturanService(),
    ) {}

    public function tambah(int $pegawaiId, array $input, ?\DateTimeImmutable $now = null): array
    {
        $now ??= Time::now();
        $errors = [];
        $nama = trim((string)($input['nama'] ?? ''));
        $len = mb_strlen($nama);
        if ($len < 3 || $len > 200) $errors['nama'] = 'Nama kegiatan harus 3-200 karakter';

        $tp = '/^([01]\d|2[0-3]):[0-5]\d$/';
        $jm = (string)($input['jam_mulai'] ?? '');
        $js = (string)($input['jam_selesai'] ?? '');
        if (!preg_match($tp, $jm)) $errors['jam_mulai'] = 'Format jam tidak valid (HH:MM 00:00-23:59)';
        if (!preg_match($tp, $js)) $errors['jam_selesai'] = 'Format jam tidak valid (HH:MM 00:00-23:59)';
        if (!$errors && $jm >= $js) $errors['jam_selesai'] = 'Jam mulai harus sebelum jam selesai';

        if (!$this->pengaturan->isHariKerja($now)) {
            $errors['_hari'] = 'Kegiatan hanya dapat dicatat pada hari kerja';
        }

        if ($errors) return ['ok' => false, 'errors' => $errors];

        $stmt = Db::pdo()->prepare(
            "INSERT INTO kegiatan (pegawai_id, tanggal, nama, jam_mulai, jam_selesai, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $pegawaiId,
            $now->format('Y-m-d'),
            $nama,
            $jm . ':00',
            $js . ':00',
            $now->format('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'id' => (int) Db::pdo()->lastInsertId()];
    }

    public function listHari(int $pegawaiId, \DateTimeImmutable $tgl): array
    {
        $stmt = Db::pdo()->prepare(
            "SELECT * FROM kegiatan WHERE pegawai_id = ? AND tanggal = ? ORDER BY jam_mulai ASC"
        );
        $stmt->execute([$pegawaiId, $tgl->format('Y-m-d')]);
        return $stmt->fetchAll();
    }

    /**
     * Daftar kegiatan SELURUH pegawai pada satu tanggal (untuk dashboard admin & kepala desa).
     * Diurutkan: nama pegawai (alfabet) lalu jam_mulai menaik.
     */
    public function listHariSemua(\DateTimeImmutable $tgl): array
    {
        $stmt = Db::pdo()->prepare(
            "SELECT k.id, k.pegawai_id, k.nama, k.jam_mulai, k.jam_selesai, k.tanggal,
                    u.nama AS pegawai_nama, u.jabatan
             FROM kegiatan k
             JOIN users u ON u.id = k.pegawai_id
             WHERE k.tanggal = ?
             ORDER BY u.nama ASC, k.jam_mulai ASC"
        );
        $stmt->execute([$tgl->format('Y-m-d')]);
        return $stmt->fetchAll();
    }
}
