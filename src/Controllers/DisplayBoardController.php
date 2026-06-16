<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Time;
use App\Core\View;
use App\Services\DashboardService;
use App\Services\PengaturanService;

final class DisplayBoardController
{
    public function show(Request $req): void
    {
        $cfg = (new PengaturanService())->getAktif();
        View::render('display/board', [
            'cfg' => $cfg,
            'now' => Time::now()->format('d-m-Y H:i'),
        ]);
    }

    public function data(Request $req): void
    {
        $svc = new DashboardService();
        $r = $svc->ringkasanHariIni(Time::now());
        $payload = [
            'tanggal' => $r['tanggal'],
            'total_aktif' => $r['total_aktif'],
            'persen' => $r['persen_kehadiran'],
            'kategori' => [],
            'updated_at' => Time::now()->format('Y-m-d H:i:s'),
        ];
        // Hanya nama, jabatan, status (Req 17.3)
        foreach (['Hadir','Terlambat','Izin','Sakit','Alpha','BelumAbsen'] as $k) {
            $payload['kategori'][$k] = array_map(
                fn($p) => ['nama' => $p['nama'], 'jabatan' => $p['jabatan'], 'status' => $k],
                $r['kategori'][$k] ?? []
            );
        }
        Response::json($payload);
    }
}
