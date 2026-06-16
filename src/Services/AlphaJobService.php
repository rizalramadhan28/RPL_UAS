<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Logger;
use App\Core\Time;

final class AlphaJobService
{
    public function __construct(
        private PengaturanService $pengaturan = new PengaturanService(),
    ) {}

    public function run(?\DateTimeImmutable $tanggal = null): array
    {
        $tanggal ??= Time::now();
        if (!$this->pengaturan->isHariKerja($tanggal)) {
            return ['ok' => true, 'created' => 0, 'note' => 'Bukan hari kerja'];
        }

        $tgl = $tanggal->format('Y-m-d');

        $pegawai = Db::pdo()->query(
            "SELECT id FROM users WHERE role = 'Pegawai' AND status = 'Aktif'"
        )->fetchAll();

        $created = 0;
        $now = Time::now()->format('Y-m-d H:i:s');
        $insert = Db::pdo()->prepare(
            "INSERT INTO absensi (pegawai_id, tanggal, status, sumber, created_at)
             VALUES (?, ?, 'Alpha', 'auto', ?)
             ON DUPLICATE KEY UPDATE status = absensi.status"
        );
        foreach ($pegawai as $p) {
            $insert->execute([(int)$p['id'], $tgl, $now]);
            if ($insert->rowCount() === 1) $created++;
        }
        Logger::info('AlphaJob run', ['tanggal' => $tgl, 'created' => $created]);
        return ['ok' => true, 'created' => $created];
    }
}
