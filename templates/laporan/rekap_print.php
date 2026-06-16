<?php
$d = $data;
$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$periode = ($namaBulan[(int)$d['bulan']] ?? $d['bulan']) . ' ' . (int)$d['tahun'];
$namaDesa = $cfg['nama_desa'] ?? 'Desa Wadas';

// Rekap agregat untuk ringkasan
$tot = ['Hadir'=>0,'Terlambat'=>0,'Izin'=>0,'Sakit'=>0,'Alpha'=>0];
foreach ($d['rows'] as $r) {
    foreach ($tot as $k=>$v) $tot[$k] += (int)$r[$k];
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8">
<title>Laporan Rekap Kehadiran - <?= e($periode) ?></title>
<style>
  @page { size: A4 portrait; margin: 16mm 14mm; }
  * { box-sizing: border-box; }
  body { font-family: 'Times New Roman', Georgia, serif; font-size: 11.5pt; color: #111; margin: 0; }

  /* KOP SURAT */
  .kop { display: flex; align-items: center; gap: 16px; border-bottom: 3px double #000; padding-bottom: 10px; }
  .kop .logo { width: 80px; flex-shrink: 0; text-align: center; }
  .kop .logo img { width: 78px; height: 78px; object-fit: contain; }
  .kop .teks { flex: 1; text-align: center; line-height: 1.25; }
  .kop .teks .l1 { font-size: 13pt; font-weight: bold; letter-spacing: .5px; }
  .kop .teks .l2 { font-size: 16pt; font-weight: bold; letter-spacing: .5px; }
  .kop .teks .l3 { font-size: 18pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
  .kop .teks .l4 { font-size: 9.5pt; margin-top: 2px; }
  .kop .spacer { width: 80px; flex-shrink: 0; }

  .judul { text-align: center; margin: 18px 0 2px; }
  .judul h1 { font-size: 13.5pt; margin: 0; text-transform: uppercase; text-decoration: underline; letter-spacing: .5px; }
  .judul .sub { font-size: 11pt; margin-top: 2px; }

  .meta { width: 100%; margin: 12px 0 6px; font-size: 10.5pt; }
  .meta td { padding: 1px 0; vertical-align: top; }
  .meta .k { width: 130px; }
  .meta .s { width: 14px; }

  table.data { border-collapse: collapse; width: 100%; margin-top: 8px; font-size: 10.5pt; }
  table.data th, table.data td { border: 1px solid #333; padding: 5px 6px; }
  table.data thead th { background: #e8eef3; text-align: center; font-weight: bold; }
  table.data td.c { text-align: center; }
  table.data tbody tr:nth-child(even) { background: #f7f9fb; }
  table.data tfoot td { font-weight: bold; background: #eef2f6; text-align: center; }
  table.data tfoot td.lbl { text-align: right; }

  .ttd { margin-top: 36px; width: 100%; }
  .ttd td { vertical-align: top; font-size: 11pt; }
  .ttd .box { text-align: center; }
  .ttd .nama { font-weight: bold; text-decoration: underline; margin-top: 60px; }
  .ttd .jab { }

  .footnote { margin-top: 28px; font-size: 8.5pt; color: #555; border-top: 1px solid #ccc; padding-top: 4px; }

  .toolbar { background:#f1f5f9; border:1px solid #cbd5e1; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-family: Arial, sans-serif; }
  .toolbar button { background:#15803d; color:#fff; border:0; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:13px; }
  .toolbar button:hover { background:#166534; }
  @media print { .toolbar { display:none; } body { font-size: 11pt; } }
</style></head><body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
  <span style="margin-left:10px;color:#475569;font-size:12px;">Pilih <strong>Tujuan: Save as PDF</strong> dan aktifkan <strong>Background graphics</strong> agar logo & garis tabel tercetak.</span>
</div>

<!-- KOP SURAT -->
<div class="kop">
  <div class="logo">
    <?php if (!empty($logo)): ?><img src="<?= e($logo) ?>" alt="Logo Kabupaten Karawang"><?php endif; ?>
  </div>
  <div class="teks">
    <div class="l1">PEMERINTAH KABUPATEN KARAWANG</div>
    <div class="l2">KECAMATAN TELUKJAMBE BARAT</div>
    <div class="l3"><?= e($namaDesa) ?></div>
    <div class="l4">Jalan Raya Desa No. 1, Kabupaten Karawang, Jawa Barat · Kode Pos 41361</div>
  </div>
  <div class="spacer"></div>
</div>

<div class="judul">
  <h1>Laporan Rekapitulasi Kehadiran Perangkat Desa</h1>
  <div class="sub">Periode <?= e($periode) ?></div>
</div>

<table class="meta">
  <tr><td class="k">Periode Laporan</td><td class="s">:</td><td><?= e($periode) ?></td>
      <td class="k">Jumlah Hari Kerja</td><td class="s">:</td><td><?= (int)$d['total_hari_kerja'] ?> hari</td></tr>
  <tr><td class="k">Jumlah Pegawai</td><td class="s">:</td><td><?= count($d['rows']) ?> orang</td>
      <td class="k">Tanggal Cetak</td><td class="s">:</td><td><?= e($cetak_pada) ?> WIB</td></tr>
</table>

<table class="data">
  <thead>
    <tr>
      <th rowspan="2" style="width:28px;">No</th>
      <th rowspan="2">Nama</th>
      <th rowspan="2">Jabatan</th>
      <th colspan="5">Rekapitulasi Kehadiran</th>
      <th rowspan="2" style="width:48px;">Hari<br>Kerja</th>
    </tr>
    <tr>
      <th style="width:42px;">Hadir</th>
      <th style="width:54px;">Terlambat</th>
      <th style="width:42px;">Izin</th>
      <th style="width:42px;">Sakit</th>
      <th style="width:48px;">Alpha</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($d['rows'] as $i => $r): ?>
      <tr>
        <td class="c"><?= $i+1 ?></td>
        <td><?= e($r['nama']) ?></td>
        <td><?= e($r['jabatan']) ?></td>
        <td class="c"><?= (int)$r['Hadir'] ?></td>
        <td class="c"><?= (int)$r['Terlambat'] ?></td>
        <td class="c"><?= (int)$r['Izin'] ?></td>
        <td class="c"><?= (int)$r['Sakit'] ?></td>
        <td class="c"><?= (int)$r['Alpha'] ?></td>
        <td class="c"><?= (int)$d['total_hari_kerja'] ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($d['rows'])): ?>
      <tr><td colspan="9" class="c">Tidak ada data pada periode ini</td></tr>
    <?php endif; ?>
  </tbody>
  <?php if (!empty($d['rows'])): ?>
  <tfoot>
    <tr>
      <td class="lbl" colspan="3">TOTAL</td>
      <td><?= $tot['Hadir'] ?></td>
      <td><?= $tot['Terlambat'] ?></td>
      <td><?= $tot['Izin'] ?></td>
      <td><?= $tot['Sakit'] ?></td>
      <td><?= $tot['Alpha'] ?></td>
      <td>—</td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<!-- TANDA TANGAN -->
<table class="ttd">
  <tr>
    <td style="width:55%;">&nbsp;</td>
    <td class="box">
      <?= e($namaDesa) ?>, <?= e($cetak_tanggal) ?><br>
      Kepala Desa
      <div class="nama">( .................................... )</div>
    </td>
  </tr>
</table>

<div class="footnote">
  Dokumen ini dihasilkan secara otomatis oleh Sistem Absensi Perangkat Desa (SAPA Desa Wadas) pada <?= e($cetak_pada) ?> WIB.
  Keterangan status: Hadir, Terlambat, Izin, Sakit, dan Alpha (tanpa keterangan).
</div>

</body></html>
