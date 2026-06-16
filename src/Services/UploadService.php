<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class UploadService
{
    public const TYPE_SWAFOTO = 'swafoto';
    public const TYPE_LAMPIRAN = 'lampiran';

    /**
     * @param array $file dari $_FILES
     * @return array{ok:bool,error?:string,path?:string}
     */
    public function save(array $file, string $type, int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Berkas tidak terbaca'];
        }
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'error' => 'Berkas kosong'];
        }
        $globalMax = (int) Config::get('app', 'upload.max_global_bytes', 5 * 1024 * 1024);
        if ($size > $globalMax) {
            return ['ok' => false, 'error' => 'Ukuran berkas melebihi batas global'];
        }

        $allowed = $type === self::TYPE_SWAFOTO
            ? Config::get('app', 'upload.allowed_image', ['image/jpeg', 'image/png'])
            : Config::get('app', 'upload.allowed_lampiran', ['image/jpeg', 'image/png', 'application/pdf']);
        $maxBytes = $type === self::TYPE_SWAFOTO
            ? (int) Config::get('app', 'upload.max_swafoto_bytes', 2 * 1024 * 1024)
            : (int) Config::get('app', 'upload.max_lampiran_bytes', 2 * 1024 * 1024);

        if ($size > $maxBytes) {
            return ['ok' => false, 'error' => 'Ukuran berkas melebihi batas (' . round($maxBytes / 1048576, 1) . ' MB)'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!$mime || !in_array($mime, $allowed, true)) {
            return ['ok' => false, 'error' => 'Format berkas tidak diizinkan'];
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'application/pdf' => 'pdf',
            default => null,
        };
        if (!$ext) return ['ok' => false, 'error' => 'Format tidak diizinkan'];

        // ekstensi nama file user juga harus sesuai whitelist
        $origExt = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowedExts = $type === self::TYPE_SWAFOTO ? ['jpg','jpeg','png'] : ['jpg','jpeg','png','pdf'];
        if (!in_array($origExt, $allowedExts, true)) {
            return ['ok' => false, 'error' => 'Ekstensi berkas tidak diizinkan'];
        }

        $base = Config::get('app', 'storage.uploads', __DIR__ . '/../../storage/uploads');
        $sub = $type === self::TYPE_SWAFOTO ? 'swafoto' : 'izin';
        $year = date('Y');
        $month = date('m');
        $dir = $base . DIRECTORY_SEPARATOR . $sub . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(16)) . '_' . $userId . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $name;
        if (!@move_uploaded_file($file['tmp_name'], $target)) {
            // fallback for non-HTTP test contexts
            if (!@rename($file['tmp_name'], $target)) {
                return ['ok' => false, 'error' => 'Gagal menyimpan berkas'];
            }
        }

        $relPath = $sub . '/' . $year . '/' . $month . '/' . $name;
        return ['ok' => true, 'path' => $relPath];
    }

    /**
     * Decode swafoto data URL (image/jpeg or image/png) ke file di storage.
     * Digunakan untuk capture kamera browser via canvas.toDataURL().
     */
    public function saveDataUrl(string $dataUrl, int $userId): array
    {
        if (!preg_match('#^data:(image/(?:jpeg|png));base64,(.+)$#i', $dataUrl, $m)) {
            return ['ok' => false, 'error' => 'Swafoto tidak valid'];
        }
        $mime = strtolower($m[1]);
        $bin = base64_decode($m[2], true);
        if ($bin === false || strlen($bin) === 0) {
            return ['ok' => false, 'error' => 'Swafoto tidak terbaca'];
        }
        $size = strlen($bin);
        $maxBytes = (int) Config::get('app', 'upload.max_swafoto_bytes', 2 * 1024 * 1024);
        if ($size > $maxBytes) {
            return ['ok' => false, 'error' => 'Ukuran swafoto melebihi 2 MB'];
        }

        // verifikasi via finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($bin);
        if ($detected !== $mime) {
            return ['ok' => false, 'error' => 'Format swafoto tidak valid'];
        }

        $ext = $mime === 'image/png' ? 'png' : 'jpg';
        $base = Config::get('app', 'storage.uploads', __DIR__ . '/../../storage/uploads');
        $year = date('Y'); $month = date('m');
        $dir = $base . DIRECTORY_SEPARATOR . 'swafoto' . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $name = bin2hex(random_bytes(16)) . '_' . $userId . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $name;

        if (file_put_contents($target, $bin, LOCK_EX) === false) {
            return ['ok' => false, 'error' => 'Gagal menyimpan swafoto'];
        }
        return ['ok' => true, 'path' => 'swafoto/' . $year . '/' . $month . '/' . $name];
    }
}
