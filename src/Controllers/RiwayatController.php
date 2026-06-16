<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Db;
use App\Core\Request;
use App\Core\Session;
use App\Core\Time;
use App\Core\View;

final class RiwayatController
{
    public function index(Request $req): void
    {
        $userId = (int) Session::userId();
        $now = Time::now();
        $bulan = (int) $req->input('bulan', (int)$now->format('n'));
        $tahun = (int) $req->input('tahun', (int)$now->format('Y'));

        $error = null;
        // 12 bulan terakhir
        $earliest = $now->modify('-12 months');
        $picked = new \DateTimeImmutable(sprintf('%04d-%02d-01', $tahun, $bulan), Time::tz());
        if ($picked < $earliest->modify('first day of this month') || $picked > $now) {
            $error = 'Periode di luar 12 bulan terakhir';
            $bulan = (int)$now->format('n');
            $tahun = (int)$now->format('Y');
        }

        $start = sprintf('%04d-%02d-01', $tahun, $bulan);
        $end = (new \DateTimeImmutable($start, Time::tz()))->modify('last day of this month')->format('Y-m-d');

        $stmt = Db::pdo()->prepare(
            "SELECT * FROM absensi WHERE pegawai_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC"
        );
        $stmt->execute([$userId, $start, $end]);
        $absen = $stmt->fetchAll();

        $stmt = Db::pdo()->prepare(
            "SELECT * FROM izin WHERE pegawai_id = ?
             AND ((tanggal_mulai BETWEEN ? AND ?) OR (tanggal_selesai BETWEEN ? AND ?))
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId, $start, $end, $start, $end]);
        $izin = $stmt->fetchAll();

        View::render('pegawai/riwayat', [
            'absen' => $absen,
            'izin' => $izin,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'error' => $error,
        ]);
    }
}
