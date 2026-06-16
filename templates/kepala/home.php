<?php
use App\Core\Response;
$title = 'Dashboard Kepala Desa';
$base = Response::baseUrl();
ob_start();
$totalAktif = $today['total_aktif'];
$persen = $today['persen_kehadiran'];
$hadirCount = count($today['kategori']['Hadir']) + count($today['kategori']['Terlambat']);
?>
<div class="flex items-end justify-between mb-5 gap-3 flex-wrap">
  <div>
    <h2 class="text-2xl font-semibold tracking-tight">Dashboard Kepala Desa</h2>
    <p class="text-sm text-muted mt-0.5">Ringkasan kehadiran perangkat desa hari ini.</p>
  </div>
  <a class="btn btn-secondary" href="<?= e($base . '/kepala/laporan') ?>">Lihat Laporan</a>
</div>

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
    <?php if ($totalAktif === 0): ?>
      <div class="text-xs text-muted mt-2">Belum ada pegawai aktif</div>
    <?php else: ?>
      <div class="text-xs text-muted mt-2"><?= $hadirCount ?> dari <?= $totalAktif ?> pegawai aktif</div>
    <?php endif; ?>
  </div>
  <?php foreach (['Hadir','Terlambat','Izin'] as $k): ?>
    <div class="card card-soft p-5">
      <div class="text-sm text-muted"><?= e($k) ?></div>
      <div class="mt-2 text-3xl font-semibold tracking-tight tabular-nums"><?= count($today['kategori'][$k]) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <div class="card card-soft p-6 lg:col-span-2">
    <h3 class="font-semibold">Daftar Pegawai - Hari Ini</h3>
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
          <div class="grid sm:grid-cols-2 gap-2">
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
    </div>
  </div>

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
  </div>
</div>

<!-- Log Kegiatan Pegawai -->
<div class="mt-4">
  <?php include __DIR__ . '/../partials/kegiatan_log.php'; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
