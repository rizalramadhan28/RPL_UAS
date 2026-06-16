<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = Config::get('app', 'storage.logs', __DIR__ . '/../../storage/logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'app-' . date('Y-m-d') . '.log';
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $msg, array $ctx = []): void { self::log('error', $msg, $ctx); }
    public static function warn(string $msg, array $ctx = []): void { self::log('warn', $msg, $ctx); }
    public static function info(string $msg, array $ctx = []): void { self::log('info', $msg, $ctx); }
}
