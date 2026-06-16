<?php
declare(strict_types=1);

/**
 * Comprehensive automated test untuk Sistem Absensi Desa Wadas.
 * Menguji:
 * - Auth (login, logout, throttle, validasi panjang)
 * - CSRF
 * - Manajemen Pegawai (NIP/username unik, format validasi)
 * - Pengaturan (urutan jam, lat/lon, radius, hari kerja)
 * - Izin (tanggal, panjang keterangan, tumpang tindih)
 * - Kegiatan (jam, nama)
 * - Absensi (GPS jarak, swafoto)
 * - Otorisasi (pegawai/admin/kepala matrix)
 * - Display board payload shape
 * - Audit log
 */

require __DIR__ . '/../src/autoload.php';

use App\Core\App;
use App\Core\Db;
use App\Services\AuthService;
use App\Services\GeoService;
use App\Services\IzinService;
use App\Services\KegiatanService;
use App\Services\PegawaiService;
use App\Services\PengaturanService;
use App\Services\RekapService;
use App\Services\AbsensiService;
use App\Services\DashboardService;
use App\Core\Csrf;

App::bootstrap();

$base = 'http://127.0.0.1:8088';
$cookieJar = __DIR__ . '/.test-comprehensive-cookies.txt';
@unlink($cookieJar);

$pass = 0; $fail = 0; $failed = [];

function check(string $name, bool $cond): void {
    global $pass, $fail, $failed;
    if ($cond) { $pass++; echo "  [OK]   $name\n"; }
    else       { $fail++; $failed[] = $name; echo "  [FAIL] $name\n"; }
}
function section(string $title): void {
    echo "\n=== $title ===\n";
}
function fresh_cookies($jar) { @unlink($jar); }

function curl_get($url, $jar, $followRedirect = true) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirect,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [$code, $body, $effective];
}
function curl_post($url, $jar, array $data, $followRedirect = true) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirect,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [$code, $body, $effective];
}

function get_csrf($body) {
    if (preg_match('/name="_csrf"\s+value="([0-9a-f]+)"/', $body, $m)) return $m[1];
    return null;
}

function login($base, $jar, $u, $p) {
    fresh_cookies($jar);
    [, $b] = curl_get($base . '/login', $jar);
    $csrf = get_csrf($b);
    return curl_post($base . '/login', $jar, ['username' => $u, 'password' => $p, '_csrf' => $csrf]);
}

// ============================================================
section('1. GeoService (unit)');
$g = new GeoService();
check('Haversine d(p,p) = 0', abs($g->haversine(-7.536, 110.668, -7.536, 110.668)) < 1e-6);
check('Haversine simetris', abs($g->haversine(0, 0, 1, 1) - $g->haversine(1, 1, 0, 0)) < 1e-6);
check('Haversine 1 derajat lat ~111km', $g->haversine(0, 0, 1, 0) > 110000 && $g->haversine(0, 0, 1, 0) < 112000);
check('Haversine 100m benar (radius default)',
    ($d = $g->haversine(-7.536, 110.668, -7.5360001, 110.6680001)) >= 0 && $d < 1.0);

// ============================================================
section('2. PengaturanService validasi');
$p = new PengaturanService();
$cfg = $p->getAktif();
check('getAktif returns array', is_array($cfg));
check('Default radius 100', $cfg['radius_meter'] == 100);

// invalid: lat di luar -90..90
$r = $p->simpan([
    'jam_masuk_mulai' => '08:00', 'jam_masuk_selesai' => '10:00',
    'jam_terlambat_selesai' => '16:00', 'jam_pulang_mulai' => '14:00',
    'latitude' => 100.0, 'longitude' => 110.668, 'radius_meter' => 100,
    'hari_kerja_mask' => 31, 'nama_desa' => 'Test',
], 1);
check('Tolak latitude > 90', !$r['ok'] && isset($r['errors']['latitude']));

// invalid: radius < 10
$r = $p->simpan([
    'jam_masuk_mulai' => '08:00', 'jam_masuk_selesai' => '10:00',
    'jam_terlambat_selesai' => '16:00', 'jam_pulang_mulai' => '14:00',
    'latitude' => -7.5, 'longitude' => 110.6, 'radius_meter' => 5,
    'hari_kerja_mask' => 31, 'nama_desa' => 'Test',
], 1);
check('Tolak radius < 10', !$r['ok'] && isset($r['errors']['radius_meter']));

