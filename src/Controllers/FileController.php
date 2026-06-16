<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Session;
use App\Core\Db;

final class FileController
{
    public function show(Request $req, array $params): void
    {
        $type = $params['type'] ?? '';
        $year = $params['year'] ?? '';
        $month = $params['month'] ?? '';
        $name = $params['name'] ?? '';
        // Nama file = <hex32>_<userId>.<ext>; ekstensi: jpg|jpeg|png|pdf.
        if (!preg_match('/^(swafoto|izin)$/', $type)
            || !preg_match('/^\d{4}$/', $year)
            || !preg_match('/^\d{2}$/', $month)
            || !preg_match('/^[a-f0-9]+_\d+\.(jpg|jpeg|png|pdf)$/i', $name)) {
            \App\Core\Logger::warn('FileController param invalid', ['type'=>$type,'year'=>$year,'month'=>$month,'name'=>$name]);
            http_response_code(404); echo 'Not found (param)'; return;
        }

        $base = Config::get('app', 'storage.uploads', __DIR__ . '/../../storage/uploads');
        $relPath = $type . '/' . $year . '/' . $month . '/' . $name;
        $full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
        if (!is_file($full)) {
            \App\Core\Logger::warn('FileController file missing', ['rel'=>$relPath, 'full'=>$full]);
            http_response_code(404); echo 'Not found (file)'; return;
        }

        // Otorisasi: pegawai hanya boleh akses file miliknya;
        // admin & kepala desa boleh akses semua file
        $role = Session::role();
        if ($role === 'Pegawai') {
            $userId = (int) Session::userId();
            $allowed = $this->isOwnedByUser($type, $relPath, $userId);
            if (!$allowed) { http_response_code(403); echo 'Forbidden'; return; }
        } elseif (!in_array($role, ['Admin', 'KepalaDesa'], true)) {
            http_response_code(403); echo 'Forbidden'; return;
        }

        $mime = mime_content_type($full) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($full));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=300');
        readfile($full);
    }

    private function isOwnedByUser(string $type, string $relPath, int $userId): bool
    {
        if ($type === 'swafoto') {
            $stmt = Db::pdo()->prepare(
                "SELECT 1 FROM absensi
                 WHERE pegawai_id = ? AND (swafoto_masuk = ? OR swafoto_pulang = ?) LIMIT 1"
            );
            $stmt->execute([$userId, $relPath, $relPath]);
        } else {
            $stmt = Db::pdo()->prepare(
                "SELECT 1 FROM izin WHERE pegawai_id = ? AND lampiran_path = ? LIMIT 1"
            );
            $stmt->execute([$userId, $relPath]);
        }
        return (bool) $stmt->fetch();
    }
}
