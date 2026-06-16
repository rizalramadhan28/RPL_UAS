<?php
use App\Core\Response;
$base = Response::baseUrl();
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Display Board · <?= e($cfg['nama_desa'] ?? 'Desa Wadas') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root { color-scheme: dark; }
  html, body { background: #050816; color: #e6edf7; font-family: 'Inter', sans-serif; }
  .grid-bg {
    background-image:
      linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
      linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 48px 48px;
  }
  .glow-1 { background: radial-gradient(ellipse 60% 40% at 30% 0%, rgba(34,197,94,0.18), transparent 60%); }
  .glow-2 { background: radial-gradient(ellipse 50% 40% at 80% 100%, rgba(56,189,248,0.18), transparent 60%); }
  .glass { background: rgba(15,23,42,0.55); border:1px solid rgba(255,255,255,0.08); backdrop-filter: blur(12px); }
  .pill { display:inline-flex; align-items:center; gap:6px; padding: 4px 12px; border-radius:9999px; font-size:14px; }
  .h-Hadir     { color:#5fe07b; background: rgba(95,224,123,0.10); border:1px solid rgba(95,224,123,0.25); }
  .h-Terlambat { color:#ffb24d; background: rgba(255,178,77,0.10); border:1px solid rgba(255,178,77,0.25); }
  .h-Izin      { color:#67e8f9; background: rgba(103,232,249,0.10); border:1px solid rgba(103,232,249,0.25); }
  .h-Sakit     { color:#c4b5fd; background: rgba(196,181,253,0.10); border:1px solid rgba(196,181,253,0.25); }
  .h-Alpha     { color:#fb7185; background: rgba(251,113,133,0.10); border:1px solid rgba(251,113,133,0.25); }
  .name-row { padding: 10px 12px; border-bottom: 1px dashed rgba(255,255,255,0.08); display:flex; align-items:center; gap:10px; }
  .name-row:last-child { border-bottom: none; }
  .avatar { width:36px; height:36px; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-weight:600; font-size:14px; flex-shrink:0; }
  .ring-pulse { position: relative; }
  .ring-pulse::after {
    content: ''; position: absolute; inset:-3px; border-radius:9999px;
    box-shadow: 0 0 0 0 rgba(95,224,123,0.6);
    animation: pulse 2s ease-out infinite;
  }
  @keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(95,224,123,0.55); }
    100% { box-shadow: 0 0 0 14px rgba(95,224,123,0); }
  }
  .indicator { padding: 6px 14px; border-radius:9999px; font-size:14px; }
  .indicator.ok { background: rgba(34,197,94,0.15); border:1px solid rgba(34,197,94,0.30); color:#86efac; }
  .indicator.fail { background: rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.30); color:#fca5a5; }
  /* Animated bottom beam (Aceternity-ish) */
  @keyframes shine { from { background-position: 200% 0; } to { background-position: -200% 0; } }
  .shine {
    background: linear-gradient(90deg, transparent, rgba(56,189,248,0.5), rgba(34,197,94,0.5), transparent);
    background-size: 200% 100%;
    animation: shine 6s linear infinite;
  }
</style>
</head>
<body class="min-h-screen relative overflow-x-hidden">

<div class="fixed inset-0 grid-bg pointer-events-none"></div>
<div class="fixed inset-0 glow-1 pointer-events-none"></div>
<div class="fixed inset-0 glow-2 pointer-events-none"></div>

<header class="relative px-8 py-6 flex items-center justify-between gap-4 border-b border-white/5">
  <div class="flex items-center gap-3">
    <div class="ring-pulse">
      <span class="inline-flex w-14 h-14 rounded-xl items-center justify-center bg-white/95 p-1.5">
        <img src="<?= e($base . '/assets/logo-karawang.svg') ?>" alt="Logo Kabupaten Karawang" class="w-full h-full object-contain">
      </span>
    </div>
    <div>
      <div class="text-xs uppercase tracking-[0.18em] text-white/60">Papan Kehadiran · Kabupaten Karawang</div>
      <div class="text-2xl md:text-3xl font-semibold"><?= e($cfg['nama_desa'] ?? 'Desa Wadas') ?></div>
    </div>
  </div>
  <div class="text-right">
    <div class="text-xs uppercase tracking-[0.18em] text-white/60">Persentase Kehadiran</div>
    <div id="persen" class="text-4xl md:text-5xl font-semibold tabular-nums">--%</div>
    <div id="meta" class="text-sm text-white/60 mt-1"><?= e($now) ?> WIB</div>
  </div>
</header>

<main class="relative px-6 md:px-8 py-6">
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
    <?php foreach (['Hadir','Terlambat','Izin','Sakit','Alpha'] as $k): ?>
      <section class="glass rounded-2xl p-5 min-h-[60vh] flex flex-col">
        <div class="flex items-center justify-between mb-4">
          <span class="pill h-<?= $k ?>"><?= $k ?></span>
          <span id="cnt-<?= $k ?>" class="text-3xl font-semibold tabular-nums">0</span>
        </div>
        <div id="list-<?= $k ?>" class="overflow-auto pr-1 flex-1"></div>
      </section>
    <?php endforeach; ?>
  </div>
</main>

<footer class="relative px-8 py-4 flex items-center justify-between border-t border-white/5 mt-2">
  <div class="text-sm text-white/60">Auto-refresh setiap 60 detik</div>
  <div id="status" class="indicator ok">● Terhubung</div>
</footer>
<div class="shine h-[2px] w-full"></div>

<script>
async function refresh() {
  try {
    const res = await fetch('<?= e($base . '/display/data') ?>', { cache: 'no-store' });
    if (!res.ok) throw new Error('http ' + res.status);
    const j = await res.json();
    document.getElementById('persen').textContent = j.persen.toFixed(2) + '%';
    document.getElementById('meta').textContent = new Date(j.updated_at).toLocaleString('id-ID') + ' WIB';
    ['Hadir','Terlambat','Izin','Sakit','Alpha'].forEach(k => {
      const list = j.kategori[k] || [];
      document.getElementById('cnt-' + k).textContent = list.length;
      const el = document.getElementById('list-' + k);
      el.innerHTML = list.length === 0
        ? '<div class="text-white/40 text-sm py-2 italic">Tidak ada</div>'
        : list.map(p => `
            <div class="name-row">
              <div class="avatar" style="background: rgba(255,255,255,0.06); color:#cbd5e1;">${initials(p.nama)}</div>
              <div class="min-w-0">
                <div class="text-base md:text-lg truncate">${escapeHtml(p.nama)}</div>
                <div class="text-xs md:text-sm text-white/50 truncate">${escapeHtml(p.jabatan)}</div>
              </div>
            </div>`).join('');
    });
    setStatus(true);
  } catch (e) {
    setStatus(false, e.message);
  }
}
function setStatus(ok, msg) {
  const el = document.getElementById('status');
  if (ok) { el.textContent = '● Terhubung'; el.className = 'indicator ok'; }
  else { el.textContent = '● Koneksi terputus' + (msg ? ' (' + msg + ')' : ''); el.className = 'indicator fail'; }
}
function initials(s) {
  const parts = String(s||'').trim().split(/\s+/);
  return ((parts[0]||'?').slice(0,1) + (parts[1]||'').slice(0,1)).toUpperCase();
}
function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
refresh();
setInterval(refresh, 60000);
</script>
</body></html>
