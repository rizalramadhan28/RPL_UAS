<?php
declare(strict_types=1);

namespace App\Middleware;

final class AdminOnly extends RoleMiddleware
{
    protected function allowedRoles(): array { return ['Admin']; }
}
