<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $cache = [];

    public static function load(string $name): array
    {
        if (!isset(self::$cache[$name])) {
            $path = __DIR__ . '/../../config/' . $name . '.php';
            if (!is_file($path)) {
                throw new \RuntimeException("Config not found: {$name}");
            }
            self::$cache[$name] = require $path;
        }
        return self::$cache[$name];
    }

    public static function get(string $name, string $key, mixed $default = null): mixed
    {
        $cfg = self::load($name);
        $segments = explode('.', $key);
        $val = $cfg;
        foreach ($segments as $seg) {
            if (!is_array($val) || !array_key_exists($seg, $val)) {
                return $default;
            }
            $val = $val[$seg];
        }
        return $val;
    }
}
