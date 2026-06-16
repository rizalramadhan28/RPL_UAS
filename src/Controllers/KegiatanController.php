<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Time;
use App\Core\View;
use App\Services\KegiatanService;

final class KegiatanController
{
    public function index(Request $req): void
    {
        $userId = (int) Session::userId();
        $rows = (new KegiatanService())->listHari($userId, Time::now());
        View::render('pegawai/kegiatan', [
            'rows' => $rows,
            'csrf' => Csrf::token(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
            'errors' => Session::flash('keg_errors'),
            'old' => $_SESSION['_old_keg'] ?? [],
        ]);
        unset($_SESSION['_old_keg']);
    }

    public function tambah(Request $req): void
    {
        $userId = (int) Session::userId();
        $input = [
            'nama' => $req->input('nama'),
            'jam_mulai' => $req->input('jam_mulai'),
            'jam_selesai' => $req->input('jam_selesai'),
        ];
        $r = (new KegiatanService())->tambah($userId, $input);
        if (!$r['ok']) {
            Session::flash('keg_errors', json_encode($r['errors']));
            $_SESSION['_old_keg'] = $input;
            Response::redirect('/pegawai/kegiatan');
        }
        Session::flash('success', 'Kegiatan tersimpan.');
        Response::redirect('/pegawai/kegiatan');
    }
}