// invalid: radius > 5000
$r = $p->simpan([
    'jam_masuk_mulai' => '08:00', 'jam_masuk_selesai' => '10:00',
    'jam_terlambat_selesai' => '16:00', 'jam_pulang_mulai' => '14:00',
    'latitude' => -7.5, 'longitude' => 110.6, 'radius_meter' => 6000,
    'hari_kerja_mask' => 31, 'nama_desa' => 'Test',
], 1);
check('Tolak radius > 5000', !$r['ok'] && isset($r['errors']['radius_meter']));

// invalid: format jam tidak HH:MM
$r = $p->simpan([
    'jam_masuk_mulai' => '99:99', 'jam_masuk_selesai' => '10:00',
    'jam_terlambat_selesai' => '16:00', 'jam_pulang_mulai' => '14:00',
    'latitude' => -7.5, 'longitude' => 110.6, 'radius_meter' => 100,
], 1);
check('Tolak format jam tidak valid', !$r['ok'] && isset($r['errors']['jam_masuk_mulai']));

// invalid: urutan jam masuk
$r = $p->simpan([
    'jam_masuk_mulai' => '10:00', 'jam_masuk_selesai' => '08:00',
    'jam_terlambat_selesai' => '16:00', 'jam_pulang_mulai' => '14:00',
    'latitude' => -7.5, 'longitude' => 110.6, 'radius_meter' => 100,
], 1);
check('Tolak masuk_mulai >= masuk_selesai', !$r['ok'] && isset($r['errors']['jam_masuk_mulai']));

// valid
$r = $p->simpan([
    'jam_masuk_mulai' => '08:00', 'jam_masuk_selesai' => '10:00',
    'jam_terlambat_selesai' => '16:00', 'jam_pulang_mulai' => '14:00',
    'latitude' => -7.536, 'longitude' => 110.668, 'radius_meter' => 100,
    'hari_kerja_mask' => 31, 'nama_desa' => 'Desa Wadas',
], 1);
check('Pengaturan valid tersimpan', $r['ok']);

// ============================================================
section('3. Hari kerja & holiday');
$mon = new DateTimeImmutable('2026-05-25');  // Senin
$sat = new DateTimeImmutable('2026-05-30');  // Sabtu
$sun = new DateTimeImmutable('2026-05-31');  // Minggu
check('Senin = hari kerja', $p->isHariKerja($mon));
check('Sabtu = bukan hari kerja (mask 31)', !$p->isHariKerja($sat));
check('Minggu = bukan hari kerja', !$p->isHariKerja($sun));

// tambah hari libur, cek
Db::pdo()->exec("DELETE FROM holidays WHERE tanggal = '2026-05-26'");
Db::pdo()->exec("INSERT INTO holidays (tanggal, nama) VALUES ('2026-05-26', 'Tes Libur')");
$tue = new DateTimeImmutable('2026-05-26');
check('Selasa libur nasional = bukan hari kerja', !$p->isHariKerja($tue));
Db::pdo()->exec("DELETE FROM holidays WHERE tanggal = '2026-05-26'");

// ============================================================
section('4. Kegiatan validasi');
$k = new KegiatanService();
// Buat user temp untuk testing
$tempUserId = null;
Db::pdo()->exec("DELETE FROM users WHERE username = 'kegtest'");
$pegawaiSvc = new PegawaiService();
$rPeg = $pegawaiSvc->tambah([
    'nip' => '111122223333444455', 'username' => 'kegtest',
    'password' => 'PassTest1234', 'nama' => 'Tester Kegiatan', 'jabatan' => 'Tester',
], 1);
check('Tambah pegawai valid (kegtest)', $rPeg['ok']);
$tempUserId = $rPeg['id'] ?? null;

