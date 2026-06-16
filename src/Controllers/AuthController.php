<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

final class AuthController
{
    public function showLogin(Request $req): void
    {
        if (Session::user()) {
            $this->redirectByRole();
        }
        View::render('auth/login', [
            'csrf' => Csrf::token(),
            'error' => Session::flash('login_error'),
        ]);
    }

    public function doLogin(Request $req): void
    {
        $token = $req->input('_csrf');
        if (!is_string($token) || !Csrf::validate($token)) {
            Session::flash('login_error', 'Sesi keamanan kedaluwarsa, silakan muat ulang halaman.');
            Response::redirect('/login');
        }

        $username = (string)$req->input('username', '');
        $password = (string)$req->input('password', '');
        $auth = new AuthService();
        $r = $auth->login($username, $password, $req->ip());
        if (!$r['ok']) {
            Session::flash('login_error', $r['message']);
            Response::redirect('/login');
        }
        $this->redirectByRole();
    }

    public function showLogoutConfirm(Request $req): void
    {
        View::render('auth/logout_confirm', ['csrf' => Csrf::token()]);
    }

    public function doLogout(Request $req): void
    {
        (new AuthService())->logout();
        Response::redirect('/login');
    }

    private function redirectByRole(): void
    {
        $role = Session::role();
        match ($role) {
            'Pegawai' => Response::redirect('/pegawai'),
            'Admin' => Response::redirect('/admin'),
            'KepalaDesa' => Response::redirect('/kepala'),
            default => Response::redirect('/login'),
        };
    }
}
