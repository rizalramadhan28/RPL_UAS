<?php
use App\Core\Response;
$title = 'Kegiatan Hari Ini';
$base = Response::baseUrl();
ob_start();
$errs = is_string($errors ?? null) ? json_decode($errors, true) : [];
$old = $old ?? [];
?>
<div class="grid lg:grid-cols-5 gap-4">
  <div class="card card-soft p-6 lg:col-span-2">
    <h3 class="font-semibold">Tambah Kegiatan</h3>
    <?php if (!empty($errs)): ?>
      <div class="alert alert-danger mt-3">
        <ul><?php foreach ($errs as $msg): ?><li>· <?= e($msg) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" action="<?= e($base . '/pegawai/kegiatan') ?>" class="mt-4 space-y-4">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <div>
        <label class="label">Nama Kegiatan</label>
        <input class="input mt-1.5" name="nama" minlength="3" maxlength="200" value="<?= e($old['nama'] ?? '') ?>" required placeholder="3–200 karakter">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="label">Mulai</label>
          <input type="time" class="input mt-1.5" name="jam_mulai" value="<?= e($old['jam_mulai'] ?? '') ?>" required>
        </div>
        <div>
          <label class="label">Selesai</label>
          <input type="time" class="input mt-1.5" name="jam_selesai" value="<?= e($old['jam_selesai'] ?? '') ?>" required>
        </div>
      </div>
      <button class="btn btn-primary w-full">Simpan</button>
    </form>
  </div>
  <div class="card card-soft p-6 lg:col-span-3">
    <h3 class="font-semibold">Daftar Kegiatan Hari Ini</h3>
    <?php if (empty($rows)): ?>
      <p class="text-sm text-muted mt-3">Belum ada kegiatan tercatat untuk hari ini.</p>
    <?php else: ?>
      <div class="mt-3 tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Nama</th><th>Mulai</th><th>Selesai</th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= e($r['nama']) ?></td>
                <td class="text-muted"><?= e(substr($r['jam_mulai'],0,5)) ?></td>
                <td class="text-muted"><?= e(substr($r['jam_selesai'],0,5)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
