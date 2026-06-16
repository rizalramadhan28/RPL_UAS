<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf';
    private const TTL = 3600; // 60 menit

    public static function token(): string
    {
        $now = time();
        $current = $_SESSION[self::KEY] ?? null;
        if (!$current || ($now - ($current['ts'] ?? 0)) > self::TTL) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::KEY] = ['token' => $token, 'ts' => $now];
            return $token;
        }
        return $current['token'];
    }

    public static function validate(?string $token): bool
    {
        if (!$token) return false;
        $current = $_SESSION[self::KEY] ?? null;
        if (!$current) return false;
        if ((time() - ($current['ts'] ?? 0)) > self::TTL) return false;
        return hash_equals($current['token'], $token);
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }
}
