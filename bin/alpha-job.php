<?php
declare(strict_types=1);

/**
 * Alpha Job - Penetapan Status Alpha Otomatis.
 * Dijalankan via cron / Task Scheduler Windows setelah jam_terlambat_selesai.
 *
 * Contoh Task Scheduler (jalankan setiap hari pukul 16:30 WIB):
 *   php D:\laragon\www\sapa-desa\bin\alpha-job.php
 */

require __DIR__ . '/../src/autoload.php';

use App\Core\App;
use App\Services\AlphaJobService;

App::bootstrap();

$service = new AlphaJobService();
$attempts = 0;
$maxAttempts = 3;
$result = null;
do {
    $attempts++;
    try {
        $result = $service->run();
        break;
    } catch (\Throwable $e) {
        \App\Core\Logger::error('AlphaJob exception (attempt ' . $attempts . ')', ['msg' => $e->getMessage()]);
        if ($attempts < $maxAttempts) {
            sleep(300); // 5 menit
        }
    }
} while ($attempts < $maxAttempts);

if ($result && $result['ok'] ?? false) {
    echo "AlphaJob OK: created={$result['created']}\n";
} else {
    echo "AlphaJob FAILED setelah {$attempts} percobaan.\n";
    exit(1);
}
