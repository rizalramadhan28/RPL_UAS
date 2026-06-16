<?php
use App\Core\Response;
$title = 'Manajemen Pegawai';
$base = Response::baseUrl();
ob_start();
?>
<div class="flex items-center justify-between mb-5">
  <div>
    <h2 class="text-xl font-semibold tracking-tight">Manajemen Pegawai</h2>
    <p class="text-sm text-muted mt-0.5">Tambah, ubah, atau nonaktifkan akun perangkat desa.</p>
  </div>
  <a class="btn btn-primary" href="<?= e($base . '/admin/pegawai/baru') ?>">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Pegawai
  </a>
</div>
<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Username</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-8">Belum ada data pegawai.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><code class="text-xs"><?= e($r['nip']) ?></code></td>
          <td>
            <div class="flex items-center gap-2.5">
              <div class="avatar"><?php
                $parts = preg_split('/\s+/', trim($r['nama']));
                echo strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
              ?></div>
              <span class="font-medium"><?= e($r['nama']) ?></span>
            </div>
          </td>
          <td class="text-muted"><?= e($r['jabatan']) ?></td>
          <td><code class="text-xs"><?= e($r['username']) ?></code></td>
          <td><span class="badge <?= $r['status']==='Aktif' ? 'badge-success' : 'badge-muted' ?>"><?= e($r['status']) ?></span></td>
          <td>
            <div class="flex gap-1">
              <a class="btn btn-ghost btn-sm" href="<?= e($base . '/admin/pegawai/' . $r['id'] . '/ubah') ?>">Ubah</a>
              <?php if ($r['status'] === 'Aktif'): ?>
                <form method="post" action="<?= e($base . '/admin/pegawai/' . $r['id'] . '/nonaktifkan') ?>" onsubmit="return confirm('Nonaktifkan pegawai ini?')">
                  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                  <button class="btn btn-ghost btn-sm text-danger">Nonaktifkan</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
