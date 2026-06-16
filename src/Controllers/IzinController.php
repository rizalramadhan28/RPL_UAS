<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\IzinService;

final class IzinController
{
    public function pegawaiList(Request $req): void
    {
        $userId = (int) Session::userId();
        $svc = new IzinService();
        $rows = $svc->listByPegawai($userId);
        View::render('pegawai/izin_list', [
            'rows' => $rows,
            'csrf' => Csrf::token(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function pegawaiForm(Request $req): void
    {
        View::render('pegawai/izin_form', [
            'csrf' => Csrf::token(),
            'errors' => Session::flash('izin_errors'),
            'old' => $_SESSION['_old_izin'] ?? [],
        ]);
        unset($_SESSION['_old_izin']);
    }

    public function pegawaiSubmit(Request $req): void
    {
        $userId = (int) Session::userId();
        $svc = new IzinService();
        $input = [
            'jenis' => $req->input('jenis'),
            'tanggal_mulai' => $req->input('tanggal_mulai'),
            'tanggal_selesai' => $req->input('tanggal_selesai'),
            'keterangan' => $req->input('keterangan'),
        ];
        $r = $svc->ajukan($userId, $input, $req->file('lampiran'));
        if (!$r['ok']) {
            Session::flash('izin_errors', json_encode($r['errors']));
            $_SESSION['_old_izin'] = $input;
            Response::redirect('/pegawai/izin/baru');
        }
        Session::flash('success', 'Pengajuan berhasil dengan nomor referensi ' . $r['nomor_referensi'] . '.');
        Response::redirect('/pegawai/izin');
    }

    // Admin
    public function adminList(Request $req): void
    {
        $page = max(1, (int) $req->input('page', 1));
        $svc = new IzinService();
        $data = $svc->listMenunggu($page);
        View::render('admin/izin_list', [
            'data' => $data,
            'csrf' => Csrf::token(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function adminApprove(Request $req, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $r = (new IzinService())->setujui($id, (int)Session::userId());
        if (!$r['ok']) Session::flash('error', $r['error']); else Session::flash('success', 'Pengajuan disetujui.');
        Response::redirect($this->izinListPath());
    }

    public function adminReject(Request $req, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $alasan = (string) $req->input('alasan', '');
        $r = (new IzinService())->tolak($id, (int)Session::userId(), $alasan);
        if (!$r['ok']) Session::flash('error', $r['error']); else Session::flash('success', 'Pengajuan ditolak.');
        Response::redirect($this->izinListPath());
    }

    private function izinListPath(): string
    {
        return Session::role() === 'KepalaDesa' ? '/kepala/izin' : '/admin/izin';
    }
}
