<?php
use App\Core\Response;
$title = 'Dashboard Admin';
$base = Response::baseUrl();
ob_start();
$totalAktif = $today['total_aktif'];
$persen = $today['persen_kehadiran'];
$hadirCount = count($today['kategori']['Hadir']) + count($today['kategori']['Terlambat']);
?>
<div class="flex items-end justify-between mb-5 gap-3 flex-wrap">
  <div>
    <h2 class="text-2xl font-semibold tracking-tight">Dashboard</h2>
    <p class="text-sm text-muted mt-0.5">Ringkasan kehadiran perangkat desa hari ini.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e($base . '/admin/rekap') ?>">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    Lihat Rekap
  </a>
</div>

<!-- Stats -->
<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
  <div class="card card-soft p-5">
    <div class="text-xs text-muted">Persentase Kehadiran</div>
    <div class="mt-2 flex items-end gap-1">
      <div class="text-3xl font-semibold tracking-tight"><?= number_format($persen, 2) ?></div>
      <div class="text-base text-muted mb-0.5">%</div>
    </div>
    <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background: hsl(var(--muted));">
      <div class="h-full" style="width: <?= max(0, min(100, $persen)) ?>%; background: linear-gradient(90deg, hsl(var(--primary)), hsl(199 89% 48%));"></div>
    </div>
    <div class="text-xs text-muted mt-2"><?= $hadirCount ?> dari <?= $totalAktif ?> pegawai aktif</div>
  </div>
  <?php
  $colorMap = [
    'Hadir' => ['bg' => 'hsl(142 71% 35% / 0.12)', 'fg' => 'hsl(142 71% 28%)'],
    'Terlambat' => ['bg' => 'hsl(32 95% 44% / 0.14)', 'fg' => 'hsl(32 95% 30%)'],
    'Izin' => ['bg' => 'hsl(199 89% 48% / 0.12)', 'fg' => 'hsl(199 89% 32%)'],
    'Sakit' => ['bg' => 'hsl(265 85% 60% / 0.12)', 'fg' => 'hsl(265 85% 42%)'],
    'Alpha' => ['bg' => 'hsl(0 84% 60% / 0.12)', 'fg' => 'hsl(0 70% 42%)'],
  ];
  ?>
  <?php foreach (['Hadir','Terlambat','Izin','Sakit'] as $k): ?>
    <div class="card card-soft p-5">
      <div class="flex items-center gap-2">
        <span class="inline-flex w-8 h-8 rounded-md items-center justify-center" style="background: <?= $colorMap[$k]['bg'] ?>; color: <?= $colorMap[$k]['fg'] ?>;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <?php switch ($k) { case 'Hadir': ?><polyline points="20 6 9 17 4 12"/><?php break; case 'Terlambat': ?><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><?php break; case 'Izin': ?><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><?php break; case 'Sakit': ?><path d="M22 12h-4l-3 9L9 3l-3 9H2"/><?php break; } ?>
          </svg>
        </span>
        <div class="text-sm text-muted"><?= e($k) ?></div>
      </div>
      <div class="text-3xl font-semibold tracking-tight mt-2"><?= count($today['kategori'][$k]) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <!-- Daftar -->
  <div class="card card-soft p-6 lg:col-span-2">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold">Daftar Pegawai - Hari Ini</h3>
      <span class="text-xs text-muted"><?= e(date('d F Y')) ?></span>
    </div>
    <div class="mt-4 space-y-4">
      <?php foreach (['Hadir','Terlambat','Izin','Sakit','Alpha','BelumAbsen'] as $k):
        $list = $today['kategori'][$k];
        if (empty($list)) continue;
      ?>
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="badge status-<?= e($k) ?>"><?= e($k === 'BelumAbsen' ? 'Belum Absen' : $k) ?></span>
            <span class="text-xs text-muted">(<?= count($list) ?>)</span>
          </div>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <?php foreach ($list as $p): ?>
              <div class="card p-3 flex items-center gap-2.5">
                <div class="avatar"><?php
                  $parts = preg_split('/\s+/', trim($p['nama']));
                  echo strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
                ?></div>
                <div class="min-w-0">
                  <div class="text-sm font-medium truncate"><?= e($p['nama']) ?></div>
                  <div class="text-xs text-muted truncate"><?= e($p['jabatan']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php
      $isEmpty = true;
      foreach ($today['kategori'] as $list) { if (!empty($list)) { $isEmpty = false; break; } }
      if ($isEmpty): ?>
        <p class="text-sm text-muted">Belum ada data kehadiran hari ini.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Rekap bulan berjalan -->
  <div class="card card-soft p-6">
    <h3 class="font-semibold">Rekap Bulan Berjalan</h3>
    <p class="text-xs text-muted mt-0.5">Periode <?= str_pad((string)$bulan['bulan'],2,'0',STR_PAD_LEFT) ?>/<?= $bulan['tahun'] ?> · HK <?= $bulan['total_hari_kerja'] ?></p>
    <ul class="mt-3 divide-y" style="border-color: hsl(var(--border));">
      <?php foreach ($bulan['totals'] as $k => $v): ?>
        <li class="py-2.5 flex items-center justify-between">
          <span class="badge status-<?= e($k) ?>"><?= e($k) ?></span>
          <span class="text-base font-semibold tabular-nums"><?= (int)$v ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <a class="btn btn-secondary w-full mt-4" href="<?= e($base . '/admin/rekap') ?>">Lihat Rekap Lengkap</a>
  </div>
</div>

<!-- Log Kegiatan Pegawai -->
<div class="mt-4">
  <?php include __DIR__ . '/../partials/kegiatan_log.php'; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