if ($tempUserId) {
    // nama < 3
    $r = $k->tambah($tempUserId, ['nama' => 'ab', 'jam_mulai' => '08:00', 'jam_selesai' => '09:00']);
    check('Tolak nama kegiatan < 3 char', !$r['ok'] && isset($r['errors']['nama']));

    // nama > 200
    $longName = str_repeat('x', 201);
    $r = $k->tambah($tempUserId, ['nama' => $longName, 'jam_mulai' => '08:00', 'jam_selesai' => '09:00']);
    check('Tolak nama kegiatan > 200 char', !$r['ok'] && isset($r['errors']['nama']));

    // jam_mulai >= jam_selesai
    $r = $k->tambah($tempUserId, ['nama' => 'Rapat', 'jam_mulai' => '10:00', 'jam_selesai' => '09:00']);
    check('Tolak jam_mulai >= jam_selesai', !$r['ok'] && isset($r['errors']['jam_selesai']));

    // format jam tidak valid
    $r = $k->tambah($tempUserId, ['nama' => 'Rapat', 'jam_mulai' => '25:99', 'jam_selesai' => '26:00']);
    check('Tolak format jam invalid (25:99)', !$r['ok'] && isset($r['errors']['jam_mulai']));

    // panjang valid 3 batas bawah
    $r = $k->tambah($tempUserId, ['nama' => 'abc', 'jam_mulai' => '08:00', 'jam_selesai' => '09:00']);
    check('Terima nama 3 char tepat', $r['ok'] || $r['errors']['_hari'] ?? false); // boleh tolak karena bukan hari kerja
}

// ============================================================
section('5. Izin validasi');
$iz = new IzinService();
if ($tempUserId) {
    // keterangan < 10
    $r = $iz->ajukan($tempUserId, [
        'jenis' => 'Izin', 'tanggal_mulai' => '2026-06-01', 'tanggal_selesai' => '2026-06-02',
        'keterangan' => 'pendek',
    ], null);
    check('Tolak keterangan < 10 char', !$r['ok'] && isset($r['errors']['keterangan']));

    // keterangan > 500
    $long = str_repeat('a', 501);
    $r = $iz->ajukan($tempUserId, [
        'jenis' => 'Izin', 'tanggal_mulai' => '2026-06-01', 'tanggal_selesai' => '2026-06-02',
        'keterangan' => $long,
    ], null);
    check('Tolak keterangan > 500 char', !$r['ok'] && isset($r['errors']['keterangan']));

    // jenis tidak valid
    $r = $iz->ajukan($tempUserId, [
        'jenis' => 'Liburan', 'tanggal_mulai' => '2026-06-01', 'tanggal_selesai' => '2026-06-02',
        'keterangan' => 'Cukup keterangan ini.',
    ], null);
    check('Tolak jenis selain Izin/Sakit', !$r['ok'] && isset($r['errors']['jenis']));

    // tanggal_mulai > tanggal_selesai
    $r = $iz->ajukan($tempUserId, [
        'jenis' => 'Izin', 'tanggal_mulai' => '2026-06-10', 'tanggal_selesai' => '2026-06-05',
        'keterangan' => 'Cukup keterangan ini.',
    ], null);
    check('Tolak tanggal_mulai > tanggal_selesai', !$r['ok'] && isset($r['errors']['tanggal_mulai']));

    // format tanggal salah
    $r = $iz->ajukan($tempUserId, [
        'jenis' => 'Izin', 'tanggal_mulai' => '2026-13-32', 'tanggal_selesai' => '2026-06-02',
        'keterangan' => 'Cukup keterangan ini.',
    ], null);
    check('Tolak tanggal tidak valid (2026-13-32)', !$r['ok'] && isset($r['errors']['tanggal_mulai']));

    // valid
    $r1 = $iz->ajukan($tempUserId, [
        'jenis' => 'Izin', 'tanggal_mulai' => '2026-06-01', 'tanggal_selesai' => '2026-06-03',
        'keterangan' => 'Keperluan keluarga mendesak.',
    ], null);
    check('Terima izin valid', $r1['ok']);
    check('Nomor referensi terbuat', !empty($r1['nomor_referensi'] ?? null));

    // tumpang tindih (Menunggu)
    $r2 = $iz->ajukan($tempUserId, [
        'jenis' => 'Sakit', 'tanggal_mulai' => '2026-06-02', 'tanggal_selesai' => '2026-06-04',
        'keterangan' => 'Sakit demam tinggi.',
    ], null);
    check('Tolak tumpang tindih dengan Menunggu', !$r2['ok'] && isset($r2['errors']['_overlap']));

    // approve dan cek propagasi ke absensi
    if ($r1['ok']) {
        $rA = $iz->setujui($r1['id'], 1);
        check('Approve izin sukses', $rA['ok']);
        $stmt = Db::pdo()->prepare(
            "SELECT COUNT(*) FROM absensi WHERE pegawai_id = ? AND tanggal BETWEEN '2026-06-01' AND '2026-06-03' AND status = 'Izin'"
        );
        $stmt->execute([$tempUserId]);
        $cnt = (int)$stmt->fetchColumn();
        check('Propagasi izin → absensi (3 hari kerja)', $cnt === 3);

        // re-approve harus gagal (state machine)
        $rA2 = $iz->setujui($r1['id'], 1);
        check('Re-approve gagal (sudah final)', !$rA2['ok']);
    }

    // tolak dengan alasan kurang dari 3 chars
    $r3 = $iz->ajukan($tempUserId, [
        'jenis' => 'Izin', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-07-01',
        'keterangan' => 'Acara keluarga besar.',
    ], null);
    if ($r3['ok']) {
        $rT = $iz->tolak($r3['id'], 1, 'ab');
        check('Tolak izin dengan alasan < 3 char ditolak', !$rT['ok']);
        $rT2 = $iz->tolak($r3['id'], 1, 'Tidak memenuhi syarat administratif');
        check('Tolak izin alasan valid sukses', $rT2['ok']);
    }
}

