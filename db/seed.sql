-- Seed data awal untuk Sistem Absensi Desa Wadas
USE sapa_desa;

-- Pengaturan default
INSERT INTO pengaturan (id, jam_masuk_mulai, jam_masuk_selesai, jam_terlambat_selesai, jam_pulang_mulai, latitude, longitude, radius_meter, hari_kerja_mask, nama_desa)
VALUES (1, '08:00:00', '10:00:00', '16:00:00', '14:00:00', -7.5360000, 110.6680000, 100, 31, 'Desa Wadas')
ON DUPLICATE KEY UPDATE id = id;

-- Akun default
-- Password semua: "Password123" (bcrypt)
-- Hash dihasilkan oleh password_hash('Password123', PASSWORD_DEFAULT)
-- $2y$10$wH8YQp5N7VqW5N7QpVqW5O5z8rJK9Y0y8sN3fL2qY5rH8KqV2lM3y (placeholder)
-- Karena bcrypt salt random, generate via PHP CLI saat instalasi.

-- Placeholder users akan di-seed via skrip PHP install.php
