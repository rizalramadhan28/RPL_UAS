<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Session;
use App\Core\Time;

final class AuditLogService
{
    public function record(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $before = null,
        ?array $after = null,
        ?array $actor = null,
        ?string $ip = null
    ): void {
        $actor = $actor ?? Session::user();
        $now = Time::now()->format('Y-m-d H:i:s.v');

        $stmt = Db::pdo()->prepare(
            "INSERT INTO audit_log
             (actor_user_id, actor_nama, actor_role, action, target_type, target_id, before_json, after_json, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $actor['id'] ?? null,
            $actor['nama'] ?? null,
            $actor['role'] ?? null,
            $action,
            $targetType,
            $targetId,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            $now,
        ]);
    }
}
