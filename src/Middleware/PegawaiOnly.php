<?php
declare(strict_types=1);

namespace App\Middleware;

final class PegawaiOnly extends RoleMiddleware
{
    protected function allowedRoles(): array { return ['Pegawai']; }
}
