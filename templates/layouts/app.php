<?php
use App\Core\Response;
use App\Core\Session;
$user = Session::user();
$role = $user['role'] ?? null;
$base = Response::baseUrl();

$path = $_SERVER['REQUEST_URI'] ?? '/';
$activeIs = function (string $needle) use ($path): string {
    return str_starts_with(strtok($path, '?'), $needle) ? 'active' : '';
};

$initials = '';
if (!empty($user['nama'])) {
    $parts = preg_split('/\s+/', trim($user['nama']));
    $initials = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'SAPA Desa Wadas') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    container: { center: true, padding: '1rem' },
    extend: {
      fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] }
    }
  }
};
</script>
<link rel="stylesheet" href="<?= e($base . '/assets/theme.css') ?>">
</head>
<body>

<?php
// Define menu by role
$menus = [];
if ($role === 'Pegawai') {
  $menus = [
    ['url' => $base . '/pegawai',          'label' => 'Beranda',     'match' => $base . '/pegawai',          'icon' => 'home'],
    ['url' => $base . '/pegawai/absen',    'label' => 'Absensi',     'match' => $base . '/pegawai/absen',    'icon' => 'camera'],
    ['url' => $base . '/pegawai/izin',     'label' => 'Izin/Sakit',  'match' => $base . '/pegawai/izin',     'icon' => 'note'],
    ['url' => $base . '/pegawai/kegiatan', 'label' => 'Kegiatan',    'match' => $base . '/pegawai/kegiatan', 'icon' => 'list'],
    ['url' => $base . '/pegawai/riwayat',  'label' => 'Riwayat',     'match' => $base . '/pegawai/riwayat',  'icon' => 'history'],
  ];
} elseif ($role === 'Admin') {
  $menus = [
    ['url' => $base . '/admin',           'label' => 'Dashboard',  'match' => $base . '/admin',           'icon' => 'dashboard', 'exact' => true],
    ['url' => $base . '/admin/absensi',   'label' => 'Absensi',    'match' => $base . '/admin/absensi',   'icon' => 'camera'],
    ['url' => $base . '/admin/pegawai',   'label' => 'Pegawai',    'match' => $base . '/admin/pegawai',   'icon' => 'users'],
    ['url' => $base . '/admin/izin',      'label' => 'Pengajuan Izin', 'match' => $base . '/admin/izin',  'icon' => 'note'],
    ['url' => $base . '/admin/rekap',     'label' => 'Rekap',      'match' => $base . '/admin/rekap',     'icon' => 'chart'],
    ['url' => $base . '/admin/hari-kerja','label' => 'Hari Kerja', 'match' => $base . '/admin/hari-kerja','icon' => 'calendar'],
    ['url' => $base . '/admin/pengaturan','label' => 'Pengaturan', 'match' => $base . '/admin/pengaturan','icon' => 'settings'],
  ];
} elseif ($role === 'KepalaDesa') {
  $menus = [
    ['url' => $base . '/kepala',         'label' => 'Dashboard',      'match' => $base . '/kepala',         'icon' => 'dashboard', 'exact' => true],
    ['url' => $base . '/kepala/absensi', 'label' => 'Absensi',        'match' => $base . '/kepala/absensi', 'icon' => 'camera'],
    ['url' => $base . '/kepala/izin',    'label' => 'Pengajuan Izin', 'match' => $base . '/kepala/izin',    'icon' => 'note'],
    ['url' => $base . '/kepala/hari-kerja','label' => 'Hari Kerja',   'match' => $base . '/kepala/hari-kerja','icon' => 'calendar'],
    ['url' => $base . '/kepala/laporan', 'label' => 'Laporan',        'match' => $base . '/kepala/laporan', 'icon' => 'chart'],
  ];
}

$icons = [
  'home'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4M9 22V12h6v10"/></svg>',
  'camera'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>',
  'note'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
  'list'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
  'history'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 109-9 9.74 9.74 0 00-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/><polyline points="12 7 12 12 15 15"/></svg>',
  'dashboard' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
  'users'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
  'chart'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
  'calendar'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  'settings'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
  'logout'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
];
?>

