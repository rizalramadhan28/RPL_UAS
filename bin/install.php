<?php
declare(strict_types=1);

/**
 * Installer untuk Sistem Absensi Desa Wadas.
 *
 * Penggunaan:
 *   php bin/install.php
 *
 * Skrip ini akan:
 * - Membuat database (jika belum ada) sesuai config/database.php
 * - Menjalankan migration.sql
 * - Membuat akun default Pegawai, Admin, dan Kepala Desa (jika belum ada)
 *
 * Akun default:
 *   admin    / Password123  (Admin / Kaur Pemerintahan)
 *   kades    / Password123  (Kepala Desa)
 *   pegawai1 / Password123  (Pegawai)
 */

require __DIR__ . '/../src/autoload.php';

use App\Core\App;

App::bootstrap();

$cfg = require __DIR__ . '/../config/database.php';

echo "==> Connecting to MySQL host {$cfg['host']}:{$cfg['port']} ...\n";
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "==> Creating database '{$cfg['database']}' if not exists ...\n";
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$cfg['database']}`");
$pdo->exec("SET time_zone = '+07:00'");

echo "==> Running migration.sql ...\n";
$sql = file_get_contents(__DIR__ . '/../db/migration.sql');
// Strip comments and pre-USE/SET stmts; split by ; at line ends
$lines = explode("\n", $sql);
$cleaned = [];
foreach ($lines as $l) {
    $t = trim($l);
    if ($t === '' || str_starts_with($t, '--')) continue;
    $cleaned[] = $l;
}
$sqlClean = implode("\n", $cleaned);
$stmts = preg_split('/;\s*\n/', $sqlClean);
foreach ($stmts as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    $u = strtoupper(substr($stmt, 0, 14));
    if (str_starts_with($u, 'CREATE DATABAS') || str_starts_with($u, 'USE ') || str_starts_with($u, 'SET TIME_ZONE')) continue;
    try {
        $pdo->exec($stmt);
    } catch (\PDOException $e) {
        echo "  Warning on stmt: " . substr($stmt, 0, 80) . "...\n  " . $e->getMessage() . "\n";
    }
}

echo "==> Seeding pengaturan default ...\n";
$pdo->exec(<<<SQL
INSERT INTO pengaturan (id, jam_masuk_mulai, jam_masuk_selesai, jam_terlambat_selesai, jam_pulang_mulai, latitude, longitude, radius_meter, hari_kerja_mask, nama_desa)
VALUES (1, '08:00:00', '10:00:00', '16:00:00', '14:00:00', -7.5360000, 110.6680000, 100, 31, 'Desa Wadas')
ON DUPLICATE KEY UPDATE id = id
SQL);

echo "==> Seeding default users (jika belum ada) ...\n";
$users = [
    ['nip' => '197001011990010001', 'username' => 'admin',   'nama' => 'Kaur Pemerintahan',   'jabatan' => 'Kaur Pemerintahan', 'role' => 'Admin'],
    ['nip' => '198001011990010001', 'username' => 'kades',   'nama' => 'Kepala Desa Wadas',   'jabatan' => 'Kepala Desa',       'role' => 'KepalaDesa'],
    ['nip' => '199001011990010001', 'username' => 'pegawai1','nama' => 'Pegawai Contoh',      'jabatan' => 'Staf Desa',         'role' => 'Pegawai'],
];
$hash = password_hash('Password123', PASSWORD_DEFAULT);
$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$insert = $pdo->prepare(
    "INSERT INTO users (nip, username, password_hash, nama, jabatan, role, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')"
);
foreach ($users as $u) {
    $check->execute([$u['username']]);
    if ($check->fetch()) {
        echo "  - {$u['username']} sudah ada (skip)\n";
        continue;
    }
    $insert->execute([$u['nip'], $u['username'], $hash, $u['nama'], $u['jabatan'], $u['role']]);
    echo "  + {$u['username']} ({$u['role']}) dibuat\n";
}

echo "==> Memastikan direktori storage ada ...\n";
foreach ([__DIR__ . '/../storage/uploads/swafoto', __DIR__ . '/../storage/uploads/izin', __DIR__ . '/../storage/logs'] as $d) {
    if (!is_dir($d)) @mkdir($d, 0775, true);
}

echo "\nDone.\n";
echo "Buka aplikasi: http://localhost/sapa-desa/public/\n";
echo "Akun default (semua password 'Password123'):\n";
echo "  - admin / Password123 (Admin)\n";
echo "  - kades / Password123 (Kepala Desa)\n";
echo "  - pegawai1 / Password123 (Pegawai)\n";
