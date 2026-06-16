<?php $title = '404'; ob_start(); ?>
<div class="max-w-md mx-auto text-center mt-16">
  <h1 class="text-6xl font-semibold tracking-tight">404</h1>
  <p class="text-muted mt-2">Halaman yang Anda cari tidak ditemukan.</p>
  <a class="btn btn-primary mt-6" href="<?= e(\App\Core\Response::baseUrl() . '/') ?>">Kembali ke beranda</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
