<?php
declare(strict_types=1);

namespace App\Domain\Enums;

enum AttendanceStatus: string
{
    case Hadir = 'Hadir';
    case Terlambat = 'Terlambat';
    case Izin = 'Izin';
    case Sakit = 'Sakit';
    case Alpha = 'Alpha';
}
