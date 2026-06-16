<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Db;
use App\Core\Session;
use App\Core\Time;

final class AuthService
{
    public function login(string $username, string $password, string $ip): array
    {
        // Validasi panjang awal — tidak menambah throttle counter (Req 1.6)
        $username = trim((string)$username);
        if ($username === '' || strlen($username) > 50) {
            return ['ok' => false, 'code' => 'INVALID_INPUT', 'message' => 'Username tidak valid.'];
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            return ['ok' => false, 'code' => 'INVALID_INPUT', 'message' => 'Password tidak valid.'];
        }

        // Cek throttle
        $lock = $this->checkLock($username);
        if ($lock !== null) {
            return [
                'ok' => false,
                'code' => 'ACCOUNT_LOCKED',
                'message' => "Akun terkunci sementara. Coba lagi dalam {$lock} detik.",
                'lock_seconds' => $lock,
            ];
        }

        $stmt = Db::pdo()->prepare(
            "SELECT id, username, password_hash, nama, jabatan, role, status FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $valid = $user
            && $user['status'] === 'Aktif'
            && password_verify($password, $user['password_hash']);

        $this->recordAttempt($username, $valid, $ip);

        if (!$valid) {
            return ['ok' => false, 'code' => 'INVALID_CREDENTIALS', 'message' => 'Username atau password salah.'];
        }

        // Sukses: buat session
        Session::regenerate();
        Session::set('user', [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'nama' => $user['nama'],
            'jabatan' => $user['jabatan'],
            'role' => $user['role'],
        ]);
        Session::set('login_at', time());
        Session::set('last_seen', time());

        // Persist sessions table (audit + invalidate ability)
        $sid = session_id();
        if (is_string($sid) && $sid !== '') {
            $now = Time::now()->format('Y-m-d H:i:s');
            $stmt = Db::pdo()->prepare(
                "INSERT INTO sessions (id, user_id, created_at, last_seen_at, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE last_seen_at = VALUES(last_seen_at), revoked_at = NULL"
            );
            $stmt->execute([
                hash('sha256', $sid),
                (int)$user['id'],
                $now,
                $now,
                $ip,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        }

        return ['ok' => true, 'user' => Session::user()];
    }

    public function logout(): void
    {
        $sid = session_id();
        if (is_string($sid) && $sid !== '') {
            $hash = hash('sha256', $sid);
            try {
                $stmt = Db::pdo()->prepare("UPDATE sessions SET revoked_at = ? WHERE id = ?");
                $stmt->execute([Time::now()->format('Y-m-d H:i:s'), $hash]);
            } catch (\Throwable $e) {
                // best-effort
            }
        }
        Session::destroy();
    }

    public function isCurrentSessionValid(): bool
    {
        $u = Session::user();
        if (!$u) return false;

        $loginAt = (int) Session::get('login_at', 0);
        $lastSeen = (int) Session::get('last_seen', 0);
        $now = time();

        $lifetime = (int) Config::get('app', 'session_lifetime', 60) * 60;
        $idle = (int) Config::get('app', 'session_idle', 30) * 60;

        if ($loginAt <= 0 || ($now - $loginAt) > $lifetime) return false;
        if ($lastSeen <= 0 || ($now - $lastSeen) > $idle) return false;

        // Cek session table revoked_at
        $sid = session_id();
        if (is_string($sid) && $sid !== '') {
            $hash = hash('sha256', $sid);
            $stmt = Db::pdo()->prepare("SELECT revoked_at FROM sessions WHERE id = ? LIMIT 1");
            $stmt->execute([$hash]);
            $row = $stmt->fetch();
            if ($row && $row['revoked_at'] !== null) return false;
        }

        Session::set('last_seen', $now);
        if ($sid && (rand(1, 10) === 1)) {
            try {
                $stmt = Db::pdo()->prepare("UPDATE sessions SET last_seen_at = ? WHERE id = ?");
                $stmt->execute([Time::now()->format('Y-m-d H:i:s'), hash('sha256', $sid)]);
            } catch (\Throwable $e) {}
        }

        return true;
    }

    private function checkLock(string $username): ?int
    {
        $cfg = Config::get('app', 'login_throttle', []);
        $max = (int)($cfg['max_attempts'] ?? 5);
        $window = (int)($cfg['window_minutes'] ?? 10);
        $lockMin = (int)($cfg['lock_minutes'] ?? 15);

        $now = Time::now();
        $windowStart = $now->modify("-{$window} minutes")->format('Y-m-d H:i:s');

        $stmt = Db::pdo()->prepare(
            "SELECT attempted_at FROM login_attempts
             WHERE username = ? AND success = 0 AND attempted_at >= ?
             ORDER BY attempted_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $username);
        $stmt->bindValue(2, $windowStart);
        $stmt->bindValue(3, $max, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (count($rows) < $max) return null;

        // Cek apakah ada login sukses setelah percobaan terakhir gagal -> kunci berakhir
        $lastFail = $rows[0]['attempted_at'];
        $stmt2 = Db::pdo()->prepare(
            "SELECT 1 FROM login_attempts
             WHERE username = ? AND success = 1 AND attempted_at > ?
             LIMIT 1"
        );
        $stmt2->execute([$username, $lastFail]);
        if ($stmt2->fetch()) return null;

        $lockUntil = (new \DateTimeImmutable($lastFail, Time::tz()))
            ->modify("+{$lockMin} minutes");
        $diff = $lockUntil->getTimestamp() - $now->getTimestamp();
        return $diff > 0 ? $diff : null;
    }

    private function recordAttempt(string $username, bool $success, string $ip): void
    {
        $stmt = Db::pdo()->prepare(
            "INSERT INTO login_attempts (username, success, ip, attempted_at) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$username, $success ? 1 : 0, $ip, Time::now()->format('Y-m-d H:i:s')]);
    }
}
