<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class PengaturanService
{
    public function getAktif(): array
    {
        $row = Db::pdo()->query("SELECT * FROM pengaturan WHERE id = 1 LIMIT 1")->fetch();
        if (!$row) {
            // default fallback
            return [
                'id' => 1,
                'jam_masuk_mulai' => '08:00:00',
                'jam_masuk_selesai' => '10:00:00',
                'jam_terlambat_selesai' => '16:00:00',
                'jam_pulang_mulai' => '14:00:00',
                'latitude' => -7.5360000,
                'longitude' => 110.6680000,
                'radius_meter' => 100,
                'hari_kerja_mask' => 31,
                'nama_desa' => 'Desa Wadas',
            ];
        }
        $row['radius_meter'] = (int)$row['radius_meter'];
        $row['hari_kerja_mask'] = (int)$row['hari_kerja_mask'];
        $row['latitude'] = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];
        return $row;
    }

    public function isHariKerja(\DateTimeInterface $date): bool
    {
        $cfg = $this->getAktif();
        // ISO weekday: 1=Senin..7=Minggu -> bit (n-1)
        $dow = (int)$date->format('N');
        $bit = 1 << ($dow - 1);
        if (($cfg['hari_kerja_mask'] & $bit) === 0) return false;

        // Cek hari libur
        $stmt = Db::pdo()->prepare("SELECT 1 FROM holidays WHERE tanggal = ? LIMIT 1");
        $stmt->execute([$date->format('Y-m-d')]);
        if ($stmt->fetch()) return false;

        return true;
    }

    /**
     * Update khusus hari kerja mingguan (bitmask). Boleh dilakukan Admin maupun Kepala Desa.
     * @return array{ok:bool,errors?:array<string,string>}
     */
    public function simpanHariKerja(int $maskRaw, int $actorId): array
    {
        if ($maskRaw < 0 || $maskRaw > 127) {
            return ['ok' => false, 'errors' => ['hari_kerja_mask' => 'Pilihan hari kerja tidak valid']];
        }
        if ($maskRaw === 0) {
            return ['ok' => false, 'errors' => ['hari_kerja_mask' => 'Minimal pilih satu hari kerja']];
        }

        $before = $this->getAktif();
        Db::tx(function () use ($maskRaw, $actorId, $before) {
            // Pastikan baris pengaturan ada; jika belum, buat dari nilai aktif (fallback).
            $stmt = Db::pdo()->prepare(
                "INSERT INTO pengaturan
                 (id, jam_masuk_mulai, jam_masuk_selesai, jam_terlambat_selesai, jam_pulang_mulai,
                  latitude, longitude, radius_meter, hari_kerja_mask, nama_desa, updated_by, updated_at)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                  hari_kerja_mask = VALUES(hari_kerja_mask),
                  updated_by = VALUES(updated_by),
                  updated_at = VALUES(updated_at)"
            );
            $stmt->execute([
                substr((string)$before['jam_masuk_mulai'], 0, 5) . ':00',
                substr((string)$before['jam_masuk_selesai'], 0, 5) . ':00',
                substr((string)$before['jam_terlambat_selesai'], 0, 5) . ':00',
                substr((string)$before['jam_pulang_mulai'], 0, 5) . ':00',
                (float)$before['latitude'],
                (float)$before['longitude'],
                (int)$before['radius_meter'],
                $maskRaw,
                $before['nama_desa'] ?? 'Desa Wadas',
                $actorId,
                Time::now()->format('Y-m-d H:i:s'),
            ]);

            (new AuditLogService())->record(
                action: 'hari_kerja_update',
                targetType: 'pengaturan',
                targetId: 1,
                before: ['hari_kerja_mask' => (int)$before['hari_kerja_mask']],
                after: ['hari_kerja_mask' => $maskRaw]
            );
        });

        return ['ok' => true];
    }

    /** Daftar hari libur, opsional difilter tahun. */
    public function listHolidays(?int $tahun = null): array
    {
        if ($tahun) {
            $stmt = Db::pdo()->prepare("SELECT tanggal, nama FROM holidays WHERE YEAR(tanggal) = ? ORDER BY tanggal ASC");
            $stmt->execute([$tahun]);
            return $stmt->fetchAll();
        }
        return Db::pdo()->query("SELECT tanggal, nama FROM holidays ORDER BY tanggal ASC")->fetchAll();
    }

    /** @return array{ok:bool,error?:string} */
    public function tambahHoliday(string $tanggal, string $nama, int $actorId): array
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tanggal, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return ['ok' => false, 'error' => 'Tanggal tidak valid'];
        }
        $nama = trim($nama);
        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
            return ['ok' => false, 'error' => 'Nama hari libur harus 3-100 karakter'];
        }
        $stmt = Db::pdo()->prepare(
            "INSERT INTO holidays (tanggal, nama) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE nama = VALUES(nama)"
        );
        $stmt->execute([$tanggal, $nama]);
        (new AuditLogService())->record(
            action: 'holiday_add', targetType: 'holiday', targetId: null,
            after: ['tanggal' => $tanggal, 'nama' => $nama], actor: \App\Core\Session::user()
        );
        return ['ok' => true];
    }

    /** @return array{ok:bool,error?:string} */
    public function hapusHoliday(string $tanggal, int $actorId): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return ['ok' => false, 'error' => 'Tanggal tidak valid'];
        }
        $stmt = Db::pdo()->prepare("DELETE FROM holidays WHERE tanggal = ?");
        $stmt->execute([$tanggal]);
        (new AuditLogService())->record(
            action: 'holiday_delete', targetType: 'holiday', targetId: null,
            before: ['tanggal' => $tanggal], actor: \App\Core\Session::user()
        );
        return ['ok' => true];
    }

    /**
     * @return array{ok:bool,errors?:array<string,string>}
     */
    public function simpan(array $input, int $adminId): array
    {
        $errors = [];
        $required = ['jam_masuk_mulai','jam_masuk_selesai','jam_terlambat_selesai','jam_pulang_mulai','latitude','longitude','radius_meter'];
        foreach ($required as $f) {
            if (!isset($input[$f]) || $input[$f] === '') {
                $errors[$f] = 'Field wajib diisi';
            }
        }

        $timePattern = '/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
        foreach (['jam_masuk_mulai','jam_masuk_selesai','jam_terlambat_selesai','jam_pulang_mulai'] as $f) {
            if (!isset($errors[$f]) && !preg_match($timePattern, (string)$input[$f])) {
                $errors[$f] = 'Format waktu tidak valid (HH:MM)';
            }
        }
        if (!$errors) {
            $jm1 = $input['jam_masuk_mulai'];
            $jm2 = $input['jam_masuk_selesai'];
            $jt  = $input['jam_terlambat_selesai'];
            $jp  = $input['jam_pulang_mulai'];
            // Aturan operasional: jendela masuk harus naik (mulai < selesai < terlambat).
            // Jendela pulang independen tetapi tidak boleh lebih awal dari jam masuk mulai.
            if (!($jm1 < $jm2)) {
                $errors['jam_masuk_mulai'] = 'Jam masuk mulai harus sebelum jam masuk selesai';
            } elseif (!($jm2 < $jt)) {
                $errors['jam_masuk_selesai'] = 'Jam masuk selesai harus sebelum batas terlambat';
            } elseif ($jp <= $jm1) {
                $errors['jam_pulang_mulai'] = 'Jam pulang harus setelah jam masuk mulai';
            }
        }

        $lat = (float)$input['latitude'];
        $lon = (float)$input['longitude'];
        $rad = (int)$input['radius_meter'];

        if ($lat < -90 || $lat > 90) $errors['latitude'] = 'Latitude di luar rentang';
        if ($lon < -180 || $lon > 180) $errors['longitude'] = 'Longitude di luar rentang';
        if ($rad < 10 || $rad > 5000) $errors['radius_meter'] = 'Radius harus 10-5000 meter';

        $maskRaw = isset($input['hari_kerja_mask']) ? (int)$input['hari_kerja_mask'] : 31;
        if ($maskRaw < 0 || $maskRaw > 127) $errors['hari_kerja_mask'] = 'Mask hari kerja tidak valid';

        if ($errors) return ['ok' => false, 'errors' => $errors];

        $auditService = new AuditLogService();
        $before = $this->getAktif();

        Db::tx(function () use ($input, $adminId, $lat, $lon, $rad, $maskRaw, $auditService, $before) {
            $stmt = Db::pdo()->prepare(
                "INSERT INTO pengaturan
                 (id, jam_masuk_mulai, jam_masuk_selesai, jam_terlambat_selesai, jam_pulang_mulai,
                  latitude, longitude, radius_meter, hari_kerja_mask, nama_desa, updated_by, updated_at)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                  jam_masuk_mulai = VALUES(jam_masuk_mulai),
                  jam_masuk_selesai = VALUES(jam_masuk_selesai),
                  jam_terlambat_selesai = VALUES(jam_terlambat_selesai),
                  jam_pulang_mulai = VALUES(jam_pulang_mulai),
                  latitude = VALUES(latitude),
                  longitude = VALUES(longitude),
                  radius_meter = VALUES(radius_meter),
                  hari_kerja_mask = VALUES(hari_kerja_mask),
                  nama_desa = VALUES(nama_desa),
                  updated_by = VALUES(updated_by),
                  updated_at = VALUES(updated_at)"
            );
            $stmt->execute([
                substr((string)$input['jam_masuk_mulai'], 0, 5) . ':00',
                substr((string)$input['jam_masuk_selesai'], 0, 5) . ':00',
                substr((string)$input['jam_terlambat_selesai'], 0, 5) . ':00',
                substr((string)$input['jam_pulang_mulai'], 0, 5) . ':00',
                $lat,
                $lon,
                $rad,
                $maskRaw,
                isset($input['nama_desa']) ? trim((string)$input['nama_desa']) : 'Desa Wadas',
                $adminId,
                Time::now()->format('Y-m-d H:i:s'),
            ]);

            $auditService->record(
                action: 'pengaturan_update',
                targetType: 'pengaturan',
                targetId: 1,
                before: $before,
                after: [
                    'jam_masuk_mulai' => $input['jam_masuk_mulai'],
                    'jam_masuk_selesai' => $input['jam_masuk_selesai'],
                    'jam_terlambat_selesai' => $input['jam_terlambat_selesai'],
                    'jam_pulang_mulai' => $input['jam_pulang_mulai'],
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'radius_meter' => $rad,
                    'hari_kerja_mask' => $maskRaw,
                ]
            );
        });

        return ['ok' => true];
    }
}
