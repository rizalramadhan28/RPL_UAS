<?php
use App\Core\Response;
$title = 'Beranda Pegawai';
$base = Response::baseUrl();
ob_start();
$cfg = $status['pengaturan'];
$absen = $status['absen'];
?>
<div class="space-y-6">
  <!-- Hero -->
  <div class="card card-soft p-6 relative overflow-hidden">
    <div class="absolute inset-0 bg-radial-fade opacity-60 pointer-events-none"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <p class="text-sm text-muted">Halo, <span class="font-medium text-foreground"><?= e($user['nama']) ?></span></p>
        <h2 class="text-2xl font-semibold tracking-tight mt-0.5"><?= e($user['jabatan']) ?></h2>
        <p class="text-sm text-muted mt-1"><?= e(date('l, d F Y')) ?> · <?= e(date('H:i')) ?> WIB</p>
      </div>
      <div class="flex items-center gap-2">
        <?php if ($status['is_hari_kerja']): ?>
          <span class="badge badge-success">Hari kerja</span>
        <?php else: ?>
          <span class="badge badge-muted">Bukan hari kerja</span>
        <?php endif; ?>
        <?php if ($absen): ?>
          <span class="badge status-<?= e($absen['status']) ?>"><?= e($absen['status']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Stat -->
  <div class="grid md:grid-cols-3 gap-4">
    <div class="card card-soft p-5">
      <div class="flex items-center gap-2 text-muted text-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Jam Masuk
      </div>
      <div class="mt-1 text-2xl font-semibold tracking-tight"><?= e($absen['ts_masuk'] ? substr($absen['ts_masuk'], 11, 5) : '—') ?></div>
      <p class="text-xs text-muted mt-1">Jendela <?= e(substr($cfg['jam_masuk_mulai'],0,5)) ?>–<?= e(substr($cfg['jam_terlambat_selesai'],0,5)) ?></p>
    </div>
    <div class="card card-soft p-5">
      <div class="flex items-center gap-2 text-muted text-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 17l5-5-5-5"/><line x1="21" y1="12" x2="9" y2="12"/><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/></svg>
        Jam Pulang
      </div>
      <div class="mt-1 text-2xl font-semibold tracking-tight"><?= e($absen['ts_pulang'] ? substr($absen['ts_pulang'], 11, 5) : '—') ?></div>
      <p class="text-xs text-muted mt-1">Sejak <?= e(substr($cfg['jam_pulang_mulai'],0,5)) ?></p>
    </div>
    <div class="card card-soft p-5">
      <div class="flex items-center gap-2 text-muted text-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Kegiatan Hari Ini
      </div>
      <div class="mt-1 text-2xl font-semibold tracking-tight"><?= count($kegiatan) ?></div>
      <p class="text-xs text-muted mt-1">Tercatat di register kegiatan</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-4">
    <!-- Aksi cepat -->
    <div class="card card-soft p-6 lg:col-span-2 beam">
      <h3 class="font-semibold">Aksi Cepat</h3>
      <p class="text-sm text-muted mt-1">Lakukan absensi dengan swafoto + GPS.</p>

      <div class="mt-4 grid sm:grid-cols-2 gap-3">
        <?php if ($status['can_masuk']): ?>
          <a href="<?= e($base . '/pegawai/absen') ?>" class="card p-4 card-hover flex items-center gap-3 group">
            <span class="inline-flex w-10 h-10 rounded-lg items-center justify-center" style="background: hsl(var(--primary)/0.12); color: hsl(var(--primary));">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
            </span>
            <div class="flex-1">
              <div class="font-medium">Absen Masuk</div>
              <div class="text-xs text-muted">Validasi swafoto + GPS</div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        <?php endif; ?>
        <?php if ($status['can_pulang']): ?>
          <a href="<?= e($base . '/pegawai/absen') ?>" class="card p-4 card-hover flex items-center gap-3">
            <span class="inline-flex w-10 h-10 rounded-lg items-center justify-center" style="background: hsl(199 89% 48% / 0.12); color: hsl(199 89% 38%);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 17l5-5-5-5"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </span>
            <div class="flex-1">
              <div class="font-medium">Absen Pulang</div>
              <div class="text-xs text-muted">Lengkapi catatan harian</div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        <?php endif; ?>
        <a href="<?= e($base . '/pegawai/izin/baru') ?>" class="card p-4 card-hover flex items-center gap-3">
          <span class="inline-flex w-10 h-10 rounded-lg items-center justify-center" style="background: hsl(265 85% 60% / 0.12); color: hsl(265 85% 50%);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </span>
          <div class="flex-1">
            <div class="font-medium">Ajukan Izin/Sakit</div>
            <div class="text-xs text-muted">Lengkapi keterangan & lampiran</div>
          </div>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="<?= e($base . '/pegawai/kegiatan') ?>" class="card p-4 card-hover flex items-center gap-3">
          <span class="inline-flex w-10 h-10 rounded-lg items-center justify-center" style="background: hsl(32 95% 44% / 0.12); color: hsl(32 95% 38%);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg>
          </span>
          <div class="flex-1">
            <div class="font-medium">Catat Kegiatan</div>
            <div class="text-xs text-muted">Register harian</div>
          </div>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>

      <?php if (!$status['can_masuk'] && !$status['can_pulang']): ?>
        <div class="alert alert-info mt-4">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <div>
            <?php if (!$status['is_hari_kerja']): ?>Hari ini bukan hari kerja.<?php
            elseif ($status['has_pulang']): ?>Anda telah menyelesaikan absensi hari ini.<?php
            elseif ($status['has_masuk']): ?>Belum memasuki jam pulang.<?php
            else: ?>Belum/sudah lewat jendela jam absen.<?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Kegiatan -->
    <div class="card card-soft p-6">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">Kegiatan Hari Ini</h3>
        <a class="btn btn-ghost btn-sm" href="<?= e($base . '/pegawai/kegiatan') ?>">Kelola</a>
      </div>
      <?php if (empty($kegiatan)): ?>
        <p class="text-sm text-muted mt-3">Belum ada kegiatan tercatat.</p>
      <?php else: ?>
        <ul class="mt-3 divide-y" style="border-color: hsl(var(--border));">
          <?php foreach ($kegiatan as $k): ?>
            <li class="py-2.5 flex items-center justify-between gap-2">
              <span class="text-sm truncate"><?= e($k['nama']) ?></span>
              <span class="text-xs text-muted whitespace-nowrap"><?= e(substr($k['jam_mulai'],0,5)) ?> – <?= e(substr($k['jam_selesai'],0,5)) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
