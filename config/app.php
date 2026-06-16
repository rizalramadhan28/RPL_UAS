<?php
declare(strict_types=1);

return [
    'name' => 'Sistem Absensi Desa Wadas',
    'timezone' => 'Asia/Jakarta',
    'base_url' => '',           // diisi otomatis oleh bootstrap jika kosong
    'session_lifetime' => 60,   // menit (umur token)
    'session_idle' => 30,       // menit (idle timeout)
    'login_throttle' => [
        'max_attempts' => 5,
        'window_minutes' => 10,
        'lock_minutes' => 15,
    ],
    'upload' => [
        'max_swafoto_bytes' => 2 * 1024 * 1024,
        'max_lampiran_bytes' => 2 * 1024 * 1024,
        'max_global_bytes' => 5 * 1024 * 1024,
        'allowed_image' => ['image/jpeg', 'image/png'],
        'allowed_lampiran' => ['image/jpeg', 'image/png', 'application/pdf'],
    ],
    'storage' => [
        'uploads' => __DIR__ . '/../storage/uploads',
        'logs' => __DIR__ . '/../storage/logs',
    ],
];
