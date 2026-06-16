<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Time;
use App\Core\View;
use App\Services\DashboardService;

final class AdminHomeController
{
    public function home(Request $req): void
    {
        $svc = new DashboardService();
        $today = $svc->ringkasanHariIni(Time::now());
        $bulan = $svc->ringkasanBulanBerjalan(Time::now());
        $kegiatan = $svc->kegiatanHariIni(Time::now());
        View::render('admin/home', [
            'today' => $today,
            'bulan' => $bulan,
            'kegiatan' => $kegiatan,
        ]);
    }
}
