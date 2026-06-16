<?php
declare(strict_types=1);

namespace App\Domain\Enums;

enum IzinStatus: string
{
    case Menunggu = 'Menunggu';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
}
