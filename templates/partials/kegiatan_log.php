<?php
/** @var array $kegiatan */
?>
<div class="card card-soft p-6">
  <div class="flex items-center justify-between gap-2">
    <div>
      <h3 class="font-semibold">Log Kegiatan Hari Ini</h3>
      <p class="text-xs text-muted mt-0.5">
        <?= (int)$kegiatan['total_kegiatan'] ?> kegiatan dari <?= (int)$kegiatan['total_pegawai'] ?> pegawai ·
        <?= e(date('d F Y', strtotime($kegiatan['tanggal']))) ?>
      </p>
    </div>
    <span class="badge badge-info"><?= (int)$kegiatan['total_kegiatan'] ?></span>
  </div>

  <?php if (empty($kegiatan['groups'])): ?>
    <p class="text-sm text-muted mt-4">Belum ada kegiatan tercatat hari ini.</p>
  <?php else: ?>
    <div class="mt-4 space-y-4 max-h-[420px] overflow-auto pr-1">
      <?php foreach ($kegiatan['groups'] as $g):
        $parts = preg_split('/\s+/', trim((string)$g['nama']));
        $init = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
      ?>
        <div>
          <div class="flex items-center gap-2.5 mb-2">
            <div class="avatar"><?= e($init) ?></div>
            <div class="min-w-0">
              <div class="text-sm font-medium truncate"><?= e($g['nama']) ?></div>
              <div class="text-xs text-muted truncate"><?= e($g['jabatan']) ?></div>
            </div>
            <span class="badge badge-muted ml-auto"><?= count($g['items']) ?></span>
          </div>
          <ul class="ml-10 space-y-1.5">
            <?php foreach ($g['items'] as $item): ?>
              <li class="flex items-center justify-between gap-2 card p-2.5">
                <span class="text-sm truncate"><?= e($item['nama']) ?></span>
                <span class="text-xs text-muted whitespace-nowrap tabular-nums">
                  <?= e($item['jam_mulai']) ?> – <?= e($item['jam_selesai']) ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
