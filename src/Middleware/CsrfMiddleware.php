<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

final class CsrfMiddleware
{
    public function handle(Request $req): void
    {
        if (!in_array($req->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        $token = $req->input('_csrf');
        if (!is_string($token) || !Csrf::validate($token)) {
            http_response_code(419);
            View::render('errors/419');
            exit;
        }
    }
}
