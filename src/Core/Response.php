<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $code = 302): void
    {
        $base = self::baseUrl();
        $target = (str_starts_with($path, 'http') ? $path : $base . $path);
        header('Location: ' . $target, true, $code);
        exit;
    }

    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }

    public static function baseUrl(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // Hanya hitung base bila SCRIPT_NAME benar-benar entry point app.
        $entryFiles = ['index.php', 'router.php'];
        $isEntry = false;
        foreach ($entryFiles as $f) {
            if (str_ends_with($script, '/' . $f) || $script === '/' . $f) { $isEntry = true; break; }
        }
        if (!$isEntry) return '';
        $base = trim(dirname($script), '/\\');
        return ($base === '' || $base === '.') ? '' : '/' . $base;
    }

    public static function url(string $path): string
    {
        return self::baseUrl() . $path;
    }
}
