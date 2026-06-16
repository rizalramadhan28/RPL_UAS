<?php
/**
 * End-to-end smoke test: login, akses dashboard masing2 peran, logout.
 * Memerlukan PHP server berjalan di 127.0.0.1:8088 dan MySQL aktif.
 */

$base = 'http://127.0.0.1:8088';
$cookieJar = __DIR__ . '/.test-cookies.txt';
@unlink($cookieJar);

function curl_get($url, $cookieJar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

function curl_post($url, $cookieJar, array $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [$code, $body, $url];
}

function extract_csrf($html) {
    if (preg_match('/name="_csrf"\s+value="([0-9a-f]+)"/', $html, $m)) return $m[1];
    return null;
}

function check($name, $cond) {
    echo ($cond ? "[OK]   " : "[FAIL] ") . $name . "\n";
    return $cond;
}

$pass = 0; $total = 0;
function tick($r) { global $pass, $total; $total++; if ($r) $pass++; }

// 1. GET login -> 200, ambil CSRF
[$c, $b] = curl_get($base . '/login', $cookieJar);
tick(check('GET /login -> 200', $c === 200));
$csrf = extract_csrf($b);
tick(check('CSRF token in form', $csrf !== null));

// 2. POST login admin
[$c, $b, $url] = curl_post($base . '/login', $cookieJar, [
    'username' => 'admin',
    'password' => 'Password123',
    '_csrf' => $csrf,
]);
tick(check('POST /login admin -> redirect to /admin', str_ends_with($url, '/admin')));
tick(check('Admin dashboard loaded', strpos($b, 'Dashboard') !== false));

// 3. Akses /admin/pegawai
[$c, $b] = curl_get($base . '/admin/pegawai', $cookieJar);
tick(check('Admin akses /admin/pegawai -> 200', $c === 200));

// 4. Akses /admin/pengaturan
[$c, $b] = curl_get($base . '/admin/pengaturan', $cookieJar);
tick(check('Admin akses /admin/pengaturan -> 200', $c === 200));

// 5. Akses fitur pegawai harus 403
[$c, $b] = curl_get($base . '/pegawai', $cookieJar);
tick(check('Admin akses /pegawai -> 403', $c === 403));

// 6. Logout admin (POST)
[$c, $b] = curl_get($base . '/logout', $cookieJar);
$csrf2 = extract_csrf($b);
[$c, $b, $url] = curl_post($base . '/logout', $cookieJar, ['_csrf' => $csrf2]);
tick(check('Logout -> redirect ke login', str_contains($url, '/login')));

// 7. Login pegawai
@unlink($cookieJar);
[$c, $b] = curl_get($base . '/login', $cookieJar);
$csrf = extract_csrf($b);
[$c, $b, $url] = curl_post($base . '/login', $cookieJar, [
    'username' => 'pegawai1', 'password' => 'Password123', '_csrf' => $csrf,
]);
tick(check('Login pegawai -> /pegawai', str_ends_with($url, '/pegawai')));

// 8. Pegawai akses /admin -> 403
[$c, $b] = curl_get($base . '/admin', $cookieJar);
tick(check('Pegawai akses /admin -> 403', $c === 403));

// 9. Pegawai akses /pegawai/kegiatan -> 200
[$c, $b] = curl_get($base . '/pegawai/kegiatan', $cookieJar);
tick(check('Pegawai akses /pegawai/kegiatan -> 200', $c === 200));

// 10. Login kades
@unlink($cookieJar);
[$c, $b] = curl_get($base . '/login', $cookieJar);
$csrf = extract_csrf($b);
[$c, $b, $url] = curl_post($base . '/login', $cookieJar, [
    'username' => 'kades', 'password' => 'Password123', '_csrf' => $csrf,
]);
tick(check('Login kades -> /kepala', str_ends_with($url, '/kepala')));

// 11. Kades akses /kepala/laporan -> 200
[$c, $b] = curl_get($base . '/kepala/laporan', $cookieJar);
tick(check('Kades akses /kepala/laporan -> 200', $c === 200));

// 12. Kades akses /admin/pegawai -> 403
[$c, $b] = curl_get($base . '/admin/pegawai', $cookieJar);
tick(check('Kades akses /admin/pegawai -> 403', $c === 403));

// 13. Login dengan kredensial salah harus tetap di /login
@unlink($cookieJar);
[$c, $b] = curl_get($base . '/login', $cookieJar);
$csrf = extract_csrf($b);
[$c, $b, $url] = curl_post($base . '/login', $cookieJar, [
    'username' => 'admin', 'password' => 'WrongPass123', '_csrf' => $csrf,
]);
tick(check('Login salah -> tetap di /login', str_ends_with($url, '/login')));

// 14. Display board (publik)
@unlink($cookieJar);
[$c, $b] = curl_get($base . '/display', $cookieJar);
tick(check('Display board (no login) -> 200', $c === 200));
[$c, $b] = curl_get($base . '/display/data', $cookieJar);
tick(check('Display board JSON -> 200', $c === 200 && str_contains($b, 'kategori')));

@unlink($cookieJar);

echo "\n=== $pass / $total tests passed ===\n";
exit($pass === $total ? 0 : 1);