// ============================================================
section('6. PegawaiService validasi');
$ps = new PegawaiService();
// NIP < 18 digit
$r = $ps->tambah([
    'nip' => '12345', 'username' => 'pegtest1', 'password' => 'PassTest1234',
    'nama' => 'Tester Satu', 'jabatan' => 'Staf',
], 1);
check('Tolak NIP < 18 digit', !$r['ok'] && isset($r['errors']['nip']));

// NIP non-numerik
$r = $ps->tambah([
    'nip' => 'ABCDEFGH123456789X', 'username' => 'pegtest2', 'password' => 'PassTest1234',
    'nama' => 'Tester Dua', 'jabatan' => 'Staf',
], 1);
check('Tolak NIP non-numerik', !$r['ok'] && isset($r['errors']['nip']));

// username < 4 chars
$r = $ps->tambah([
    'nip' => '111122223333444466', 'username' => 'abc', 'password' => 'PassTest1234',
    'nama' => 'Tester Tiga', 'jabatan' => 'Staf',
], 1);
check('Tolak username < 4 char', !$r['ok'] && isset($r['errors']['username']));

// password < 8 chars
$r = $ps->tambah([
    'nip' => '111122223333444477', 'username' => 'pegtest4', 'password' => 'short',
    'nama' => 'Tester Empat', 'jabatan' => 'Staf',
], 1);
check('Tolak password < 8 char', !$r['ok'] && isset($r['errors']['password']));

// nama < 3 chars
$r = $ps->tambah([
    'nip' => '111122223333444488', 'username' => 'pegtest5', 'password' => 'PassTest1234',
    'nama' => 'Ab', 'jabatan' => 'Staf',
], 1);
check('Tolak nama < 3 char', !$r['ok'] && isset($r['errors']['nama']));

// jabatan > 100 chars
$r = $ps->tambah([
    'nip' => '111122223333444499', 'username' => 'pegtest6', 'password' => 'PassTest1234',
    'nama' => 'Tester Enam', 'jabatan' => str_repeat('x', 101),
], 1);
check('Tolak jabatan > 100 char', !$r['ok'] && isset($r['errors']['jabatan']));

// duplikat NIP
$r = $ps->tambah([
    'nip' => '111122223333444455', 'username' => 'pegtest7', 'password' => 'PassTest1234',
    'nama' => 'Tester Tujuh', 'jabatan' => 'Staf',
], 1);
check('Tolak NIP duplikat', !$r['ok'] && isset($r['errors']['_dup']));

// duplikat username
$r = $ps->tambah([
    'nip' => '999988887777666655', 'username' => 'kegtest', 'password' => 'PassTest1234',
    'nama' => 'Tester Delapan', 'jabatan' => 'Staf',
], 1);
check('Tolak username duplikat', !$r['ok'] && isset($r['errors']['_dup']));

