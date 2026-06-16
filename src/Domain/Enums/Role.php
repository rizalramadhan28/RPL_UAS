<?php
declare(strict_types=1);

namespace App\Domain\Enums;

enum Role: string
{
    case Pegawai = 'Pegawai';
    case Admin = 'Admin';
    case KepalaDesa = 'KepalaDesa';
}
