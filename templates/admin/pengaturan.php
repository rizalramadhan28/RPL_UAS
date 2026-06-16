<?php
use App\Core\Response;
$title = 'Pengaturan';
$base = Response::baseUrl();
ob_start();
$errs = is_string($errors ?? null) ? json_decode($errors, true) : [];
$mask = (int)$cfg['hari_kerja_mask'];
?>
<div class="max-w-3xl mx-auto">
  <div class="card card-soft p-6">
    <h2 class="text-lg font-semibold">Pengaturan Sistem</h2>
    <p class="text-sm text-muted mt-1">Atur jam kerja, koordinat kantor, dan radius absensi.</p>

    <?php if (!empty($errs)): ?>
      <div class="alert alert-danger mt-4">
        <ul><?php foreach ($errs as $msg): ?><li>· <?= e($msg) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e($base . '/admin/pengaturan') ?>" class="mt-5 space-y-4">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <fieldset>
        <legend class="text-sm font-semibold mb-3">Jendela Jam</legend>
        <div class="grid sm:grid-cols-2 gap-3">
          <div><label class="label">Masuk Mulai</label>
            <input type="time" class="input mt-1.5" name="jam_masuk_mulai" value="<?= e(substr($cfg['jam_masuk_mulai'],0,5)) ?>" required>
          </div>
          <div><label class="label">Masuk Selesai</label>
            <input type="time" class="input mt-1.5" name="jam_masuk_selesai" value="<?= e(substr($cfg['jam_masuk_selesai'],0,5)) ?>" required>
          </div>
          <div><label class="label">Terlambat Selesai</label>
            <input type="time" class="input mt-1.5" name="jam_terlambat_selesai" value="<?= e(substr($cfg['jam_terlambat_selesai'],0,5)) ?>" required>
          </div>
          <div><label class="label">Pulang Mulai</label>
            <input type="time" class="input mt-1.5" name="jam_pulang_mulai" value="<?= e(substr($cfg['jam_pulang_mulai'],0,5)) ?>" required>
          </div>
        </div>
      </fieldset>

      <div class="divider"></div>

      <fieldset>
        <legend class="text-sm font-semibold mb-3">Lokasi Kantor & Radius</legend>
        <div class="grid sm:grid-cols-3 gap-3">
          <div><label class="label">Latitude</label>
            <input type="number" step="0.0000001" class="input mt-1.5" name="latitude" value="<?= e((string)$cfg['latitude']) ?>" required>
          </div>
          <div><label class="label">Longitude</label>
            <input type="number" step="0.0000001" class="input mt-1.5" name="longitude" value="<?= e((string)$cfg['longitude']) ?>" required>
          </div>
          <div><label class="label">Radius (m)</label>
            <input type="number" min="10" max="5000" class="input mt-1.5" name="radius_meter" value="<?= e((string)$cfg['radius_meter']) ?>" required>
          </div>
        </div>
      </fieldset>

      <div class="divider"></div>

      <div>
        <label class="label">Nama Desa</label>
        <input class="input mt-1.5" name="nama_desa" value="<?= e($cfg['nama_desa'] ?? 'Desa Wadas') ?>">
      </div>

      <div class="pt-2">
        <button class="btn btn-primary">Simpan Pengaturan</button>
      </div>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
