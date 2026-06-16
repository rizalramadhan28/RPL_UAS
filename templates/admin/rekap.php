<?php
use App\Core\Response;
$title = 'Rekap Bulanan';
$base = Response::baseUrl();
ob_start();
$baseLap = (\App\Core\Session::role() === 'KepalaDesa') ? '/kepala/laporan/print' : '/admin/laporan/print';

/** Helper: bangun URL akses foto dari path relatif yang tersimpan di DB. */
$fotoUrl = function (?string $rel) use ($base): ?string {
    if (!$rel) return null;
    $p = explode('/', $rel);
    if (count($p) < 4) return null;
    return $base . '/file/' . $p[0] . '/' . $p[1] . '/' . $p[2] . '/' . $p[3];
};
?>
<div class="flex flex-wrap items-end justify-between gap-3 mb-5">
  <div>
    <h2 class="text-xl font-semibold tracking-tight">Rekap Bulanan</h2>
    <p class="text-sm text-muted mt-0.5">Filter periode dan/atau pegawai untuk melihat detail.</p>
  </div>
  <form class="flex flex-wrap gap-2" method="get">
    <select class="select" style="width:120px" name="bulan">
      <?php for ($m=1;$m<=12;$m++): ?>
        <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= str_pad((string)$m,2,'0',STR_PAD_LEFT) ?></option>
      <?php endfor; ?>
    </select>
    <input type="number" class="input" style="width:100px" name="tahun" value="<?= e((string)$tahun) ?>" min="2020">
    <select class="select" style="width:200px" name="pegawai_id">
      <option value="0">Semua Pegawai</option>
      <?php foreach ($pegawai_list as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $pegawai_id ? 'selected' : '' ?>><?= e($p['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary">Tampilkan</button>
    <a class="btn btn-primary" target="_blank"
       href="<?= e($base . $baseLap . '?bulan=' . $bulan . '&tahun=' . $tahun . '&pegawai_id=' . $pegawai_id) ?>">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Cetak / PDF
    </a>
  </form>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-warning"><?= e($error) ?></div>
<?php elseif (empty($data['rows'])): ?>
  <div class="alert alert-info">Tidak ada data pada periode ini.</div>
<?php else: ?>
  <?php if ($pegawai_id > 0): ?>
    <div class="card card-soft p-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold">Detail Harian</h3>
          <p class="text-sm text-muted"><?= e($data['pegawai']['nama']) ?> · <?= e($data['pegawai']['jabatan']) ?></p>
        </div>
        <span class="badge badge-default">HK <?= (int)$data['total_hari_kerja'] ?></span>
      </div>
      <div class="mt-4 tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Tanggal</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Keterangan</th><th>Foto</th></tr></thead>
          <tbody>
            <?php foreach ($data['rows'] as $r):
              $urlIn = $fotoUrl($r['swafoto_masuk'] ?? null);
              $urlOut = $fotoUrl($r['swafoto_pulang'] ?? null);
              $modalId = 'foto_' . (int)$r['id'];
            ?>
              <tr>
                <td class="text-sm font-medium"><?= e($r['tanggal']) ?></td>
                <td><span class="badge status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td class="text-muted text-sm">
                  <?php if ($r['ts_masuk']): ?>
                    <?= e(substr($r['ts_masuk'],11,5)) ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-muted text-sm">
                  <?php if ($r['ts_pulang']): ?>
                    <?= e(substr($r['ts_pulang'],11,5)) ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-sm"><?= e($r['alasan_terlambat'] ?? $r['keterangan'] ?? '—') ?></td>
                <td>
                  <?php if ($urlIn || $urlOut): ?>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('<?= $modalId ?>').classList.remove('hidden')">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      Lihat
                    </button>
                    <div id="<?= $modalId ?>" class="dialog-bg hidden">
                      <div class="dialog" style="max-width: 36rem;">
                        <div class="flex items-center justify-between mb-3">
                          <h3 class="font-semibold">Foto Absensi · <?= e($r['tanggal']) ?></h3>
                          <button class="btn btn-ghost btn-sm" onclick="document.getElementById('<?= $modalId ?>').classList.add('hidden')">✕</button>
                        </div>
                        <div class="grid <?= ($urlIn && $urlOut) ? 'grid-cols-2' : 'grid-cols-1' ?> gap-3">
                          <?php if ($urlIn): ?>
                            <div>
                              <div class="text-xs text-muted mb-1">Masuk · <?= e(substr((string)$r['ts_masuk'],11,5)) ?></div>
                              <img src="<?= e($urlIn) ?>" alt="foto masuk" class="w-full rounded-md border" style="border-color: hsl(var(--border));">
                            </div>
                          <?php endif; ?>
                          <?php if ($urlOut): ?>
                            <div>
                              <div class="text-xs text-muted mb-1">Pulang · <?= e(substr((string)$r['ts_pulang'],11,5)) ?></div>
                              <img src="<?= e($urlOut) ?>" alt="foto pulang" class="w-full rounded-md border" style="border-color: hsl(var(--border));">
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php else: ?>
    <div class="card card-soft p-6">
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted">Periode <?= str_pad((string)$bulan,2,'0',STR_PAD_LEFT) ?>/<?= $tahun ?></p>
        <span class="badge badge-default">Total Hari Kerja: <?= (int)$data['total_hari_kerja'] ?></span>
      </div>
      <div class="mt-4 tbl-wrap">
        <table class="tbl">
          <thead><tr>
            <th>Nama</th><th>Jabatan</th>
            <th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>HK</th>
          </tr></thead>
          <tbody>
            <?php foreach ($data['rows'] as $r): ?>
              <tr>
                <td class="font-medium"><?= e($r['nama']) ?></td>
                <td class="text-muted text-sm"><?= e($r['jabatan']) ?></td>
                <td class="tabular-nums text-sm"><?= (int)$r['Hadir'] ?></td>
                <td class="tabular-nums text-sm"><?= (int)$r['Terlambat'] ?></td>
                <td class="tabular-nums text-sm"><?= (int)$r['Izin'] ?></td>
                <td class="tabular-nums text-sm"><?= (int)$r['Sakit'] ?></td>
                <td class="tabular-nums text-sm"><?= (int)$r['Alpha'] ?></td>
                <td class="tabular-nums text-sm font-medium"><?= (int)$data['total_hari_kerja'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
