<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Db;
use App\Core\Request;
use App\Core\Time;
use App\Core\View;

final class AbsensiAdminController
{
    /**
     * Daftar absensi pada satu tanggal (default hari ini), termasuk foto.
     * Bisa dipakai Admin atau Kepala Desa.
     */
    public function index(Request $req): void
    {
        $tanggal = (string) $req->input('tanggal', Time::now()->format('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = Time::now()->format('Y-m-d');
        }

        $stmt = Db::pdo()->prepare(
            "SELECT a.*, u.nama AS pegawai_nama, u.jabatan
             FROM absensi a
             JOIN users u ON u.id = a.pegawai_id
             WHERE a.tanggal = ?
             ORDER BY u.nama ASC"
        );
        $stmt->execute([$tanggal]);
        $rows = $stmt->fetchAll();

        // Pegawai aktif yang belum absen pada tanggal tsb
        $stmt = Db::pdo()->prepare(
            "SELECT u.id, u.nama, u.jabatan
             FROM users u
             WHERE u.role = 'Pegawai' AND u.status = 'Aktif'
               AND NOT EXISTS (SELECT 1 FROM absensi a WHERE a.pegawai_id = u.id AND a.tanggal = ?)
             ORDER BY u.nama ASC"
        );
        $stmt->execute([$tanggal]);
        $belumAbsen = $stmt->fetchAll();

        View::render('admin/absensi_hari', [
            'rows' => $rows,
            'belum_absen' => $belumAbsen,
            'tanggal' => $tanggal,
        ]);
    }
}
