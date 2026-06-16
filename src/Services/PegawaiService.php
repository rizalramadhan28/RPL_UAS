<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Time;

final class PegawaiService
{
    public function listAktif(): array
    {
        return Db::pdo()->query(
            "SELECT id, nip, username, nama, jabatan, role, status, created_at, updated_at
             FROM users WHERE role = 'Pegawai' ORDER BY status DESC, nama ASC"
        )->fetchAll();
    }

    public function tambah(array $input, int $adminId): array
    {
        $errors = $this->validate($input, true);
        if ($errors) return ['ok' => false, 'errors' => $errors];

        $exists = Db::pdo()->prepare("SELECT 1 FROM users WHERE nip = ? OR username = ? LIMIT 1");
        $exists->execute([$input['nip'], $input['username']]);
        if ($exists->fetch()) {
            return ['ok' => false, 'errors' => ['_dup' => 'NIP atau username sudah terdaftar']];
        }

        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        return Db::tx(function () use ($input, $hash, $adminId) {
            $stmt = Db::pdo()->prepare(
                "INSERT INTO users (nip, username, password_hash, nama, jabatan, role, status)
                 VALUES (?, ?, ?, ?, ?, 'Pegawai', 'Aktif')"
            );
            $stmt->execute([
                $input['nip'], $input['username'], $hash,
                trim($input['nama']), trim($input['jabatan']),
            ]);
            $id = (int) Db::pdo()->lastInsertId();
            (new AuditLogService())->record(
                action: 'user_create', targetType: 'user', targetId: $id,
                after: ['nip' => $input['nip'], 'username' => $input['username'], 'nama' => $input['nama']]
            );
            return ['ok' => true, 'id' => $id];
        });
    }

    public function ubah(int $id, array $input, int $adminId): array
    {
        $stmt = Db::pdo()->prepare("SELECT * FROM users WHERE id = ? AND role = 'Pegawai' LIMIT 1");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        if (!$current) return ['ok' => false, 'errors' => ['_notfound' => 'Pegawai tidak ditemukan']];

        $errors = $this->validate($input, false);
        if ($errors) return ['ok' => false, 'errors' => $errors];

        $namaBaru = isset($input['nama']) && $input['nama'] !== null ? trim((string)$input['nama']) : $current['nama'];
        $jabBaru = isset($input['jabatan']) && $input['jabatan'] !== null ? trim((string)$input['jabatan']) : $current['jabatan'];
        $statusBaru = $input['status'] ?? $current['status'];

        return Db::tx(function () use ($id, $namaBaru, $jabBaru, $statusBaru, $current, $input) {
            $stmt = Db::pdo()->prepare(
                "UPDATE users SET nama = ?, jabatan = ?, status = ?, updated_at = ? WHERE id = ?"
            );
            $stmt->execute([
                $namaBaru, $jabBaru, $statusBaru,
                Time::now()->format('Y-m-d H:i:s'),
                $id,
            ]);
            // jika status -> Nonaktif, revoke sessions
            if ($statusBaru === 'Nonaktif' && $current['status'] === 'Aktif') {
                $rev = Db::pdo()->prepare(
                    "UPDATE sessions SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL"
                );
                $rev->execute([Time::now()->format('Y-m-d H:i:s'), $id]);
            }
            (new AuditLogService())->record(
                action: 'user_update',
                targetType: 'user',
                targetId: $id,
                before: ['nama' => $current['nama'], 'jabatan' => $current['jabatan'], 'status' => $current['status']],
                after: ['nama' => $namaBaru, 'jabatan' => $jabBaru, 'status' => $statusBaru]
            );
            return ['ok' => true];
        });
    }

    public function nonaktifkan(int $id, int $adminId): array
    {
        return $this->ubah($id, ['nama' => null, 'jabatan' => null, 'status' => 'Nonaktif'], $adminId);
    }

    private function validate(array $input, bool $isCreate): array
    {
        $errors = [];
        if ($isCreate) {
            $nip = (string)($input['nip'] ?? '');
            if (!preg_match('/^\d{18}$/', $nip)) $errors['nip'] = 'NIP harus 18 digit angka';
            $username = (string)($input['username'] ?? '');
            if (!preg_match('/^[a-zA-Z0-9]{4,30}$/', $username)) $errors['username'] = 'Username 4-30 karakter alfanumerik';
            $password = (string)($input['password'] ?? '');
            if (strlen($password) < 8 || strlen($password) > 72) {
                $errors['password'] = 'Password 8-72 karakter';
            }
        }

        // Saat update dipanggil dari nonaktifkan(): nama/jabatan = null -> skip validasi field tsb
        $namaProvided = !($isCreate === false && ($input['nama'] ?? null) === null);
        $jabatanProvided = !($isCreate === false && ($input['jabatan'] ?? null) === null);

        if ($namaProvided) {
            $nama = trim((string)($input['nama'] ?? ''));
            if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
                $errors['nama'] = 'Nama 3-100 karakter';
            }
        }
        if ($jabatanProvided) {
            $jab = trim((string)($input['jabatan'] ?? ''));
            if (mb_strlen($jab) < 3 || mb_strlen($jab) > 100) {
                $errors['jabatan'] = 'Jabatan 3-100 karakter';
            }
        }
        if (!$isCreate && isset($input['status']) && !in_array($input['status'], ['Aktif','Nonaktif'], true)) {
            $errors['status'] = 'Status tidak valid';
        }
        return $errors;
    }
}