// nonaktifkan & cek session revoked
if ($tempUserId) {
    Db::pdo()->prepare("INSERT INTO sessions (id, user_id, created_at, last_seen_at) VALUES (?, ?, NOW(), NOW())")
       ->execute(['testsession_' . $tempUserId, $tempUserId]);
    $r = $ps->nonaktifkan($tempUserId, 1);
    check('Nonaktifkan pegawai sukses', $r['ok']);
    $row = Db::pdo()->query("SELECT status FROM users WHERE id = $tempUserId")->fetch();
    check('Status menjadi Nonaktif', $row['status'] === 'Nonaktif');
    $sess = Db::pdo()->query("SELECT revoked_at FROM sessions WHERE id = 'testsession_$tempUserId'")->fetch();
    check('Sesi pegawai di-revoke', $sess['revoked_at'] !== null);
    Db::pdo()->exec("DELETE FROM sessions WHERE user_id = $tempUserId");
}

// ============================================================
section('7. AbsensiService unit (jendela jam, GPS, swafoto)');
$abs = new AbsensiService();
// reset user kegtest -> Aktif untuk test absen
if ($tempUserId) {
    Db::pdo()->prepare("UPDATE users SET status = 'Aktif' WHERE id = ?")->execute([$tempUserId]);
    Db::pdo()->prepare("DELETE FROM absensi WHERE pegawai_id = ?")->execute([$tempUserId]);

    // status hari ini: hari kerja Senin (assumed today is hari kerja per cfg)
    $now = (new DateTimeImmutable('2026-05-25 09:00:00', new DateTimeZone('Asia/Jakarta')));
    $r = $abs->absenMasuk($tempUserId, null, null, -7.536, 110.668, '', $now);
    check('Tolak absen tanpa swafoto', !$r['ok'] && str_contains($r['error'], 'Swafoto'));

    // GPS jauh
    $jpgDataUrl = 'data:image/jpeg;base64,' . base64_encode(generateMinJpeg());
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, 0.0, 0.0, '', $now);
    check('Tolak GPS di luar radius', !$r['ok'] && str_contains($r['error'], 'luar area'));

    // GPS dekat: sukses Hadir (jendela 09:00 = Hadir)
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, -7.536, 110.668, '', $now);
    check('Absen masuk Hadir sukses', $r['ok'] && $r['status'] === 'Hadir');

    // duplikat
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, -7.536, 110.668, '', $now);
    check('Tolak absen masuk kedua', !$r['ok'] && str_contains($r['error'], 'sudah absen'));

    // pulang sebelum jam pulang
    $r = $abs->absenPulang($tempUserId, null, $jpgDataUrl, -7.536, 110.668, $now);
    check('Tolak pulang sebelum jam pulang', !$r['ok'] && str_contains($r['error'], 'jam pulang'));

    // pulang setelah jam pulang sukses
    $now2 = (new DateTimeImmutable('2026-05-25 15:00:00', new DateTimeZone('Asia/Jakarta')));
    $r = $abs->absenPulang($tempUserId, null, $jpgDataUrl, -7.536, 110.668, $now2);
    check('Absen pulang sukses', $r['ok']);

    // pulang kedua
    $r = $abs->absenPulang($tempUserId, null, $jpgDataUrl, -7.536, 110.668, $now2);
    check('Tolak pulang kedua', !$r['ok'] && str_contains($r['error'], 'sudah absen pulang'));

    // absen di luar hari kerja
    Db::pdo()->prepare("DELETE FROM absensi WHERE pegawai_id = ?")->execute([$tempUserId]);
    $sat = (new DateTimeImmutable('2026-05-30 09:00:00', new DateTimeZone('Asia/Jakarta')));
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, -7.536, 110.668, '', $sat);
    check('Tolak absen di hari Sabtu', !$r['ok'] && str_contains($r['error'], 'bukan hari kerja'));

    // absen dengan jendela terlambat tanpa alasan
    $late = (new DateTimeImmutable('2026-05-25 11:00:00', new DateTimeZone('Asia/Jakarta')));
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, -7.536, 110.668, '', $late);
    check('Tolak absen Terlambat tanpa alasan', !$r['ok']);

    // dengan alasan < 10
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, -7.536, 110.668, 'pendek', $late);
    check('Tolak absen Terlambat alasan < 10 char', !$r['ok']);

    // dengan alasan valid
    $r = $abs->absenMasuk($tempUserId, null, $jpgDataUrl, -7.536, 110.668, 'Macet di jalan utama, ada perbaikan jalan.', $late);
    check('Absen Terlambat dengan alasan valid sukses', $r['ok'] && $r['status'] === 'Terlambat');
}

