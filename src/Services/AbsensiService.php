<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class AbsensiService
{
    public function __construct(
        private PengaturanService $pengaturan = new PengaturanService(),
        private GeoService $geo = new GeoService(),
        private UploadService $uploads = new UploadService(),
    ) {}

    public function getStatusHariIni(int $userId, \DateTimeImmutable $now): array
    {
        $cfg = $this->pengaturan->getAktif();
        $tanggal = $now->format('Y-m-d');
        $stmt = Db::pdo()->prepare("SELECT * FROM absensi WHERE pegawai_id = ? AND tanggal = ? LIMIT 1");
        $stmt->execute([$userId, $tanggal]);
        $absen = $stmt->fetch() ?: null;

        $isHK = $this->pengaturan->isHariKerja($now);
        $hhmm = $now->format('H:i:s');
        $window = null;
        if ($hhmm >= $cfg['jam_masuk_mulai'] && $hhmm < $cfg['jam_masuk_selesai']) {
            $window = 'normal';
        } elseif ($hhmm >= $cfg['jam_masuk_selesai'] && $hhmm < $cfg['jam_terlambat_selesai']) {
            $window = 'terlambat';
        } elseif ($hhmm >= $cfg['jam_pulang_mulai']) {
            $window = 'pulang';
        }

        $hasMasuk = $absen && !empty($absen['ts_masuk']);
        $hasPulang = $absen && !empty($absen['ts_pulang']);

        $canMasuk = $isHK && !$hasMasuk && in_array($window, ['normal','terlambat'], true);
        $canPulang = $isHK && $hasMasuk && !$hasPulang && ($hhmm >= $cfg['jam_pulang_mulai']);

        return [
            'pengaturan' => $cfg,
            'absen' => $absen,
            'is_hari_kerja' => $isHK,
            'window' => $window,
            'has_masuk' => $hasMasuk,
            'has_pulang' => $hasPulang,
            'can_masuk' => $canMasuk,
            'can_pulang' => $canPulang,
            'now_hms' => $hhmm,
        ];
    }

    /**
     * @return array{ok:bool,error?:string,status?:string}
     */
    public function absenMasuk(
        int $userId,
        ?array $fileFoto,
        ?string $fotoDataUrl,
        float $lat,
        float $lon,
        string $alasan = '',
        ?\DateTimeImmutable $now = null
    ): array {
        $now ??= Time::now();
        $cfg = $this->pengaturan->getAktif();

        if (!$this->pengaturan->isHariKerja($now)) {
            return ['ok' => false, 'error' => 'Hari ini bukan hari kerja'];
        }

        $hhmm = $now->format('H:i:s');
        if ($hhmm < $cfg['jam_masuk_mulai']) {
            return ['ok' => false, 'error' => 'Belum memasuki jam absen masuk'];
        }
        if ($hhmm >= $cfg['jam_terlambat_selesai']) {
            return ['ok' => false, 'error' => 'Jendela absen masuk telah berakhir'];
        }

        $isLate = $hhmm >= $cfg['jam_masuk_selesai'];
        $status = $isLate ? 'Terlambat' : 'Hadir';

        if ($isLate) {
            $alasanTrim = trim($alasan);
            $len = mb_strlen($alasanTrim);
            if ($len < 10 || $len > 500) {
                return ['ok' => false, 'error' => 'Alasan keterlambatan harus 10-500 karakter'];
            }
        }

        // Cek duplikat
        $tanggal = $now->format('Y-m-d');
        $stmt = Db::pdo()->prepare(
            "SELECT id, ts_masuk, status FROM absensi WHERE pegawai_id = ? AND tanggal = ? LIMIT 1"
        );
        $stmt->execute([$userId, $tanggal]);
        $existing = $stmt->fetch();
        if ($existing && !empty($existing['ts_masuk'])) {
            return ['ok' => false, 'error' => 'Anda sudah absen masuk hari ini'];
        }

        // Validasi GPS
        $jarak = $this->geo->haversine($lat, $lon, (float)$cfg['latitude'], (float)$cfg['longitude']);
        if ($jarak > (float)$cfg['radius_meter']) {
            return ['ok' => false, 'error' => 'Lokasi di luar area Kantor Desa (jarak ' . round($jarak) . ' m)'];
        }

        // Simpan swafoto
        $fotoPath = null;
        if ($fileFoto) {
            $r = $this->uploads->save($fileFoto, UploadService::TYPE_SWAFOTO, $userId);
            if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
            $fotoPath = $r['path'];
        } elseif ($fotoDataUrl) {
            $r = $this->uploads->saveDataUrl($fotoDataUrl, $userId);
            if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
            $fotoPath = $r['path'];
        } else {
            return ['ok' => false, 'error' => 'Swafoto wajib disertakan'];
        }

        $tsMasuk = $now->format('Y-m-d H:i:s');

        if ($existing && in_array($existing['status'], ['Izin', 'Sakit', 'Alpha'], true)) {
            // Override (mis. status sebelumnya Alpha karena dijalankan ulang)
            $stmt = Db::pdo()->prepare(
                "UPDATE absensi SET status = ?, ts_masuk = ?, lat_masuk = ?, lon_masuk = ?, swafoto_masuk = ?, alasan_terlambat = ?, sumber = 'manual', updated_at = ? WHERE id = ?"
            );
            $stmt->execute([
                $status, $tsMasuk, $lat, $lon, $fotoPath,
                $isLate ? trim($alasan) : null,
                $tsMasuk, $existing['id']
            ]);
        } else {
            $stmt = Db::pdo()->prepare(
                "INSERT INTO absensi (pegawai_id, tanggal, status, ts_masuk, lat_masuk, lon_masuk, swafoto_masuk, alasan_terlambat, sumber)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'manual')"
            );
            $stmt->execute([
                $userId, $tanggal, $status, $tsMasuk, $lat, $lon, $fotoPath,
                $isLate ? trim($alasan) : null,
            ]);
        }
        return ['ok' => true, 'status' => $status];
    }

    public function absenPulang(
        int $userId,
        ?array $fileFoto,
        ?string $fotoDataUrl,
        float $lat,
        float $lon,
        ?\DateTimeImmutable $now = null
    ): array {
        $now ??= Time::now();
        $cfg = $this->pengaturan->getAktif();

        if (!$this->pengaturan->isHariKerja($now)) {
            return ['ok' => false, 'error' => 'Hari ini bukan hari kerja'];
        }
        $hhmm = $now->format('H:i:s');
        if ($hhmm < $cfg['jam_pulang_mulai']) {
            return ['ok' => false, 'error' => 'Belum memasuki jam pulang'];
        }
        $tanggal = $now->format('Y-m-d');
        $stmt = Db::pdo()->prepare(
            "SELECT id, ts_masuk, ts_pulang FROM absensi WHERE pegawai_id = ? AND tanggal = ? LIMIT 1"
        );
        $stmt->execute([$userId, $tanggal]);
        $absen = $stmt->fetch();
        if (!$absen || empty($absen['ts_masuk'])) {
            return ['ok' => false, 'error' => 'Belum ada absen masuk hari ini'];
        }
        if (!empty($absen['ts_pulang'])) {
            return ['ok' => false, 'error' => 'Anda sudah absen pulang hari ini'];
        }

        $jarak = $this->geo->haversine($lat, $lon, (float)$cfg['latitude'], (float)$cfg['longitude']);
        if ($jarak > (float)$cfg['radius_meter']) {
            return ['ok' => false, 'error' => 'Lokasi di luar area Kantor Desa (jarak ' . round($jarak) . ' m)'];
        }

        $fotoPath = null;
        if ($fileFoto) {
            $r = $this->uploads->save($fileFoto, UploadService::TYPE_SWAFOTO, $userId);
            if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
            $fotoPath = $r['path'];
        } elseif ($fotoDataUrl) {
            $r = $this->uploads->saveDataUrl($fotoDataUrl, $userId);
            if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
            $fotoPath = $r['path'];
        } else {
            return ['ok' => false, 'error' => 'Swafoto wajib disertakan'];
        }

        $stmt = Db::pdo()->prepare(
            "UPDATE absensi SET ts_pulang = ?, lat_pulang = ?, lon_pulang = ?, swafoto_pulang = ?, updated_at = ? WHERE id = ?"
        );
        $stmt->execute([
            $now->format('Y-m-d H:i:s'),
            $lat, $lon, $fotoPath,
            $now->format('Y-m-d H:i:s'),
            $absen['id'],
        ]);

        return ['ok' => true];
    }
}
