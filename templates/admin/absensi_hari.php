<?php
use App\Core\Response;
$title = 'Daftar Absensi';
$base = Response::baseUrl();
ob_start();

$fotoUrl = function (?string $rel) use ($base): ?string {
    if (!$rel) return null;
    $p = explode('/', $rel);
    if (count($p) < 4) return null;
    return $base . '/file/' . $p[0] . '/' . $p[1] . '/' . $p[2] . '/' . $p[3];
};
?>
<div class="flex flex-wrap items-end justify-between gap-3 mb-5">
  <div>
    <h2 class="text-xl font-semibold tracking-tight">Daftar Absensi</h2>
    <p class="text-sm text-muted mt-0.5">Lihat catatan masuk/pulang seluruh pegawai pada tanggal terpilih, termasuk swafoto.</p>
  </div>
  <form method="get" class="flex gap-2">
    <input type="date" name="tanggal" class="input" value="<?= e($tanggal) ?>">
    <button class="btn btn-secondary">Tampilkan</button>
  </form>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr>
      <th>Pegawai</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Foto Masuk</th><th>Foto Pulang</th><th>Keterangan</th>
    </tr></thead>
    <tbody>
    <?php if (empty($rows) && empty($belum_absen)): ?>
      <tr><td colspan="7" class="text-center text-muted py-8">Tidak ada data pada tanggal ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r):
      $urlIn = $fotoUrl($r['swafoto_masuk'] ?? null);
      $urlOut = $fotoUrl($r['swafoto_pulang'] ?? null);
      $modalIn = 'fin_' . (int)$r['id'];
      $modalOut = 'fout_' . (int)$r['id'];
      $parts = preg_split('/\s+/', trim((string)$r['pegawai_nama']));
      $init = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    ?>
      <tr>
        <td>
          <div class="flex items-center gap-2.5">
            <div class="avatar"><?= e($init) ?></div>
            <div class="min-w-0">
              <div class="text-sm font-medium truncate"><?= e($r['pegawai_nama']) ?></div>
              <div class="text-xs text-muted truncate"><?= e($r['jabatan']) ?></div>
            </div>
          </div>
        </td>
        <td><span class="badge status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td class="text-sm tabular-nums"><?= e($r['ts_masuk'] ? substr((string)$r['ts_masuk'], 11, 5) : '—') ?></td>
        <td class="text-sm tabular-nums"><?= e($r['ts_pulang'] ? substr((string)$r['ts_pulang'], 11, 5) : '—') ?></td>
        <td>
          <?php if ($urlIn): ?>
            <button type="button" class="p-0 border-0 bg-transparent" onclick="document.getElementById('<?= $modalIn ?>').classList.remove('hidden')">
              <img src="<?= e($urlIn) ?>" alt="thumb in" class="rounded-md border" style="width:48px;height:48px;object-fit:cover;border-color:hsl(var(--border));cursor:pointer;">
            </button>
            <div id="<?= $modalIn ?>" class="dialog-bg hidden">
              <div class="dialog" style="max-width: 30rem;">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="font-semibold">Foto Masuk · <?= e($r['pegawai_nama']) ?></h3>
                  <button class="btn btn-ghost btn-sm" onclick="document.getElementById('<?= $modalIn ?>').classList.add('hidden')">✕</button>
                </div>
                <div class="text-xs text-muted mb-2">
                  <?= e($r['ts_masuk']) ?> · GPS <?= e((string)$r['lat_masuk']) ?>, <?= e((string)$r['lon_masuk']) ?>
                </div>
                <img src="<?= e($urlIn) ?>" alt="foto masuk" class="w-full rounded-md border" style="border-color: hsl(var(--border));">
              </div>
            </div>
          <?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
        </td>
        <td>
          <?php if ($urlOut): ?>
            <button type="button" class="p-0 border-0 bg-transparent" onclick="document.getElementById('<?= $modalOut ?>').classList.remove('hidden')">
              <img src="<?= e($urlOut) ?>" alt="thumb out" class="rounded-md border" style="width:48px;height:48px;object-fit:cover;border-color:hsl(var(--border));cursor:pointer;">
            </button>
            <div id="<?= $modalOut ?>" class="dialog-bg hidden">
              <div class="dialog" style="max-width: 30rem;">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="font-semibold">Foto Pulang · <?= e($r['pegawai_nama']) ?></h3>
                  <button class="btn btn-ghost btn-sm" onclick="document.getElementById('<?= $modalOut ?>').classList.add('hidden')">✕</button>
                </div>
                <div class="text-xs text-muted mb-2">
                  <?= e($r['ts_pulang']) ?> · GPS <?= e((string)$r['lat_pulang']) ?>, <?= e((string)$r['lon_pulang']) ?>
                </div>
                <img src="<?= e($urlOut) ?>" alt="foto pulang" class="w-full rounded-md border" style="border-color: hsl(var(--border));">
              </div>
            </div>
          <?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
        </td>
        <td class="text-sm"><?= e($r['alasan_terlambat'] ?? $r['keterangan'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($belum_absen as $p):
      $parts = preg_split('/\s+/', trim((string)$p['nama']));
      $init = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    ?>
      <tr>
        <td>
          <div class="flex items-center gap-2.5">
            <div class="avatar"><?= e($init) ?></div>
            <div class="min-w-0">
              <div class="text-sm font-medium truncate"><?= e($p['nama']) ?></div>
              <div class="text-xs text-muted truncate"><?= e($p['jabatan']) ?></div>
            </div>
          </div>
        </td>
        <td><span class="badge status-BelumAbsen">Belum Absen</span></td>
        <td colspan="5" class="text-sm text-muted">—</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
