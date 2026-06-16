<?php
/**
 * Router script untuk PHP built-in server (php -S 127.0.0.1:8088 -t public public/router.php).
 *
 * Tujuannya: untuk URL non-asset (mis. /file/...) yang seharusnya ditangani oleh aplikasi,
 * server bawaan PHP biasanya akan langsung 404 karena melihat ekstensi (.png/.pdf/.jpg).
 *
 * Logika:
 *   - Jika URL menunjuk ke berkas fisik di public/ (assets, css, js, gambar), serve apa adanya.
 *   - Selain itu, fallback ke index.php (front controller).
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

// Hanya serve file statis di /assets/ atau yang benar-benar ada di public/.
if ($path !== '/' && is_file($file)) {
    return false; // beri tahu server untuk men-serve apa adanya
}

require __DIR__ . '/index.php';
