<?php
use App\Core\Response;
$title = 'Pengaturan Hari Kerja';
$base = Response::baseUrl();
ob_start();
$errs = is_string($errors ?? null) ? json_decode($errors, true) : [];
$mask = (int)$cfg['hari_kerja_mask'];
$bp = $base . $base_path;
$namaHari = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
?>
<div class="mb-5">
  <h2 class="text-xl font-semibold tracking-tight">Pengaturan Hari Kerja</h2>
  <p class="text-sm text-muted mt-0.5">Atur hari kerja mingguan dan daftar hari libur. Absensi hanya berlaku pada hari kerja.</p>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <!-- Hari kerja mingguan -->
  <div class="card card-soft p-6">
    <h3 class="font-semibold">Hari Kerja Mingguan</h3>
    <p class="text-sm text-muted mt-1">Centang hari yang dihitung sebagai hari kerja.</p>

    <?php if (!empty($errs)): ?>
      <div class="alert alert-danger mt-3">
        <?php foreach ($errs as $msg): ?><div>· <?= e($msg) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e($bp . '/simpan') ?>" class="mt-4">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <div class="flex flex-wrap gap-2">
        <?php foreach ($namaHari as $i => $n): $bit = 1 << $i; $checked = ($mask & $bit) ? true : false; ?>
          <label class="card cursor-pointer px-3 py-2 text-sm select-none flex items-center gap-2"
                 style="<?= $checked ? 'background: hsl(var(--primary)/0.10); border-color: hsl(var(--primary)/0.4);' : '' ?>">
            <input type="checkbox" name="hk[]" value="<?= $bit ?>" <?= $checked ? 'checked' : '' ?> class="accent-primary"
                   onchange="this.closest('label').style.background = this.checked ? 'hsl(var(--primary)/0.10)' : ''; this.closest('label').style.borderColor = this.checked ? 'hsl(var(--primary)/0.4)' : '';">
            <?= $n ?>
          </label>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary mt-4">Simpan Hari Kerja</button>
    </form>

    <div class="divider my-5"></div>
    <div class="text-sm">
      <div class="text-muted mb-1">Hari kerja saat ini:</div>
      <div class="flex flex-wrap gap-1.5">
        <?php $ada = false; foreach ($namaHari as $i => $n): if ($mask & (1 << $i)): $ada = true; ?>
          <span class="badge badge-success"><?= $n ?></span>
        <?php endif; endforeach; if (!$ada): ?><span class="text-muted">Belum ada</span><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Hari libur -->
  <div class="card card-soft p-6">
    <div class="flex items-center justify-between gap-2">
      <h3 class="font-semibold">Hari Libur</h3>
      <form method="get" class="flex gap-2">
        <input type="number" name="tahun" value="<?= e((string)$tahun) ?>" min="2020" class="input" style="width:110px">
        <button class="btn btn-secondary btn-sm">Lihat</button>
      </form>
    </div>
    <p class="text-sm text-muted mt-1">Tanggal pada daftar ini dikecualikan dari hari kerja (libur nasional/cuti bersama).</p>

    <form method="post" action="<?= e($bp . '/libur/tambah') ?>" class="mt-4 flex flex-wrap gap-2 items-end">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <div>
        <label class="label">Tanggal</label>
        <input type="date" name="tanggal" class="input mt-1.5" required>
      </div>
      <div class="flex-1" style="min-width:160px">
        <label class="label">Keterangan</label>
        <input type="text" name="nama" class="input mt-1.5" minlength="3" maxlength="100" placeholder="cth. Hari Kemerdekaan" required>
      </div>
      <button class="btn btn-primary">Tambah</button>
    </form>

    <div class="mt-4 tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Tanggal</th><th>Keterangan</th><th style="width:80px;">Aksi</th></tr></thead>
        <tbody>
          <?php if (empty($holidays)): ?>
            <tr><td colspan="3" class="text-center text-muted py-6">Belum ada hari libur untuk tahun <?= (int)$tahun ?>.</td></tr>
          <?php else: foreach ($holidays as $h): ?>
            <tr>
              <td class="text-sm font-medium"><?= e($h['tanggal']) ?></td>
              <td class="text-sm"><?= e($h['nama']) ?></td>
              <td>
                <form method="post" action="<?= e($bp . '/libur/hapus') ?>" onsubmit="return confirm('Hapus hari libur ini?')">
                  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="tanggal" value="<?= e($h['tanggal']) ?>">
                  <button class="btn btn-ghost btn-sm text-danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
