<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Time;
use App\Core\View;
use App\Services\PengaturanService;
use App\Services\RekapService;

final class LaporanController
{
    public function rekapHtml(Request $req): void
    {
        $now = Time::now();
        $bulan = (int) $req->input('bulan', (int)$now->format('n'));
        $tahun = (int) $req->input('tahun', (int)$now->format('Y'));
        $pegawaiId = (int) $req->input('pegawai_id', 0);

        $svc = new RekapService();
        $cfg = (new PengaturanService())->getAktif();
        $logo = $this->logoDataUri();

        $shared = [
            'cfg' => $cfg,
            'logo' => $logo,
            'cetak_pada' => $now->format('d-m-Y H:i'),
            'cetak_tanggal' => $this->tanggalIndonesia($now),
        ];

        if ($pegawaiId > 0) {
            $data = $svc->rekapHarianPegawai($pegawaiId, $bulan, $tahun);
            View::render('laporan/detail_print', array_merge($shared, ['data' => $data]));
        } else {
            $data = $svc->rekapBulanan($bulan, $tahun);
            View::render('laporan/rekap_print', array_merge($shared, ['data' => $data]));
        }
    }

    /** Logo Kabupaten Karawang sebagai data URI agar tetap tampil saat Save as PDF. */
    private function logoDataUri(): ?string
    {
        $path = __DIR__ . '/../../public/assets/logo-karawang.svg';
        if (!is_file($path)) return null;
        $svg = file_get_contents($path);
        if ($svg === false) return null;
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function tanggalIndonesia(\DateTimeImmutable $dt): string
    {
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return $dt->format('j') . ' ' . $bulan[(int)$dt->format('n')] . ' ' . $dt->format('Y');
    }
}
