<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

final class AuthMiddleware
{
    public function handle(Request $req): void
    {
        $auth = new AuthService();
        if (!$auth->isCurrentSessionValid()) {
            Session::destroy();
            Session::start();
            Session::flash('login_error', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
            Response::redirect('/login');
        }
    }
}
