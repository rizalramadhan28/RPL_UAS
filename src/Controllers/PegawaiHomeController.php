<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Time;
use App\Core\View;
use App\Services\AbsensiService;
use App\Services\KegiatanService;

final class PegawaiHomeController
{
    public function home(Request $req): void
    {
        $userId = (int) Session::userId();
        $abs = new AbsensiService();
        $status = $abs->getStatusHariIni($userId, Time::now());

        $keg = new KegiatanService();
        $kegiatanHari = $keg->listHari($userId, Time::now());

        View::render('pegawai/home', [
            'status' => $status,
            'kegiatan' => $kegiatanHari,
            'csrf' => Csrf::token(),
            'user' => Session::user(),
        ]);
    }

    public function absenForm(Request $req): void
    {
        $userId = (int) Session::userId();
        $abs = new AbsensiService();
        $status = $abs->getStatusHariIni($userId, Time::now());
        View::render('pegawai/absen_form', [
            'status' => $status,
            'csrf' => Csrf::token(),
            'user' => Session::user(),
        ]);
    }

    public function absenMasuk(Request $req): void
    {
        $userId = (int) Session::userId();
        $foto = $req->input('foto_data');
        $lat = (float) $req->input('lat', '0');
        $lon = (float) $req->input('lon', '0');
        $alasan = (string) $req->input('alasan', '');

        $abs = new AbsensiService();
        $r = $abs->absenMasuk($userId, null, is_string($foto) ? $foto : null, $lat, $lon, $alasan, Time::now());
        if (!$r['ok']) {
            Session::flash('error', $r['error']);
            Response::redirect('/pegawai/absen');
        }
        Session::flash('success', 'Absen masuk berhasil (' . $r['status'] . ').');
        Response::redirect('/pegawai');
    }

    public function absenPulang(Request $req): void
    {
        $userId = (int) Session::userId();
        $foto = $req->input('foto_data');
        $lat = (float) $req->input('lat', '0');
        $lon = (float) $req->input('lon', '0');

        $abs = new AbsensiService();
        $r = $abs->absenPulang($userId, null, is_string($foto) ? $foto : null, $lat, $lon, Time::now());
        if (!$r['ok']) {
            Session::flash('error', $r['error']);
            Response::redirect('/pegawai/absen');
        }
        Session::flash('success', 'Absen pulang berhasil.');
        Response::redirect('/pegawai');
    }
}
