<?php
use App\Core\Response;
$title = 'Ajukan Izin/Sakit';
$base = Response::baseUrl();
ob_start();
$errs = is_string($errors ?? null) ? json_decode($errors, true) : [];
$old = $old ?? [];
?>
<div class="max-w-2xl mx-auto">
  <div class="card card-soft p-6">
    <h2 class="text-lg font-semibold">Ajukan Izin / Sakit</h2>
    <p class="text-sm text-muted mt-1">Lengkapi keterangan dan unggah lampiran pendukung jika ada.</p>

    <?php if (!empty($errs)): ?>
      <div class="alert alert-danger mt-4">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul class="space-y-0.5">
          <?php foreach ($errs as $msg): ?><li>· <?= e($msg) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e($base . '/pegawai/izin/baru') ?>" enctype="multipart/form-data" class="mt-5 space-y-4">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <div>
        <label class="label">Jenis</label>
        <select name="jenis" class="select mt-1.5" required>
          <option value="">— pilih —</option>
          <?php foreach (['Izin','Sakit'] as $j): ?>
            <option value="<?= $j ?>" <?= ($old['jenis'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="label">Tanggal Mulai</label>
          <input type="date" class="input mt-1.5" name="tanggal_mulai" value="<?= e($old['tanggal_mulai'] ?? '') ?>" required>
        </div>
        <div>
          <label class="label">Tanggal Selesai</label>
          <input type="date" class="input mt-1.5" name="tanggal_selesai" value="<?= e($old['tanggal_selesai'] ?? '') ?>" required>
        </div>
      </div>
      <div>
        <label class="label">Keterangan</label>
        <textarea class="textarea mt-1.5" name="keterangan" rows="3" minlength="10" maxlength="500" required placeholder="10–500 karakter"><?= e($old['keterangan'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="label">Lampiran <span class="help">PDF / JPG / PNG, maks 2 MB</span></label>
        <input type="file" class="input mt-1.5" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
      </div>
      <div class="flex gap-2 pt-2">
        <button class="btn btn-primary">Kirim Pengajuan</button>
        <a class="btn btn-ghost" href="<?= e($base . '/pegawai/izin') ?>">Batal</a>
      </div>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
