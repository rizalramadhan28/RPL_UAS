<?php $title = 'Konfirmasi Keluar'; ob_start(); ?>
<div class="max-w-md mx-auto">
  <div class="card card-soft p-6">
    <h2 class="text-lg font-semibold">Konfirmasi Logout</h2>
    <p class="text-sm text-muted mt-1.5">Anda akan keluar dari sesi saat ini. Yakin?</p>
    <form method="post" action="<?= e(\App\Core\Response::baseUrl() . '/logout') ?>" class="mt-5 flex gap-2">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <button class="btn btn-destructive">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Ya, keluar
      </button>
      <a class="btn btn-secondary" href="<?= e(\App\Core\Response::baseUrl() . '/') ?>">Batal</a>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
