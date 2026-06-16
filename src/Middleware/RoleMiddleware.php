<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditLogService;

abstract class RoleMiddleware
{
    abstract protected function allowedRoles(): array;

    public function handle(Request $req): void
    {
        $role = Session::role();
        if (!$role || !in_array($role, $this->allowedRoles(), true)) {
            try {
                (new AuditLogService())->record(
                    action: 'authorization_denied',
                    targetType: 'route',
                    targetId: null,
                    after: ['path' => $req->path, 'method' => $req->method, 'role' => $role]
                );
            } catch (\Throwable $e) { /* ignore */ }
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }
}
