<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class RekapService
{
    public function __construct(
        private PengaturanService $pengaturan = new PengaturanService(),
    ) {}

    /**
     * @return array{ok:bool,error?:string,rows?:array,total_hari_kerja?:int,bulan?:int,tahun?:int}
     */
    public function rekapBulanan(int $bulan, int $tahun): array
    {
        $now = Time::now();
        if ($bulan < 1 || $bulan > 12) return ['ok' => false, 'error' => 'Bulan tidak valid'];
        if ($tahun < 2020 || $tahun > (int)$now->format('Y')) return ['ok' => false, 'error' => 'Tahun tidak valid'];

        $start = sprintf('%04d-%02d-01', $tahun, $bulan);
        $end = (new \DateTimeImmutable($start, Time::tz()))->modify('last day of this month')->format('Y-m-d');

        // Per pegawai aktif
        $pegawaiList = Db::pdo()->query(
            "SELECT id, nama, jabatan FROM users WHERE role = 'Pegawai' AND status = 'Aktif' ORDER BY nama ASC"
        )->fetchAll();

        $stmt = Db::pdo()->prepare(
            "SELECT pegawai_id, status, COUNT(*) as cnt
             FROM absensi
             WHERE tanggal BETWEEN ? AND ?
             GROUP BY pegawai_id, status"
        );
        $stmt->execute([$start, $end]);
        $by = [];
        foreach ($stmt->fetchAll() as $row) {
            $by[$row['pegawai_id']][$row['status']] = (int)$row['cnt'];
        }

        $rows = [];
        foreach ($pegawaiList as $p) {
            $b = $by[$p['id']] ?? [];
            $rows[] = [
                'id' => (int)$p['id'],
                'nama' => $p['nama'],
                'jabatan' => $p['jabatan'],
                'Hadir' => $b['Hadir'] ?? 0,
                'Terlambat' => $b['Terlambat'] ?? 0,
                'Izin' => $b['Izin'] ?? 0,
                'Sakit' => $b['Sakit'] ?? 0,
                'Alpha' => $b['Alpha'] ?? 0,
            ];
        }

        $thk = $this->totalHariKerja($bulan, $tahun);
        return ['ok' => true, 'rows' => $rows, 'total_hari_kerja' => $thk, 'bulan' => $bulan, 'tahun' => $tahun];
    }

    public function rekapHarianPegawai(int $pegawaiId, int $bulan, int $tahun): array
    {
        if ($bulan < 1 || $bulan > 12) return ['ok' => false, 'error' => 'Bulan tidak valid'];
        $now = Time::now();
        if ($tahun < 2020 || $tahun > (int)$now->format('Y')) return ['ok' => false, 'error' => 'Tahun tidak valid'];

        $start = sprintf('%04d-%02d-01', $tahun, $bulan);
        $end = (new \DateTimeImmutable($start, Time::tz()))->modify('last day of this month')->format('Y-m-d');

        $stmt = Db::pdo()->prepare(
            "SELECT * FROM absensi WHERE pegawai_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal ASC"
        );
        $stmt->execute([$pegawaiId, $start, $end]);
        $rows = $stmt->fetchAll();

        $stmt = Db::pdo()->prepare("SELECT id, nama, jabatan FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$pegawaiId]);
        $pegawai = $stmt->fetch();

        return [
            'ok' => true,
            'pegawai' => $pegawai,
            'rows' => $rows,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_hari_kerja' => $this->totalHariKerja($bulan, $tahun),
        ];
    }

    public function totalHariKerja(int $bulan, int $tahun): int
    {
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $tahun, $bulan), Time::tz());
        $end = $start->modify('last day of this month');
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1 day'));
        $count = 0;
        foreach ($period as $d) {
            if ($this->pengaturan->isHariKerja($d)) $count++;
        }
        return $count;
    }
}
