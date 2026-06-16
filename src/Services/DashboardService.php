<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class DashboardService
{
    public function __construct(
        private RekapService $rekap = new RekapService(),
        private KegiatanService $kegiatan = new KegiatanService(),
    ) {}

    public function ringkasanHariIni(?\DateTimeImmutable $now = null): array
    {
        $now ??= Time::now();
        $tanggal = $now->format('Y-m-d');

        $pegawai = Db::pdo()->query(
            "SELECT id, nama, jabatan FROM users WHERE role = 'Pegawai' AND status = 'Aktif'"
        )->fetchAll();

        $byId = [];
        foreach ($pegawai as $p) $byId[(int)$p['id']] = $p;

        $stmt = Db::pdo()->prepare(
            "SELECT pegawai_id, status FROM absensi WHERE tanggal = ?"
        );
        $stmt->execute([$tanggal]);
        $statusById = [];
        foreach ($stmt->fetchAll() as $row) {
            $statusById[(int)$row['pegawai_id']] = $row['status'];
        }

        $kategori = [
            'Hadir' => [],
            'Terlambat' => [],
            'Izin' => [],
            'Sakit' => [],
            'Alpha' => [],
            'BelumAbsen' => [],
        ];
        foreach ($pegawai as $p) {
            $s = $statusById[(int)$p['id']] ?? 'BelumAbsen';
            $kategori[$s][] = ['nama' => $p['nama'], 'jabatan' => $p['jabatan']];
        }
        foreach ($kategori as $k => $list) {
            usort($kategori[$k], fn($a, $b) => strcasecmp($a['nama'], $b['nama']));
        }

        $totalAktif = count($pegawai);
        $hadirCount = count($kategori['Hadir']) + count($kategori['Terlambat']);
        $persen = $totalAktif > 0 ? round(($hadirCount / $totalAktif) * 100, 2) : 0.0;

        return [
            'tanggal' => $tanggal,
            'total_aktif' => $totalAktif,
            'persen_kehadiran' => $persen,
            'kategori' => $kategori,
        ];
    }

    public function kegiatanHariIni(?\DateTimeImmutable $now = null): array
    {
        $now ??= Time::now();
        $rows = $this->kegiatan->listHariSemua($now);
        // Group by pegawai
        $grouped = [];
        foreach ($rows as $r) {
            $key = (int)$r['pegawai_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'pegawai_id' => $key,
                    'nama' => $r['pegawai_nama'],
                    'jabatan' => $r['jabatan'],
                    'items' => [],
                ];
            }
            $grouped[$key]['items'][] = [
                'id' => (int)$r['id'],
                'nama' => $r['nama'],
                'jam_mulai' => substr((string)$r['jam_mulai'], 0, 5),
                'jam_selesai' => substr((string)$r['jam_selesai'], 0, 5),
            ];
        }
        return [
            'tanggal' => $now->format('Y-m-d'),
            'total_kegiatan' => count($rows),
            'total_pegawai' => count($grouped),
            'groups' => array_values($grouped),
        ];
    }

    public function ringkasanBulanBerjalan(?\DateTimeImmutable $now = null): array
    {
        $now ??= Time::now();
        $bulan = (int)$now->format('n');
        $tahun = (int)$now->format('Y');

        $r = $this->rekap->rekapBulanan($bulan, $tahun);
        $totals = ['Hadir' => 0, 'Terlambat' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
        foreach ($r['rows'] ?? [] as $row) {
            foreach ($totals as $k => $_) {
                $totals[$k] += $row[$k];
            }
        }
        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_hari_kerja' => $r['total_hari_kerja'] ?? 0,
            'totals' => $totals,
        ];
    }
}