// helper minimum JPEG
function generateMinJpeg(): string {
    // 1x1 white pixel JPEG
    return base64_decode(
        '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEB' .
        'AQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEB' .
        'AQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEB' .
        'AQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/' .
        'xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAA' .
        'AAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL8AB//Z'
    );
}

// ============================================================
section('8. RekapService validasi periode');
$rs = new RekapService();
$r = $rs->rekapBulanan(0, 2026);
check('Tolak bulan = 0', !$r['ok']);
$r = $rs->rekapBulanan(13, 2026);
check('Tolak bulan = 13', !$r['ok']);
$r = $rs->rekapBulanan(5, 2019);
check('Tolak tahun < 2020', !$r['ok']);
$r = $rs->rekapBulanan(5, 9999);
check('Tolak tahun > tahun berjalan', !$r['ok']);
$r = $rs->rekapBulanan(5, 2026);
check('Terima periode valid', $r['ok'] && is_array($r['rows']));

// total hari kerja Mei 2026 (mask 31, no holidays di week 1-31): hitung manual
$count = 0;
$start = new DateTimeImmutable('2026-05-01');
$end = new DateTimeImmutable('2026-05-31');
$period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
foreach ($period as $d) {
    $dow = (int)$d->format('N');
    if ($dow >= 1 && $dow <= 5) $count++;
}
$thk = $rs->totalHariKerja(5, 2026);
check("Total Hari Kerja Mei 2026 = $count (svc: $thk)", $thk === $count);

// ============================================================
section('9. DashboardService payload');
$ds = new DashboardService();
$today = $ds->ringkasanHariIni();
check('Dashboard payload memiliki kategori', isset($today['kategori']) && isset($today['kategori']['Hadir']));
check('persen_kehadiran = float (>=0)', is_float($today['persen_kehadiran']) && $today['persen_kehadiran'] >= 0);

// ============================================================
section('10. Auth (HTTP integration)');
// validasi panjang username
[$c, $b, $url] = login($base, $cookieJar, '', 'Password123');
check('Login username kosong → tetap di /login', str_ends_with($url, '/login'));
[$c, $b, $url] = login($base, $cookieJar, str_repeat('x', 51), 'Password123');
check('Login username > 50 char → tetap di /login', str_ends_with($url, '/login'));
[$c, $b, $url] = login($base, $cookieJar, 'admin', 'short');
check('Login password < 8 char → tetap di /login', str_ends_with($url, '/login'));
[$c, $b, $url] = login($base, $cookieJar, 'admin', str_repeat('y', 73));
check('Login password > 72 char → tetap di /login', str_ends_with($url, '/login'));

// kredensial salah
[$c, $b, $url] = login($base, $cookieJar, 'admin', 'WrongPassword123');
check('Login password salah → tetap di /login', str_ends_with($url, '/login'));
check('Pesan kesalahan generic (tidak bocor)', str_contains($b, 'Username atau password salah'));

// kredensial benar
[$c, $b, $url] = login($base, $cookieJar, 'admin', 'Password123');
check('Login admin sukses', str_ends_with($url, '/admin'));

// CSRF: POST tanpa CSRF
[$c, $b, $url] = curl_post($base . '/admin/pegawai/baru', $cookieJar, [
    'nip' => '999988887777666644', 'username' => 'csrftest1', 'password' => 'PassTest1234',
    'nama' => 'CSRF Test', 'jabatan' => 'Tester',
]);
check('POST tanpa CSRF → 419', $c === 419);

// ============================================================
section('11. Otorisasi matrix (HTTP)');
// Admin
[$c, ] = curl_get($base . '/admin', $cookieJar);
check('Admin → /admin (200)', $c === 200);
[$c, ] = curl_get($base . '/admin/pegawai', $cookieJar);
check('Admin → /admin/pegawai (200)', $c === 200);
[$c, ] = curl_get($base . '/admin/pengaturan', $cookieJar);
check('Admin → /admin/pengaturan (200)', $c === 200);
[$c, ] = curl_get($base . '/pegawai', $cookieJar);
check('Admin → /pegawai (403)', $c === 403);
[$c, ] = curl_get($base . '/kepala', $cookieJar);
check('Admin → /kepala (403)', $c === 403);

