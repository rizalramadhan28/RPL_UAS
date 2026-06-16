<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

final class Time
{
    private static ?DateTimeZone $tz = null;

    public static function tz(): DateTimeZone
    {
        if (self::$tz === null) {
            self::$tz = new DateTimeZone(Config::get('app', 'timezone', 'Asia/Jakarta'));
        }
        return self::$tz;
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::tz());
    }

    public static function today(): DateTimeImmutable
    {
        return self::now()->setTime(0, 0, 0);
    }

    public static function format(DateTimeImmutable $dt, string $fmt = 'Y-m-d H:i:s'): string
    {
        return $dt->format($fmt);
    }

    public static function fromDb(?string $s): ?DateTimeImmutable
    {
        if ($s === null || $s === '' || $s === '0000-00-00 00:00:00') return null;
        return new DateTimeImmutable($s, self::tz());
    }
}
