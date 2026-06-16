<?php
use App\Core\Response;
$title = 'Pengajuan Izin Menunggu';
$base = Response::baseUrl();
// Pilih prefix URL approve/reject sesuai peran user yang sedang login
$role = \App\Core\Session::role();
$urlPrefix = ($role === 'KepalaDesa') ? '/kepala/izin' : '/admin/izin';
ob_start();
?>
<div class="mb-5">
  <h2 class="text-xl font-semibold tracking-tight">Pengajuan Izin / Sakit</h2>
  <p class="text-sm text-muted mt-0.5">Daftar pengajuan menunggu persetujuan, terurut dari yang paling lama.</p>
</div>

<?php if (empty($data['items'])): ?>
  <div class="alert alert-info">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    <div>Tidak ada pengajuan menunggu persetujuan.</div>
  </div>
<?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>Diajukan</th><th>Nomor</th><th>Pegawai</th><th>Jenis</th>
        <th>Periode</th><th>Keterangan</th><th>Lampiran</th><th>Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach ($data['items'] as $r):
        $parts = preg_split('/\s+/', trim($r['pegawai_nama']));
        $init = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
      ?>
        <tr>
          <td class="text-muted text-sm whitespace-nowrap"><?= e(substr($r['created_at'],0,16)) ?></td>
          <td><code class="text-xs"><?= e($r['nomor_referensi']) ?></code></td>
          <td>
            <div class="flex items-center gap-2.5">
              <div class="avatar"><?= e($init) ?></div>
              <div class="min-w-0">
                <div class="text-sm font-medium truncate"><?= e($r['pegawai_nama']) ?></div>
                <div class="text-xs text-muted truncate"><?= e($r['jabatan']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge <?= $r['jenis'] === 'Sakit' ? 'badge-purple' : 'badge-info' ?>"><?= e($r['jenis']) ?></span></td>
          <td class="text-sm whitespace-nowrap"><?= e($r['tanggal_mulai']) ?> <span class="text-muted">→</span> <?= e($r['tanggal_selesai']) ?></td>
          <td class="text-sm max-w-xs truncate"><?= e(mb_strimwidth($r['keterangan'], 0, 80, '...')) ?></td>
          <td>
            <?php if (!empty($r['lampiran_path'])): $p = explode('/', $r['lampiran_path']); ?>
              <a class="btn btn-ghost btn-sm" target="_blank" href="<?= e($base . '/file/izin/' . $p[1] . '/' . $p[2] . '/' . $p[3]) ?>">Lihat</a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <div class="flex gap-1">
              <form method="post" class="inline" action="<?= e($base . $urlPrefix . '/' . (int)$r['id'] . '/setujui') ?>" onsubmit="return confirm('Setujui pengajuan ini?')">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button class="btn btn-primary btn-sm">Setujui</button>
              </form>
              <button class="btn btn-outline btn-sm text-danger" onclick="document.getElementById('tolak<?= (int)$r['id'] ?>').classList.remove('hidden')">Tolak</button>
            </div>

            <div id="tolak<?= (int)$r['id'] ?>" class="dialog-bg hidden">
              <form method="post" action="<?= e($base . $urlPrefix . '/' . (int)$r['id'] . '/tolak') ?>" class="dialog">
                <h3 class="font-semibold">Tolak Pengajuan</h3>
                <p class="text-sm text-muted mt-1">Tuliskan alasan penolakan (3–500 karakter).</p>
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <textarea class="textarea mt-3" name="alasan" minlength="3" maxlength="500" rows="3" required></textarea>
                <div class="mt-4 flex justify-end gap-2">
                  <button type="button" class="btn btn-ghost" onclick="document.getElementById('tolak<?= (int)$r['id'] ?>').classList.add('hidden')">Batal</button>
                  <button class="btn btn-destructive">Tolak</button>
                </div>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($data['pages'] > 1): ?>
    <div class="mt-4 flex gap-1">
      <?php for ($p=1; $p <= $data['pages']; $p++): ?>
        <a class="btn <?= $p === $data['page'] ? 'btn-primary' : 'btn-outline' ?> btn-sm" href="?page=<?= $p ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
