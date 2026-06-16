<?php $title = '403 Akses Ditolak'; ob_start(); ?>
<div class="max-w-md mx-auto text-center mt-16">
  <div class="inline-flex w-16 h-16 rounded-full items-center justify-center mb-4" style="background: hsl(0 84% 60% / 0.12); color: hsl(0 84% 50%);">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
  </div>
  <h1 class="text-3xl font-semibold tracking-tight">Akses Ditolak</h1>
  <p class="text-muted mt-2">Anda tidak memiliki hak untuk mengakses halaman ini.</p>
  <a class="btn btn-primary mt-6" href="<?= e(\App\Core\Response::baseUrl() . '/') ?>">Kembali ke beranda</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
