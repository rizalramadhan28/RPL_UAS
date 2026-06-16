<?php
use App\Core\Response;
$title = 'Pengajuan Izin/Sakit';
$base = Response::baseUrl();
ob_start();
?>
<div class="flex items-center justify-between mb-5">
  <div>
    <h2 class="text-xl font-semibold tracking-tight">Pengajuan Izin / Sakit</h2>
    <p class="text-sm text-muted mt-0.5">Riwayat pengajuan beserta status keputusan.</p>
  </div>
  <a class="btn btn-primary" href="<?= e($base . '/pegawai/izin/baru') ?>">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Ajukan Baru
  </a>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr><th>Nomor</th><th>Jenis</th><th>Periode</th><th>Status</th><th>Keputusan</th><th>Lampiran</th></tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-8">Belum ada pengajuan.</td></tr>
      <?php else: foreach ($rows as $r):
        $cls = ['Menunggu'=>'badge-warning','Disetujui'=>'badge-success','Ditolak'=>'badge-danger'][$r['status']] ?? 'badge-default';
      ?>
        <tr>
          <td><code class="text-xs"><?= e($r['nomor_referensi']) ?></code></td>
          <td><?= e($r['jenis']) ?></td>
          <td class="text-sm"><?= e($r['tanggal_mulai']) ?> <span class="text-muted">→</span> <?= e($r['tanggal_selesai']) ?></td>
          <td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td>
          <td class="text-sm text-muted"><?= e($r['decided_at'] ?? '—') ?></td>
          <td>
            <?php if (!empty($r['lampiran_path'])): $p = explode('/', $r['lampiran_path']); ?>
              <a class="btn btn-ghost btn-sm" target="_blank" href="<?= e($base . '/file/izin/' . $p[1] . '/' . $p[2] . '/' . $p[3]) ?>">Lihat</a>
            <?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
