<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Time;
use App\Core\View;
use App\Services\PengaturanService;

final class HariKerjaController
{
    /** Path basis sesuai peran agar form submit & redirect konsisten. */
    private function basePath(): string
    {
        return Session::role() === 'KepalaDesa' ? '/kepala/hari-kerja' : '/admin/hari-kerja';
    }

    public function show(Request $req): void
    {
        $svc = new PengaturanService();
        $now = Time::now();
        $tahun = (int) $req->input('tahun', (int)$now->format('Y'));

        View::render('admin/hari_kerja', [
            'cfg' => $svc->getAktif(),
            'holidays' => $svc->listHolidays($tahun),
            'tahun' => $tahun,
            'base_path' => $this->basePath(),
            'csrf' => Csrf::token(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
            'errors' => Session::flash('hk_errors'),
        ]);
    }

    public function simpanHariKerja(Request $req): void
    {
        $bits = $req->input('hk', []);
        $mask = 0;
        if (is_array($bits)) {
            foreach ($bits as $b) $mask |= (int)$b;
        }
        $r = (new PengaturanService())->simpanHariKerja($mask, (int)Session::userId());
        if (!$r['ok']) {
            Session::flash('hk_errors', json_encode($r['errors']));
            Session::flash('error', 'Periksa kembali pilihan hari kerja.');
        } else {
            Session::flash('success', 'Hari kerja berhasil diperbarui.');
        }
        Response::redirect($this->basePath());
    }

    public function tambahHoliday(Request $req): void
    {
        $tanggal = (string) $req->input('tanggal', '');
        $nama = (string) $req->input('nama', '');
        $r = (new PengaturanService())->tambahHoliday($tanggal, $nama, (int)Session::userId());
        if (!$r['ok']) Session::flash('error', $r['error']);
        else Session::flash('success', 'Hari libur ditambahkan.');
        Response::redirect($this->basePath());
    }

    public function hapusHoliday(Request $req): void
    {
        $tanggal = (string) $req->input('tanggal', '');
        $r = (new PengaturanService())->hapusHoliday($tanggal, (int)Session::userId());
        if (!$r['ok']) Session::flash('error', $r['error']);
        else Session::flash('success', 'Hari libur dihapus.');
        Response::redirect($this->basePath());
    }
}
