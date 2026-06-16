<?php
use App\Core\Response;
$title = 'Riwayat';
$base = Response::baseUrl();
ob_start();
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div>
    <h2 class="text-xl font-semibold tracking-tight">Riwayat Pribadi</h2>
    <p class="text-sm text-muted mt-0.5">Riwayat absensi dan pengajuan izin hingga 12 bulan terakhir.</p>
  </div>
  <form class="flex gap-2" method="get" action="<?= e($base . '/pegawai/riwayat') ?>">
    <select class="select" style="width:120px" name="bulan">
      <?php for ($m=1;$m<=12;$m++): ?>
        <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= str_pad((string)$m,2,'0',STR_PAD_LEFT) ?></option>
      <?php endfor; ?>
    </select>
    <input type="number" class="input" style="width:100px" name="tahun" value="<?= e((string)$tahun) ?>" min="2020">
    <button class="btn btn-secondary">Filter</button>
  </form>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-warning mb-4"><?= e($error) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-5 gap-4">
  <div class="card card-soft p-6 lg:col-span-3">
    <h3 class="font-semibold">Absensi</h3>
    <?php if (empty($absen)): ?>
      <p class="text-sm text-muted mt-3">Tidak ada riwayat absensi pada periode ini.</p>
    <?php else: ?>
      <div class="mt-3 tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Tanggal</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Keterangan</th></tr></thead>
          <tbody>
            <?php foreach ($absen as $a): ?>
              <tr>
                <td class="font-medium text-sm"><?= e($a['tanggal']) ?></td>
                <td><span class="badge status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                <td class="text-muted text-sm"><?= e($a['ts_masuk'] ? substr($a['ts_masuk'], 11, 5) : '—') ?></td>
                <td class="text-muted text-sm"><?= e($a['ts_pulang'] ? substr($a['ts_pulang'], 11, 5) : '—') ?></td>
                <td class="text-sm"><?= e($a['alasan_terlambat'] ?? $a['keterangan'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <div class="card card-soft p-6 lg:col-span-2">
    <h3 class="font-semibold">Pengajuan Izin</h3>
    <?php if (empty($izin)): ?>
      <p class="text-sm text-muted mt-3">Tidak ada pengajuan pada periode ini.</p>
    <?php else: ?>
      <ul class="mt-3 space-y-2.5">
        <?php foreach ($izin as $i):
          $cls = ['Menunggu'=>'badge-warning','Disetujui'=>'badge-success','Ditolak'=>'badge-danger'][$i['status']] ?? 'badge-default';
        ?>
          <li class="card p-3 flex items-center justify-between gap-2">
            <div class="min-w-0">
              <div class="text-sm font-medium truncate"><?= e($i['jenis']) ?> · <?= e($i['tanggal_mulai']) ?> → <?= e($i['tanggal_selesai']) ?></div>
              <div class="text-xs text-muted"><code><?= e($i['nomor_referensi']) ?></code></div>
            </div>
            <span class="badge <?= $cls ?>"><?= e($i['status']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
