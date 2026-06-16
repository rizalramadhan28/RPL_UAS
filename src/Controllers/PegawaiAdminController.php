<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\PegawaiService;

final class PegawaiAdminController
{
    public function index(Request $req): void
    {
        $rows = (new PegawaiService())->listAktif();
        View::render('admin/pegawai_list', [
            'rows' => $rows,
            'csrf' => Csrf::token(),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function form(Request $req, array $params = []): void
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $row = null;
        if ($id) {
            $stmt = Db::pdo()->prepare("SELECT * FROM users WHERE id = ? AND role = 'Pegawai' LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) Response::redirect('/admin/pegawai');
        }
        View::render('admin/pegawai_form', [
            'row' => $row,
            'csrf' => Csrf::token(),
            'errors' => Session::flash('peg_errors'),
            'old' => $_SESSION['_old_peg'] ?? [],
        ]);
        unset($_SESSION['_old_peg']);
    }

    public function tambah(Request $req): void
    {
        $input = [
            'nip' => trim((string)$req->input('nip')),
            'username' => trim((string)$req->input('username')),
            'password' => (string)$req->input('password'),
            'nama' => $req->input('nama'),
            'jabatan' => $req->input('jabatan'),
        ];
        $r = (new PegawaiService())->tambah($input, (int)Session::userId());
        if (!$r['ok']) {
            Session::flash('peg_errors', json_encode($r['errors']));
            $_SESSION['_old_peg'] = $input;
            Response::redirect('/admin/pegawai/baru');
        }
        Session::flash('success', 'Pegawai berhasil ditambahkan.');
        Response::redirect('/admin/pegawai');
    }

    public function ubah(Request $req, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $input = [
            'nama' => $req->input('nama'),
            'jabatan' => $req->input('jabatan'),
            'status' => $req->input('status'),
        ];
        $r = (new PegawaiService())->ubah($id, $input, (int)Session::userId());
        if (!$r['ok']) {
            Session::flash('peg_errors', json_encode($r['errors']));
            $_SESSION['_old_peg'] = $input;
            Response::redirect('/admin/pegawai/' . $id . '/ubah');
        }
        Session::flash('success', 'Data pegawai diperbarui.');
        Response::redirect('/admin/pegawai');
    }

    public function nonaktifkan(Request $req, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $r = (new PegawaiService())->nonaktifkan($id, (int)Session::userId());
        if (!$r['ok']) Session::flash('error', 'Gagal menonaktifkan pegawai.');
        else Session::flash('success', 'Pegawai dinonaktifkan.');
        Response::redirect('/admin/pegawai');
    }
}
