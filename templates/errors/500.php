<?php $title = 'Kesalahan Server'; ob_start(); ?>
<div class="max-w-md mx-auto text-center mt-16">
  <h1 class="text-6xl font-semibold tracking-tight">500</h1>
  <p class="text-muted mt-2"><?= e($message ?? 'Terjadi kesalahan pada server.') ?></p>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
