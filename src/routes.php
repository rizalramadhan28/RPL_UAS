<?php
declare(strict_types=1);

use App\Controllers\AbsensiAdminController;
use App\Controllers\AdminHomeController;
use App\Controllers\HariKerjaController;
use App\Controllers\AuthController;
use App\Controllers\DisplayBoardController;
use App\Controllers\FileController;
use App\Controllers\IzinController;
use App\Controllers\KegiatanController;
use App\Controllers\KepalaController;
use App\Controllers\LaporanController;
use App\Controllers\PegawaiAdminController;
use App\Controllers\PegawaiHomeController;
use App\Controllers\PengaturanController;
use App\Controllers\RekapController;
use App\Controllers\RiwayatController;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\AdminOnly;
use App\Middleware\AdminOrKepala;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\KepalaOnly;
use App\Middleware\PegawaiOnly;

/** @var \App\Core\Router $router */

// Root -> redirect by role / login
$router->get('/', [new class {
    public function index(Request $req): void
    {
        $u = \App\Core\Session::user();
        if (!$u) Response::redirect('/login');
        match ($u['role']) {
            'Pegawai' => Response::redirect('/pegawai'),
            'Admin' => Response::redirect('/admin'),
            'KepalaDesa' => Response::redirect('/kepala'),
            default => Response::redirect('/login'),
        };
    }
}, 'index']);

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'doLogin']);
$router->get('/logout', [AuthController::class, 'showLogoutConfirm'], [AuthMiddleware::class]);
$router->post('/logout', [AuthController::class, 'doLogout'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Display board (publik)
$router->get('/display', [DisplayBoardController::class, 'show']);
$router->get('/display/data', [DisplayBoardController::class, 'data']);

// File download (autentikasi)
$router->get('/file/{type}/{year}/{month}/{name}', [FileController::class, 'show'], [AuthMiddleware::class]);

// Pegawai
$router->get('/pegawai', [PegawaiHomeController::class, 'home'], [AuthMiddleware::class, PegawaiOnly::class]);
$router->get('/pegawai/absen', [PegawaiHomeController::class, 'absenForm'], [AuthMiddleware::class, PegawaiOnly::class]);
$router->post('/pegawai/absen/masuk', [PegawaiHomeController::class, 'absenMasuk'], [AuthMiddleware::class, PegawaiOnly::class, CsrfMiddleware::class]);
$router->post('/pegawai/absen/pulang', [PegawaiHomeController::class, 'absenPulang'], [AuthMiddleware::class, PegawaiOnly::class, CsrfMiddleware::class]);

$router->get('/pegawai/izin', [IzinController::class, 'pegawaiList'], [AuthMiddleware::class, PegawaiOnly::class]);
$router->get('/pegawai/izin/baru', [IzinController::class, 'pegawaiForm'], [AuthMiddleware::class, PegawaiOnly::class]);
$router->post('/pegawai/izin/baru', [IzinController::class, 'pegawaiSubmit'], [AuthMiddleware::class, PegawaiOnly::class, CsrfMiddleware::class]);

$router->get('/pegawai/kegiatan', [KegiatanController::class, 'index'], [AuthMiddleware::class, PegawaiOnly::class]);
$router->post('/pegawai/kegiatan', [KegiatanController::class, 'tambah'], [AuthMiddleware::class, PegawaiOnly::class, CsrfMiddleware::class]);

$router->get('/pegawai/riwayat', [RiwayatController::class, 'index'], [AuthMiddleware::class, PegawaiOnly::class]);

// Admin
$router->get('/admin', [AdminHomeController::class, 'home'], [AuthMiddleware::class, AdminOnly::class]);
$router->get('/admin/pegawai', [PegawaiAdminController::class, 'index'], [AuthMiddleware::class, AdminOnly::class]);
$router->get('/admin/pegawai/baru', [PegawaiAdminController::class, 'form'], [AuthMiddleware::class, AdminOnly::class]);
$router->post('/admin/pegawai/baru', [PegawaiAdminController::class, 'tambah'], [AuthMiddleware::class, AdminOnly::class, CsrfMiddleware::class]);
$router->get('/admin/pegawai/{id}/ubah', [PegawaiAdminController::class, 'form'], [AuthMiddleware::class, AdminOnly::class]);
$router->post('/admin/pegawai/{id}/ubah', [PegawaiAdminController::class, 'ubah'], [AuthMiddleware::class, AdminOnly::class, CsrfMiddleware::class]);
$router->post('/admin/pegawai/{id}/nonaktifkan', [PegawaiAdminController::class, 'nonaktifkan'], [AuthMiddleware::class, AdminOnly::class, CsrfMiddleware::class]);

// Pengajuan izin: Admin & Kepala Desa boleh setujui/tolak
$router->get('/admin/izin', [IzinController::class, 'adminList'], [AuthMiddleware::class, AdminOrKepala::class]);
$router->post('/admin/izin/{id}/setujui', [IzinController::class, 'adminApprove'], [AuthMiddleware::class, AdminOrKepala::class, CsrfMiddleware::class]);
$router->post('/admin/izin/{id}/tolak', [IzinController::class, 'adminReject'], [AuthMiddleware::class, AdminOrKepala::class, CsrfMiddleware::class]);

$router->get('/admin/pengaturan', [PengaturanController::class, 'show'], [AuthMiddleware::class, AdminOnly::class]);
$router->post('/admin/pengaturan', [PengaturanController::class, 'simpan'], [AuthMiddleware::class, AdminOnly::class, CsrfMiddleware::class]);

$router->get('/admin/rekap', [RekapController::class, 'index'], [AuthMiddleware::class, AdminOnly::class]);
$router->get('/admin/laporan/print', [LaporanController::class, 'rekapHtml'], [AuthMiddleware::class, AdminOnly::class]);
$router->get('/admin/absensi', [AbsensiAdminController::class, 'index'], [AuthMiddleware::class, AdminOrKepala::class]);

// Hari kerja & hari libur: Admin & Kepala Desa
$router->get('/admin/hari-kerja', [HariKerjaController::class, 'show'], [AuthMiddleware::class, AdminOrKepala::class]);
$router->post('/admin/hari-kerja/simpan', [HariKerjaController::class, 'simpanHariKerja'], [AuthMiddleware::class, AdminOrKepala::class, CsrfMiddleware::class]);
$router->post('/admin/hari-kerja/libur/tambah', [HariKerjaController::class, 'tambahHoliday'], [AuthMiddleware::class, AdminOrKepala::class, CsrfMiddleware::class]);
$router->post('/admin/hari-kerja/libur/hapus', [HariKerjaController::class, 'hapusHoliday'], [AuthMiddleware::class, AdminOrKepala::class, CsrfMiddleware::class]);

// Kepala Desa
$router->get('/kepala', [KepalaController::class, 'home'], [AuthMiddleware::class, KepalaOnly::class]);
$router->get('/kepala/laporan', [RekapController::class, 'index'], [AuthMiddleware::class, KepalaOnly::class]);
$router->get('/kepala/laporan/print', [LaporanController::class, 'rekapHtml'], [AuthMiddleware::class, KepalaOnly::class]);

// Kepala Desa juga punya akses sendiri ke pengajuan izin (URL ramah peran)
$router->get('/kepala/izin', [IzinController::class, 'adminList'], [AuthMiddleware::class, KepalaOnly::class]);
$router->post('/kepala/izin/{id}/setujui', [IzinController::class, 'adminApprove'], [AuthMiddleware::class, KepalaOnly::class, CsrfMiddleware::class]);
$router->post('/kepala/izin/{id}/tolak', [IzinController::class, 'adminReject'], [AuthMiddleware::class, KepalaOnly::class, CsrfMiddleware::class]);
$router->get('/kepala/absensi', [AbsensiAdminController::class, 'index'], [AuthMiddleware::class, KepalaOnly::class]);

// Hari kerja & hari libur untuk Kepala Desa
$router->get('/kepala/hari-kerja', [HariKerjaController::class, 'show'], [AuthMiddleware::class, KepalaOnly::class]);
$router->post('/kepala/hari-kerja/simpan', [HariKerjaController::class, 'simpanHariKerja'], [AuthMiddleware::class, KepalaOnly::class, CsrfMiddleware::class]);
$router->post('/kepala/hari-kerja/libur/tambah', [HariKerjaController::class, 'tambahHoliday'], [AuthMiddleware::class, KepalaOnly::class, CsrfMiddleware::class]);
$router->post('/kepala/hari-kerja/libur/hapus', [HariKerjaController::class, 'hapusHoliday'], [AuthMiddleware::class, KepalaOnly::class, CsrfMiddleware::class]);
