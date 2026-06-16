<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\PengaturanService;

final class PengaturanController
{
    public function show(Request $req): void
    {
        $svc = new PengaturanService();
        View::render('admin/pengaturan', [
            'cfg' => $svc->getAktif(),
            'csrf' => Csrf::token(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
            'errors' => Session::flash('peng_errors'),
        ]);
    }

    public function simpan(Request $req): void
    {
        $svc = new PengaturanService();
        // Hari kerja dikelola di menu terpisah; pertahankan nilai yang berlaku.
        $mask = (int) $svc->getAktif()['hari_kerja_mask'];
        $input = [
            'jam_masuk_mulai' => $req->input('jam_masuk_mulai'),
            'jam_masuk_selesai' => $req->input('jam_masuk_selesai'),
            'jam_terlambat_selesai' => $req->input('jam_terlambat_selesai'),
            'jam_pulang_mulai' => $req->input('jam_pulang_mulai'),
            'latitude' => $req->input('latitude'),
            'longitude' => $req->input('longitude'),
            'radius_meter' => $req->input('radius_meter'),
            'hari_kerja_mask' => $mask,
            'nama_desa' => $req->input('nama_desa', 'Desa Wadas'),
        ];
        $r = $svc->simpan($input, (int)Session::userId());
        if (!$r['ok']) {
            Session::flash('peng_errors', json_encode($r['errors']));
            Session::flash('error', 'Periksa kembali input pengaturan.');
            Response::redirect('/admin/pengaturan');
        }
        Session::flash('success', 'Pengaturan tersimpan.');
        Response::redirect('/admin/pengaturan');
    }
}
