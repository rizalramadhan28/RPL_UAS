<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Db;
use App\Core\Request;
use App\Core\Time;
use App\Core\View;
use App\Services\RekapService;

final class RekapController
{
    public function index(Request $req): void
    {
        $now = Time::now();
        $bulan = (int) $req->input('bulan', (int)$now->format('n'));
        $tahun = (int) $req->input('tahun', (int)$now->format('Y'));
        $pegawaiId = (int) $req->input('pegawai_id', 0);

        $svc = new RekapService();
        $error = null;

        if ($pegawaiId > 0) {
            $r = $svc->rekapHarianPegawai($pegawaiId, $bulan, $tahun);
        } else {
            $r = $svc->rekapBulanan($bulan, $tahun);
        }
        if (isset($r['ok']) && !$r['ok']) {
            $error = $r['error'];
        }

        $listPeg = Db::pdo()->query(
            "SELECT id, nama FROM users WHERE role = 'Pegawai' ORDER BY nama ASC"
        )->fetchAll();

        View::render('admin/rekap', [
            'data' => $r,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'pegawai_id' => $pegawaiId,
            'pegawai_list' => $listPeg,
            'error' => $error,
        ]);
    }
}
