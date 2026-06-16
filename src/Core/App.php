<?php
declare(strict_types=1);

namespace App\Core;

final class App
{
    public static function bootstrap(): void
    {
        date_default_timezone_set(Config::get('app', 'timezone', 'Asia/Jakarta'));
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_exception_handler(function (\Throwable $e) {
            Logger::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            http_response_code(500);
            if (is_file(__DIR__ . '/../../templates/errors/500.php')) {
                View::render('errors/500', ['message' => 'Terjadi kesalahan pada server']);
            } else {
                echo '<h1>500 Internal Server Error</h1>';
            }
        });

        Session::start();
    }
}
