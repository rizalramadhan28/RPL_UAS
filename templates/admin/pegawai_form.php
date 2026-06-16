<?php
use App\Core\Response;
$title = isset($row) && $row ? 'Ubah Pegawai' : 'Tambah Pegawai';
$base = Response::baseUrl();
ob_start();
$errs = is_string($errors ?? null) ? json_decode($errors, true) : [];
$old = $old ?? [];
$action = isset($row) && $row
    ? $base . '/admin/pegawai/' . (int)$row['id'] . '/ubah'
    : $base . '/admin/pegawai/baru';
?>
<div class="max-w-2xl mx-auto">
  <div class="card card-soft p-6">
    <h2 class="text-lg font-semibold"><?= e($title) ?></h2>
    <p class="text-sm text-muted mt-1">Pastikan data sesuai dengan dokumen kepegawaian.</p>

    <?php if (!empty($errs)): ?>
      <div class="alert alert-danger mt-4">
        <ul class="space-y-0.5"><?php foreach ($errs as $msg): ?><li>· <?= e($msg) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>" class="mt-5 space-y-4">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <?php if (!isset($row) || !$row): ?>
        <div>
          <label class="label">NIP <span class="help">18 digit</span></label>
          <input class="input mt-1.5" name="nip" pattern="\d{18}" maxlength="18" value="<?= e($old['nip'] ?? '') ?>" required>
        </div>
        <div>
          <label class="label">Username <span class="help">4–30 alfanumerik</span></label>
          <input class="input mt-1.5" name="username" pattern="[a-zA-Z0-9]{4,30}" value="<?= e($old['username'] ?? '') ?>" required>
        </div>
        <div>
          <label class="label">Password Awal <span class="help">min 8 karakter</span></label>
          <input type="password" class="input mt-1.5" name="password" minlength="8" required>
        </div>
      <?php endif; ?>
      <div>
        <label class="label">Nama Lengkap</label>
        <input class="input mt-1.5" name="nama" minlength="3" maxlength="100" value="<?= e($old['nama'] ?? ($row['nama'] ?? '')) ?>" required>
      </div>
      <div>
        <label class="label">Jabatan</label>
        <input class="input mt-1.5" name="jabatan" minlength="3" maxlength="100" value="<?= e($old['jabatan'] ?? ($row['jabatan'] ?? '')) ?>" required>
      </div>
      <?php if (isset($row) && $row): ?>
        <div>
          <label class="label">Status</label>
          <select class="select mt-1.5" name="status">
            <?php foreach (['Aktif','Nonaktif'] as $s): ?>
              <option value="<?= $s ?>" <?= ($row['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="flex gap-2 pt-2">
        <button class="btn btn-primary">Simpan</button>
        <a class="btn btn-ghost" href="<?= e($base . '/admin/pegawai') ?>">Batal</a>
      </div>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
