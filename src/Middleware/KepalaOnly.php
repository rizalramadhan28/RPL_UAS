<?php
declare(strict_types=1);

namespace App\Middleware;

final class KepalaOnly extends RoleMiddleware
{
    protected function allowedRoles(): array { return ['KepalaDesa']; }
}