// Pegawai
[, $b] = curl_get($base . '/logout', $cookieJar);
$csrf = get_csrf($b);
curl_post($base . '/logout', $cookieJar, ['_csrf' => $csrf]);

[$c, $b, $url] = login($base, $cookieJar, 'pegawai1', 'Password123');
check('Login pegawai sukses', str_ends_with($url, '/pegawai'));
foreach (['/admin', '/admin/pegawai', '/admin/pengaturan', '/admin/rekap', '/admin/izin', '/kepala'] as $p) {
    [$c, ] = curl_get($base . $p, $cookieJar);
    check("Pegawai → $p (403)", $c === 403);
}
foreach (['/pegawai', '/pegawai/absen', '/pegawai/izin', '/pegawai/kegiatan', '/pegawai/riwayat'] as $p) {
    [$c, ] = curl_get($base . $p, $cookieJar);
    check("Pegawai → $p (200)", $c === 200);
}

// Kepala Desa
[, $b] = curl_get($base . '/logout', $cookieJar);
$csrf = get_csrf($b);
curl_post($base . '/logout', $cookieJar, ['_csrf' => $csrf]);
[$c, $b, $url] = login($base, $cookieJar, 'kades', 'Password123');
check('Login kades sukses', str_ends_with($url, '/kepala'));
foreach (['/admin', '/admin/pegawai', '/admin/pengaturan'] as $p) {
    [$c, ] = curl_get($base . $p, $cookieJar);
    check("Kades → $p (403)", $c === 403);
}
foreach (['/kepala', '/kepala/laporan'] as $p) {
    [$c, ] = curl_get($base . $p, $cookieJar);
    check("Kades → $p (200)", $c === 200);
}

// ============================================================
section('12. Display Board (publik)');
fresh_cookies($cookieJar);
[$c, $b] = curl_get($base . '/display', $cookieJar);
check('Display board publik (200)', $c === 200);
[$c, $b] = curl_get($base . '/display/data', $cookieJar);
check('Display JSON (200)', $c === 200);
$json = json_decode($b, true);
check('JSON valid', is_array($json));
check('Hanya field yang diizinkan', !str_contains($b, '"nip"') && !str_contains($b, '"username"'));
$entry = null;
foreach (['Hadir','Terlambat','Izin','Sakit','Alpha','BelumAbsen'] as $cat) {
    if (!empty($json['kategori'][$cat])) { $entry = $json['kategori'][$cat][0]; break; }
}
if ($entry) {
    $allowed = ['nama','jabatan','status'];
    $extra = array_diff(array_keys($entry), $allowed);
    check('Entry display tidak punya field selain {nama,jabatan,status}', empty($extra));
}

// ============================================================
section('13. Input validation HTTP - Pegawai admin form');
// login admin
fresh_cookies($cookieJar);
[$c, $b, $url] = login($base, $cookieJar, 'admin', 'Password123');
[, $b] = curl_get($base . '/admin/pegawai/baru', $cookieJar);
$csrf = get_csrf($b);
[$c, $b, $url] = curl_post($base . '/admin/pegawai/baru', $cookieJar, [
    '_csrf' => $csrf,
    'nip' => 'INVALID', 'username' => 'ab', 'password' => 'short',
    'nama' => 'A', 'jabatan' => 'X',
]);
check('Form pegawai invalid → redirect kembali', str_contains($url, '/admin/pegawai/baru'));

// ============================================================
section('14. Cleanup');
Db::pdo()->exec("DELETE FROM absensi WHERE pegawai_id IN (SELECT id FROM users WHERE username = 'kegtest')");
Db::pdo()->exec("DELETE FROM kegiatan WHERE pegawai_id IN (SELECT id FROM users WHERE username = 'kegtest')");
Db::pdo()->exec("DELETE FROM izin WHERE pegawai_id IN (SELECT id FROM users WHERE username = 'kegtest')");
Db::pdo()->exec("DELETE FROM users WHERE username = 'kegtest'");
@unlink($cookieJar);
echo "  cleanup OK\n";

// ============================================================
echo "\n";
echo "════════════════════════════════════════════════\n";
echo "RESULTS: $pass passed, $fail failed\n";
if ($fail > 0) {
    echo "Failed tests:\n";
    foreach ($failed as $name) echo "  - $name\n";
}
echo "════════════════════════════════════════════════\n";
exit($fail === 0 ? 0 : 1);
