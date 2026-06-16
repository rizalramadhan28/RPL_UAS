<?php $title = 'Sesi Kedaluwarsa'; ob_start(); ?>
<div class="max-w-md mx-auto text-center mt-16">
  <h1 class="text-3xl font-semibold tracking-tight">Sesi keamanan kedaluwarsa</h1>
  <p class="text-muted mt-2">Silakan muat ulang halaman dan ulangi tindakan Anda.</p>
  <a class="btn btn-primary mt-6" href="<?= e(\App\Core\Response::baseUrl() . '/') ?>">Beranda</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
