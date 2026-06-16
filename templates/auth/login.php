<?php
use App\Core\Response;
$base = Response::baseUrl();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - SAPA Desa Wadas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= e($base . '/assets/theme.css') ?>">
</head>
<body class="min-h-screen relative overflow-hidden">

<!-- Aceternity-inspired animated background -->
<div class="absolute inset-0 bg-radial-fade pointer-events-none"></div>
<div class="absolute inset-0 bg-grid pointer-events-none" style="mask-image: radial-gradient(ellipse 70% 60% at 50% 35%, black, transparent);"></div>
<div class="absolute -top-32 -left-24 w-96 h-96 rounded-full pointer-events-none"
     style="background: radial-gradient(circle, hsl(var(--primary)/0.25), transparent 70%); filter: blur(60px);"></div>
<div class="absolute -bottom-40 -right-24 w-[28rem] h-[28rem] rounded-full pointer-events-none"
     style="background: radial-gradient(circle, hsl(199 89% 48% / 0.18), transparent 70%); filter: blur(80px);"></div>

<div class="relative min-h-screen flex flex-col">
  <header class="px-6 py-5 flex items-center justify-between">
    <a class="flex items-center gap-2.5" href="<?= e($base . '/') ?>">
      <img src="<?= e($base . '/assets/logo-karawang.svg') ?>" alt="Logo Kabupaten Karawang" class="w-10 h-10 object-contain">
      <div class="leading-tight">
        <div class="font-semibold">SAPA Desa Wadas</div>
        <div class="text-xs text-muted">Kabupaten Karawang</div>
      </div>
    </a>
    <a href="<?= e($base . '/display') ?>" class="btn btn-ghost btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      Display Board
    </a>
  </header>

  <main class="flex-1 flex items-center justify-center px-4 pb-12">
    <div class="w-full max-w-md">
      <div class="gradient-border rounded-xl p-8 shadow-xl">
        <div class="mb-6 text-center">
          <img src="<?= e($base . '/assets/logo-karawang.svg') ?>" alt="Logo Kabupaten Karawang" class="w-20 h-20 object-contain mx-auto mb-3">
          <div class="inline-flex items-center gap-1.5 badge badge-success mb-4">
            <span class="inline-flex w-1.5 h-1.5 rounded-full" style="background: hsl(142 71% 35%);"></span>
            Sistem aktif
          </div>
          <h1 class="text-2xl font-semibold tracking-tight">Selamat datang kembali</h1>
          <p class="text-sm text-muted mt-1.5">Masuk untuk melanjutkan ke dashboard Anda</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger mb-4">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= e($base . '/login') ?>" class="space-y-4">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <div>
            <label class="label">Username</label>
            <input type="text" class="input mt-1.5" name="username" required maxlength="50" autofocus
                   placeholder="cth. admin">
          </div>
          <div>
            <div class="flex items-center justify-between">
              <label class="label">Password</label>
              <span class="help">8–72 karakter</span>
            </div>
            <input type="password" class="input mt-1.5" name="password" required minlength="8" maxlength="72"
                   placeholder="••••••••">
          </div>
          <button class="btn btn-primary w-full btn-lg">
            Masuk
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <div class="my-6 flex items-center gap-3">
          <div class="flex-1 divider"></div>
          <span class="text-[11px] text-muted uppercase tracking-wider">Akun demo</span>
          <div class="flex-1 divider"></div>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <button type="button" data-fill="admin" class="btn btn-outline btn-sm">Admin</button>
          <button type="button" data-fill="kades" class="btn btn-outline btn-sm">Kepala Desa</button>
          <button type="button" data-fill="pegawai1" class="btn btn-outline btn-sm">Pegawai</button>
        </div>
        <p class="help mt-3 text-center">Password default: <code class="px-1.5 py-0.5 rounded" style="background:hsl(var(--muted));">Password123</code></p>
      </div>

      <p class="text-center text-xs text-muted mt-6">
        © <?= date('Y') ?> Pemerintah Desa Wadas - Sistem Absensi Perangkat Desa
      </p>
    </div>
  </main>
</div>

<script>
document.querySelectorAll('[data-fill]').forEach(b => b.addEventListener('click', () => {
  document.querySelector('input[name=username]').value = b.dataset.fill;
  document.querySelector('input[name=password]').value = 'Password123';
}));
</script>
</body>
</html>
