<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $files;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Hitung base prefix HANYA jika SCRIPT_NAME mengarah ke entry point asli
        // (Apache/Nginx mod_rewrite akan men-set SCRIPT_NAME ke '/.../public/index.php').
        // Pada PHP built-in server dengan router script, SCRIPT_NAME bisa di-set
        // ke URL request itu sendiri — jangan dipakai sebagai base.
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $entryFiles = ['index.php', 'router.php'];
        $isEntry = false;
        foreach ($entryFiles as $f) {
            if (str_ends_with($script, '/' . $f) || $script === '/' . $f) { $isEntry = true; break; }
        }
        if ($isEntry) {
            $base = trim(dirname($script), '/\\');
            if ($base !== '' && $base !== '.' && str_starts_with($path, '/' . $base)) {
                $path = substr($path, strlen($base) + 1);
            }
        }

        $this->path = '/' . trim($path, '/');
        if ($this->path === '') $this->path = '/';
        $this->query = $_GET ?? [];
        $this->body = $_POST ?? [];
        $this->files = $_FILES ?? [];
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}
