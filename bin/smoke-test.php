<?php
declare(strict_types=1);

/**
 * Smoke test cepat untuk komponen yang tidak butuh DB.
 *   php bin/smoke-test.php
 */
require __DIR__ . '/../src/autoload.php';

use App\Services\GeoService;

$failed = 0;
function check(string $name, bool $ok): void {
    global $failed;
    if ($ok) echo "[OK]   $name\n";
    else { echo "[FAIL] $name\n"; $failed++; }
}

// GeoService: jarak titik sama harus 0
$g = new GeoService();
check('Haversine distance to self is 0',
    abs($g->haversine(-7.536, 110.668, -7.536, 110.668)) < 1e-6);

// Simetris
check('Haversine symmetric',
    abs($g->haversine(-7.536, 110.668, -7.530, 110.670) - $g->haversine(-7.530, 110.670, -7.536, 110.668)) < 1e-6);

// 1 derajat latitude ~ 111km
$d = $g->haversine(0, 0, 1, 0);
check('1 degree latitude ~111 km', $d > 110000 && $d < 112000);

// Csrf basic
session_start();
$t1 = \App\Core\Csrf::token();
check('CSRF token deterministic within session', \App\Core\Csrf::token() === $t1);
check('CSRF validate true for own token', \App\Core\Csrf::validate($t1));
check('CSRF validate false for empty', !\App\Core\Csrf::validate(''));

// Output escape
require_once __DIR__ . '/../src/Core/View.php';
$encoded = \App\Core\View::e('<script>alert(1)</script>');
check('htmlspecialchars escapes <script>', !str_contains($encoded, '<script>'));

if ($failed === 0) {
    echo "\nALL SMOKE TESTS PASSED.\n";
    exit(0);
}
echo "\n$failed test(s) FAILED.\n";
exit(1);
