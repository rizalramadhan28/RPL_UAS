<?php
declare(strict_types=1);

namespace App\Domain\Enums;

enum IzinJenis: string
{
    case Izin = 'Izin';
    case Sakit = 'Sakit';
}