<div class="min-h-screen flex">
  <!-- Sidebar -->
  <aside class="hidden lg:flex flex-col w-64 shrink-0 border-r" style="background: hsl(var(--card));">
    <div class="px-5 py-5 border-b">
      <a href="<?= e($base . '/') ?>" class="flex items-center gap-2.5">
        <img src="<?= e($base . '/assets/logo-karawang.svg') ?>" alt="Logo Kabupaten Karawang" class="w-9 h-9 object-contain shrink-0">
        <div class="leading-tight">
          <div class="font-semibold text-sm">SAPA Desa Wadas</div>
          <div class="text-[11px] text-muted">Kabupaten Karawang</div>
        </div>
      </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">
      <div class="px-2 pb-2 text-[11px] font-medium text-muted uppercase tracking-wider">Menu</div>
      <?php foreach ($menus as $m):
        $isActive = !empty($m['exact'])
          ? rtrim(strtok($path, '?'), '/') === rtrim($m['match'], '/')
          : str_starts_with(strtok($path, '?'), $m['match']);
      ?>
        <a href="<?= e($m['url']) ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
          <?= $icons[$m['icon']] ?? '' ?>
          <span><?= e($m['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <!-- Main column -->
  <div class="flex-1 flex flex-col min-w-0">
    <!-- Top bar (mobile + actions) -->
    <header class="h-14 border-b flex items-center justify-between gap-3 px-4 lg:px-6" style="background: hsl(var(--card));">
      <div class="flex items-center gap-3 lg:hidden">
        <button id="sidebarToggle" class="btn btn-ghost btn-sm">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <img src="<?= e($base . '/assets/logo-karawang.svg') ?>" alt="Logo" class="w-6 h-6 object-contain">
        <span class="font-semibold">SAPA Desa Wadas</span>
      </div>
      <div class="hidden lg:block">
        <h1 class="text-sm font-medium text-muted"><?= e($title ?? '') ?></h1>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-muted hidden sm:block"><?= e(date('D, d M Y H:i')) ?></span>
        <?php if ($user): ?>
          <div class="hidden sm:flex items-center gap-2 px-2 py-1 rounded-md border" style="border-color: hsl(var(--border));">
            <div class="avatar" style="width:1.5rem;height:1.5rem;font-size:.7rem;background: linear-gradient(135deg, hsl(var(--primary)/0.2), hsl(199 89% 48%/0.2));"><?= e($initials ?: '?') ?></div>
            <div class="text-xs leading-tight">
              <div class="font-medium"><?= e($user['nama']) ?></div>
              <div class="text-muted text-[11px]"><?= e($user['role']) ?></div>
            </div>
          </div>
          <a href="<?= e($base . '/logout') ?>" class="btn btn-destructive btn-sm" title="Keluar">
            <?= $icons['logout'] ?>
            <span class="hidden sm:inline">Keluar</span>
          </a>
        <?php endif; ?>
      </div>
    </header>

    <main class="flex-1 px-4 lg:px-6 py-6">
      <?php $flashSuccess = Session::flash('success'); ?>
      <?php $flashError = Session::flash('error'); ?>
      <?php if (!empty($flashSuccess) || !empty($success ?? null)): ?>
        <div class="alert alert-success mb-4">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <div><?= e($flashSuccess ?: ($success ?? '')) ?></div>
        </div>
      <?php endif; ?>
      <?php if (!empty($flashError) || !empty($error ?? null)): ?>
        <div class="alert alert-danger mb-4">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div><?= e($flashError ?: ($error ?? '')) ?></div>
        </div>
      <?php endif; ?>

      <?= $content ?? '' ?>
    </main>
  </div>
</div>

<!-- Mobile sidebar drawer -->
<div id="mobileSidebar" class="fixed inset-0 z-40 hidden lg:hidden">
  <div class="absolute inset-0 bg-black/40" data-close></div>
  <aside class="absolute top-0 left-0 bottom-0 w-72 p-4 overflow-auto" style="background: hsl(var(--card)); border-right:1px solid hsl(var(--border));">
    <div class="flex items-center justify-between mb-4">
      <span class="font-semibold">Menu</span>
      <button class="btn btn-ghost btn-sm" data-close>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <nav class="space-y-1">
      <?php foreach ($menus as $m):
        $isActive = !empty($m['exact'])
          ? rtrim(strtok($path, '?'), '/') === rtrim($m['match'], '/')
          : str_starts_with(strtok($path, '?'), $m['match']);
      ?>
        <a href="<?= e($m['url']) ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
          <?= $icons[$m['icon']] ?? '' ?>
          <span><?= e($m['label']) ?></span>
        </a>
      <?php endforeach; ?>
      <div class="divider my-3"></div>
      <?php if ($user): ?>
        <a href="<?= e($base . '/logout') ?>" class="sidebar-link"><?= $icons['logout'] ?> <span>Keluar</span></a>
      <?php endif; ?>
    </nav>
  </aside>
</div>

<script>
(function () {
  const tg = document.getElementById('sidebarToggle');
  const ms = document.getElementById('mobileSidebar');
  if (tg && ms) {
    tg.addEventListener('click', () => ms.classList.remove('hidden'));
    ms.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => ms.classList.add('hidden')));
  }
})();
</script>
</body>
</html>
