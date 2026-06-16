<?php
use App\Core\Response;
$title = 'Absensi';
$base = Response::baseUrl();
ob_start();
$cfg = $status['pengaturan'];
$isLateWindow = ($status['window'] === 'terlambat') && !$status['has_masuk'];
$action = $status['can_pulang'] ? '/pegawai/absen/pulang' : '/pegawai/absen/masuk';
$mode = $status['can_pulang'] ? 'Pulang' : 'Masuk';
?>
<div class="grid lg:grid-cols-5 gap-4">
  <div class="card card-soft p-6 lg:col-span-3">
    <div class="flex items-center justify-between gap-2 mb-4">
      <div>
        <h2 class="text-lg font-semibold">Absensi <?= e($mode) ?></h2>
        <p class="text-xs text-muted mt-0.5">Validasi swafoto + GPS · radius <?= (int)$cfg['radius_meter'] ?> m dari kantor</p>
      </div>
      <span class="badge <?= $status['can_pulang'] ? 'badge-info' : 'badge-success' ?>"><?= e($mode) ?></span>
    </div>

    <div id="alertBox" class="mb-3"></div>

    <div class="rounded-xl overflow-hidden border" style="border-color: hsl(var(--border)); background: #000;">
      <video id="cam" autoplay playsinline muted class="w-full block" style="aspect-ratio: 4/3; object-fit: cover;"></video>
      <canvas id="canvas" hidden></canvas>
      <img id="preview" class="w-full hidden" style="aspect-ratio: 4/3; object-fit: cover;" alt="Swafoto">
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
      <button id="btnCapture" class="btn btn-primary" type="button">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Ambil Swafoto
      </button>
      <button id="btnRetake" class="btn btn-secondary hidden" type="button">Ulangi</button>
    </div>

    <div class="mt-4 card p-3 flex items-center gap-3" style="background: hsl(var(--muted)/0.4);">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted-foreground"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
      <div class="text-sm">
        <div class="font-medium">Lokasi</div>
        <div id="locStatus" class="text-xs text-muted">Mendeteksi GPS…</div>
      </div>
    </div>
  </div>

  <div class="lg:col-span-2 space-y-4">
    <form id="absenForm" method="post" action="<?= e($base . $action) ?>" class="card card-soft p-6 <?= ($status['can_masuk'] || $status['can_pulang']) ? '' : 'hidden' ?>">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="lat" id="lat">
      <input type="hidden" name="lon" id="lon">
      <input type="hidden" name="foto_data" id="foto_data">
      <h3 class="font-semibold">Konfirmasi Absen <?= e($mode) ?></h3>
      <p class="text-xs text-muted mt-1">Tombol kirim akan aktif ketika swafoto dan GPS sudah terdeteksi.</p>

      <?php if ($isLateWindow): ?>
        <div class="mt-4">
          <label class="label">Alasan keterlambatan</label>
          <textarea name="alasan" class="textarea mt-1.5" minlength="10" maxlength="500" rows="3" required placeholder="10–500 karakter"></textarea>
        </div>
      <?php endif; ?>

      <button id="btnSubmit" class="btn btn-primary mt-4 w-full" disabled>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Kirim Absen <?= e($mode) ?>
      </button>
      <a class="btn btn-ghost mt-2 w-full" href="<?= e($base . '/pegawai') ?>">Batal</a>
    </form>

    <?php if (!$status['can_masuk'] && !$status['can_pulang']): ?>
      <div class="alert alert-warning">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>Saat ini belum/sudah lewat jendela absensi atau Anda sudah absen hari ini.</div>
      </div>
      <a class="btn btn-secondary w-full" href="<?= e($base . '/pegawai') ?>">Kembali</a>
    <?php endif; ?>

    <div class="card card-soft p-5">
      <h3 class="font-semibold text-sm">Petunjuk</h3>
      <ol class="text-xs text-muted mt-2 space-y-1.5 list-decimal list-inside">
        <li>Izinkan akses kamera dan GPS pada peramban.</li>
        <li>Pastikan wajah terlihat jelas, lalu klik <em>Ambil Swafoto</em>.</li>
        <li>Tunggu hingga lokasi GPS terdeteksi.</li>
        <li>Klik tombol kirim absen.</li>
      </ol>
    </div>
  </div>
</div>

<script>
(function () {
  const $ = id => document.getElementById(id);
  const cam = $('cam'), canvas = $('canvas'), preview = $('preview');
  const btnCap = $('btnCapture'), btnRetake = $('btnRetake'), btnSubmit = $('btnSubmit');
  const fotoInput = $('foto_data');
  const latIn = $('lat'), lonIn = $('lon');
  const locStatus = $('locStatus'), alertBox = $('alertBox');

  let stream = null, hasPhoto = false, hasGps = false;

  function alertHtml(msg, type='warning') {
    alertBox.innerHTML = '<div class="alert alert-' + type + '"><div>' + msg + '</div></div>';
  }
  function updateSubmit() { btnSubmit.disabled = !(hasPhoto && hasGps); }

  async function startCam() {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
      cam.srcObject = stream;
    } catch (e) {
      alertHtml('Izin kamera ditolak atau kamera tidak tersedia. Tidak dapat melanjutkan absen.', 'danger');
      btnCap.disabled = true;
    }
  }
  function capture() {
    canvas.width = cam.videoWidth || 640;
    canvas.height = cam.videoHeight || 480;
    canvas.getContext('2d').drawImage(cam, 0, 0, canvas.width, canvas.height);
    const data = canvas.toDataURL('image/jpeg', 0.85);
    fotoInput.value = data;
    preview.src = data; preview.classList.remove('hidden');
    cam.classList.add('hidden'); btnCap.classList.add('hidden');
    btnRetake.classList.remove('hidden');
    hasPhoto = true; updateSubmit();
  }
  btnCap?.addEventListener('click', e => { e.preventDefault(); capture(); });
  btnRetake?.addEventListener('click', () => {
    preview.classList.add('hidden'); cam.classList.remove('hidden');
    btnCap.classList.remove('hidden'); btnRetake.classList.add('hidden');
    fotoInput.value = ''; hasPhoto = false; updateSubmit();
  });

  if (!navigator.geolocation) {
    locStatus.textContent = 'GPS tidak didukung peramban ini.';
    alertHtml('GPS tidak didukung peramban ini.', 'danger');
  } else {
    const t = setTimeout(() => {
      if (!hasGps) {
        locStatus.textContent = 'Lokasi GPS tidak tersedia (timeout 30 detik).';
        alertHtml('Lokasi GPS tidak tersedia. Tidak dapat melanjutkan absen.', 'danger');
      }
    }, 30000);
    navigator.geolocation.getCurrentPosition(pos => {
      clearTimeout(t);
      latIn.value = pos.coords.latitude.toFixed(7);
      lonIn.value = pos.coords.longitude.toFixed(7);
      locStatus.textContent = latIn.value + ', ' + lonIn.value + ' (akurasi ' + Math.round(pos.coords.accuracy) + ' m)';
      hasGps = true; updateSubmit();
    }, err => {
      clearTimeout(t);
      locStatus.textContent = 'Akses GPS ditolak: ' + err.message;
      alertHtml('Akses GPS ditolak. Tidak dapat melanjutkan absen.', 'danger');
    }, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
  }
  startCam();
  window.addEventListener('beforeunload', () => stream?.getTracks().forEach(t => t.stop()));
})();
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
