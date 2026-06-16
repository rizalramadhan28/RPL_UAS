# Requirements Document

## Introduction

Sistem Absensi Berbasis Web untuk Perangkat Desa Wadas adalah aplikasi web yang mencatat kehadiran harian perangkat desa, memproses pengajuan izin/sakit, mencatat kegiatan harian, serta menyajikan rekap dan laporan bulanan. Sistem ini melayani tiga peran pengguna: Pegawai (perangkat desa), Admin (Kaur Pemerintahan), dan Kepala Desa. Sistem dirancang sederhana dan aman dengan validasi kehadiran berlapis (kredensial, swafoto, dan GPS), serta dapat ditampilkan pada layar TV sebagai papan informasi kehadiran. Implementasi menggunakan PHP 8.x native, MySQL 8.x, HTML/CSS/JavaScript (Bootstrap 5), dan pustaka cetak PDF (dompdf atau mpdf).

## Glossary

- **Sistem**: Aplikasi web Sistem Absensi Desa Wadas secara keseluruhan.
- **Modul_Autentikasi**: Komponen Sistem yang menangani login, validasi kredensial, validasi swafoto, validasi GPS, dan logout.
- **Modul_Absensi**: Komponen Sistem yang menangani pencatatan absen masuk, absen terlambat, dan absen pulang.
- **Modul_Izin**: Komponen Sistem yang menangani pengajuan dan persetujuan izin atau sakit.
- **Modul_Kegiatan**: Komponen Sistem yang menangani pencatatan register kegiatan harian Pegawai.
- **Modul_Pegawai**: Komponen Sistem yang menangani manajemen data Pegawai oleh Admin.
- **Modul_Pengaturan**: Komponen Sistem yang menangani pengaturan jam kerja dan parameter operasional.
- **Modul_Rekap**: Komponen Sistem yang menyajikan rekap kehadiran bulanan dengan filter.
- **Modul_Laporan**: Komponen Sistem yang menghasilkan berkas laporan PDF.
- **Modul_Dashboard**: Komponen Sistem yang menyajikan ringkasan kehadiran harian untuk Kepala Desa.
- **Display_Board**: Halaman publik Sistem yang menampilkan status kehadiran harian untuk ditayangkan di TV.
- **Pegawai**: Pengguna Sistem dengan peran perangkat desa yang melakukan absensi, izin, dan register kegiatan.
- **Admin**: Pengguna Sistem dengan peran Kaur Pemerintahan yang mengelola data Pegawai, jam kerja, izin, rekap, dan laporan.
- **Kepala_Desa**: Pengguna Sistem dengan peran pemantau yang hanya membaca dashboard dan laporan bulanan.
- **Sesi**: Status login pengguna yang aktif pada Sistem, ditandai dengan token sesi PHP.
- **Status_Kehadiran**: Salah satu nilai dari himpunan {Hadir, Terlambat, Izin, Sakit, Alpha}.
- **Jam_Masuk_Mulai**: Waktu mulai jendela absen masuk normal, nilai default 08.00 WIB, dapat diubah Admin.
- **Jam_Masuk_Selesai**: Waktu akhir jendela absen masuk normal, nilai default 10.00 WIB, dapat diubah Admin.
- **Jam_Terlambat_Selesai**: Waktu akhir jendela absen terlambat, nilai default 16.00 WIB, dapat diubah Admin.
- **Jam_Pulang_Mulai**: Waktu mulai jendela absen pulang, nilai default 14.00 WIB, dapat diubah Admin.
- **Radius_Absensi**: Jarak maksimal dalam meter antara koordinat GPS Pegawai dan koordinat Kantor Desa yang diperbolehkan untuk absen, nilai default 100 meter, dapat diubah Admin.
- **Kantor_Desa**: Titik koordinat (lintang, bujur) lokasi Kantor Desa Wadas yang disimpan pada pengaturan Sistem.
- **Swafoto**: Berkas gambar yang diambil dari kamera perangkat Pegawai pada saat absensi.
- **Hari_Kerja**: Hari yang ditandai sebagai hari kerja pada pengaturan Sistem (default Senin sampai Jumat).
- **Token_Sesi**: Pengenal unik Sesi pengguna yang dikelola Modul_Autentikasi.

## Requirements

### Requirement 1: Autentikasi Pengguna

**User Story:** Sebagai pengguna Sistem, saya ingin masuk ke Sistem dengan kredensial yang valid sesuai peran saya, agar hanya pengguna berwenang yang dapat mengakses fitur Sistem.

#### Acceptance Criteria

1. WHEN seorang pengguna mengirimkan username sepanjang 1 sampai 50 karakter dan password sepanjang 8 sampai 72 karakter yang cocok dengan data akun aktif, THE Modul_Autentikasi SHALL membuat Token_Sesi yang berlaku maksimum 60 menit dan mengarahkan pengguna ke halaman beranda sesuai peran dalam waktu maksimum 2 detik.
2. IF kredensial yang dikirim tidak cocok dengan data akun aktif, baik karena username tidak ditemukan, password tidak cocok, maupun akun nonaktif, THEN THE Modul_Autentikasi SHALL menolak login dan menampilkan pesan kesalahan generik yang mengindikasikan kredensial tidak valid tanpa membedakan penyebab.
3. IF lima percobaan login gagal terjadi pada akun yang sama dalam rentang 10 menit, THEN THE Modul_Autentikasi SHALL mengunci akun tersebut selama 15 menit, menolak setiap percobaan login pada akun terkunci, dan menampilkan pesan yang menunjukkan sisa durasi penguncian.
4. THE Modul_Autentikasi SHALL menyimpan password menggunakan algoritma hash password_hash bawaan PHP dengan algoritma PASSWORD_DEFAULT dan tidak menyimpan password dalam bentuk plaintext pada media penyimpanan apa pun.
5. WHEN pengguna mengakses halaman yang memerlukan login tanpa Token_Sesi yang valid atau dengan Token_Sesi yang telah melewati 60 menit sejak pembuatan, THE Sistem SHALL mengarahkan pengguna ke halaman login.
6. IF username atau password yang dikirim kosong atau melebihi batas panjang yang diizinkan, THEN THE Modul_Autentikasi SHALL menolak permintaan login dan menampilkan pesan kesalahan yang menunjukkan field yang tidak valid tanpa mencatat percobaan tersebut sebagai percobaan login gagal pada penghitung penguncian akun.
7. THE Modul_Autentikasi SHALL menyelesaikan proses validasi login dalam waktu maksimum 3 detik sejak permintaan login diterima oleh server.

### Requirement 2: Otorisasi Berbasis Peran

**User Story:** Sebagai pemilik Sistem, saya ingin setiap peran hanya dapat mengakses fitur yang sesuai, agar pemisahan tanggung jawab terjaga.

#### Acceptance Criteria

1. WHERE peran pengguna terotentikasi adalah Pegawai, THE Sistem SHALL mengizinkan akses baca dan tulis hanya pada fitur absensi, pengajuan izin, register kegiatan, dan riwayat pribadi yang dimiliki oleh pengguna tersebut, dan SHALL menolak akses pada fitur di luar daftar tersebut.
2. WHERE peran pengguna terotentikasi adalah Admin, THE Sistem SHALL mengizinkan akses baca dan tulis pada fitur manajemen Pegawai, pengaturan jam kerja, persetujuan izin, rekap, dan laporan, dan SHALL menolak akses pada fitur di luar daftar tersebut.
3. WHERE peran pengguna terotentikasi adalah Kepala_Desa, THE Sistem SHALL mengizinkan akses hanya-baca pada Modul_Dashboard dan Modul_Laporan, dan SHALL menolak setiap operasi tulis, ubah, atau hapus pada modul tersebut maupun akses ke modul lain.
4. IF pengguna terotentikasi meminta sumber daya di luar cakupan perannya, THEN THE Sistem SHALL menolak permintaan dalam waktu maksimal 1 detik, menampilkan pesan kesalahan yang mengindikasikan akses ditolak, dan tidak mengubah data atau status sumber daya yang diminta.
5. IF pengguna yang belum terotentikasi meminta sumber daya yang memerlukan peran, THEN THE Sistem SHALL menolak akses dan mengarahkan pengguna ke halaman autentikasi tanpa mengungkap keberadaan atau isi sumber daya yang diminta.
6. WHEN Sistem menolak permintaan karena pelanggaran otorisasi, THE Sistem SHALL mencatat peristiwa tersebut beserta identitas pengguna, peran pengguna, sumber daya yang diminta, dan waktu kejadian dengan presisi detik.
7. WHILE peran pengguna terotentikasi tidak terdefinisi atau bernilai kosong, THE Sistem SHALL memperlakukan pengguna seperti tidak memiliki hak akses dan menolak setiap permintaan ke sumber daya yang memerlukan peran.

### Requirement 3: Logout dan Pengelolaan Sesi

**User Story:** Sebagai pengguna, saya ingin keluar dari Sistem dengan aman, agar Sesi saya tidak disalahgunakan.

#### Acceptance Criteria

1. WHEN pengguna menekan tombol logout dan menekan tombol konfirmasi pada dialog konfirmasi logout, THE Modul_Autentikasi SHALL menginvalidasi Token_Sesi pada sisi server dalam waktu maksimal 3 detik dan mengarahkan pengguna ke halaman login.
2. WHEN tidak ada permintaan terautentikasi dari pengguna selama 30 menit berturut-turut pada Sesi aktif, THE Modul_Autentikasi SHALL menginvalidasi Token_Sesi, mengakhiri Sesi, dan mengarahkan permintaan berikutnya ke halaman login.
3. THE Modul_Autentikasi SHALL menetapkan atribut HttpOnly dan SameSite=Lax pada cookie Token_Sesi.
4. IF proses invalidasi Token_Sesi pada sisi server gagal saat logout, THEN THE Modul_Autentikasi SHALL menampilkan pesan kesalahan yang menunjukkan kegagalan invalidasi Sesi, tetap menghapus Token_Sesi pada sisi klien, dan mengarahkan pengguna ke halaman login.

### Requirement 4: Validasi Swafoto dan GPS pada Absensi

**User Story:** Sebagai Admin, saya ingin absensi divalidasi dengan swafoto dan GPS, agar data kehadiran lebih sulit dipalsukan.

#### Acceptance Criteria

1. WHEN Pegawai membuka halaman absensi, THE Modul_Absensi SHALL meminta izin akses kamera dan izin akses GPS pada peramban dalam waktu maksimum 3 detik setelah halaman dimuat.
2. IF peramban menolak izin kamera atau izin GPS, THEN THE Modul_Absensi SHALL menolak proses absensi, tidak membuat catatan absensi, dan menampilkan pesan kesalahan yang menyatakan izin kamera dan GPS diperlukan.
3. IF perangkat Pegawai tidak menyediakan koordinat GPS dalam waktu 30 detik sejak permintaan, THEN THE Modul_Absensi SHALL menolak proses absensi, tidak membuat catatan absensi, dan menampilkan pesan kesalahan yang menyatakan lokasi GPS tidak tersedia.
4. WHEN Pegawai mengirimkan absensi dengan Swafoto dan koordinat GPS yang valid, THE Modul_Absensi SHALL menyimpan Swafoto sebagai berkas pada server dan mencatat koordinat GPS pada catatan absensi dalam waktu maksimum 5 detik.
5. IF jarak antara koordinat GPS Pegawai dan koordinat Kantor_Desa, dihitung dengan akurasi GPS toleransi 50 meter, melebihi Radius_Absensi yang dikonfigurasi (default 100 meter), THEN THE Modul_Absensi SHALL menolak absensi, tidak membuat catatan absensi, dan menampilkan pesan kesalahan yang menyatakan lokasi di luar area Kantor Desa.
6. THE Modul_Absensi SHALL menerima Swafoto dengan ukuran maksimum 2 MB (2.097.152 byte) dan format JPEG atau PNG.
7. IF ukuran Swafoto melebihi 2 MB atau berformat di luar JPEG dan PNG, THEN THE Modul_Absensi SHALL menolak absensi, tidak menyimpan berkas Swafoto, dan menampilkan pesan kesalahan yang menyatakan Swafoto tidak valid.
8. IF berkas Swafoto kosong atau tidak terbaca, THEN THE Modul_Absensi SHALL menolak absensi, tidak membuat catatan absensi, dan menampilkan pesan kesalahan yang menyatakan Swafoto tidak valid.

### Requirement 5: Absensi Masuk pada Jam Normal

**User Story:** Sebagai Pegawai, saya ingin melakukan absen masuk pada jam kerja normal dengan satu klik, agar pencatatan kehadiran cepat dan akurat.

#### Acceptance Criteria

1. WHILE waktu Sistem berada pada rentang Jam_Masuk_Mulai sampai Jam_Masuk_Selesai pada Hari_Kerja, THE Modul_Absensi SHALL menampilkan halaman absen masuk untuk Pegawai yang belum memiliki catatan absen masuk pada hari tersebut dalam waktu maksimum 3 detik setelah halaman dimuat.
2. WHEN Pegawai menekan tombol absen masuk dan validasi Swafoto serta GPS berhasil sesuai Requirement 4, THE Modul_Absensi SHALL menyimpan catatan absensi dengan Status_Kehadiran bernilai "Hadir" dan timestamp menggunakan waktu server dengan presisi detik.
3. IF validasi Swafoto atau GPS gagal saat Pegawai menekan tombol absen masuk, THEN THE Modul_Absensi SHALL menolak absensi, tidak membuat catatan absensi, dan menampilkan pesan kesalahan yang menunjukkan jenis validasi yang gagal.
4. IF Pegawai sudah memiliki catatan absen masuk pada hari yang sama, THEN THE Modul_Absensi SHALL menolak absensi dan menampilkan pesan "Anda sudah absen masuk hari ini".
5. WHILE waktu Sistem berada di luar Hari_Kerja, THE Modul_Absensi SHALL menonaktifkan tombol absen masuk dan menampilkan pesan "Hari ini bukan hari kerja".

### Requirement 6: Absensi Terlambat

**User Story:** Sebagai Pegawai yang datang setelah jam masuk normal, saya ingin tetap dapat melakukan absen masuk, agar kehadiran saya tetap tercatat dengan status terlambat.

#### Acceptance Criteria

1. WHILE waktu Sistem berada pada rentang setelah Jam_Masuk_Selesai sampai Jam_Terlambat_Selesai pada Hari_Kerja, THE Modul_Absensi SHALL mengarahkan Pegawai yang belum memiliki catatan absen masuk ke halaman absen terlambat dalam waktu maksimum 3 detik.
2. WHEN Pegawai menekan tombol absen pada halaman terlambat dan validasi Swafoto serta GPS berhasil sesuai Requirement 4, THE Modul_Absensi SHALL menyimpan catatan absensi dengan Status_Kehadiran bernilai "Terlambat" dan timestamp menggunakan waktu server dengan presisi detik.
3. THE Modul_Absensi SHALL mewajibkan Pegawai mengisi alasan keterlambatan dengan panjang minimum 10 karakter dan maksimum 500 karakter setelah pemangkasan spasi pada halaman absen terlambat.
4. IF Pegawai mengirim absen terlambat tanpa alasan, dengan alasan kurang dari 10 karakter setelah pemangkasan spasi, atau lebih dari 500 karakter, THEN THE Modul_Absensi SHALL menolak absensi, mempertahankan input Pegawai, dan menampilkan pesan kesalahan yang menyatakan alasan keterlambatan harus 10 sampai 500 karakter.
5. IF Pegawai sudah memiliki catatan absen masuk pada hari yang sama, THEN THE Modul_Absensi SHALL menolak absensi terlambat dan menampilkan pesan "Anda sudah absen masuk hari ini".

### Requirement 7: Absensi Pulang

**User Story:** Sebagai Pegawai, saya ingin melakukan absen pulang, agar jam kerja harian saya tercatat lengkap.

#### Acceptance Criteria

1. WHILE waktu Sistem berada pada rentang Jam_Pulang_Mulai sampai pukul 23:59:59 pada Hari_Kerja, THE Modul_Absensi SHALL menampilkan tombol absen pulang untuk Pegawai yang sudah memiliki catatan absen masuk pada hari tersebut.
2. WHEN Pegawai menekan tombol absen pulang dan validasi Swafoto serta GPS berhasil sesuai Requirement 4, THE Modul_Absensi SHALL memperbarui catatan absensi hari tersebut dengan timestamp pulang menggunakan waktu server.
3. IF Pegawai menekan absen pulang tanpa adanya catatan absen masuk pada hari yang sama, THEN THE Modul_Absensi SHALL menolak absensi dan menampilkan pesan "Belum ada absen masuk hari ini".
4. IF Pegawai sudah memiliki timestamp pulang pada hari yang sama, THEN THE Modul_Absensi SHALL menolak absensi dan menampilkan pesan "Anda sudah absen pulang hari ini".
5. IF Pegawai mencoba absen pulang sebelum Jam_Pulang_Mulai atau pada hari di luar Hari_Kerja, THEN THE Modul_Absensi SHALL menonaktifkan tombol absen pulang dan menampilkan pesan "Belum memasuki jam pulang".

### Requirement 8: Penetapan Status Alpha Otomatis

**User Story:** Sebagai Admin, saya ingin status Alpha tercatat otomatis untuk Pegawai yang tidak absen, agar rekap kehadiran tetap konsisten tanpa input manual.

#### Acceptance Criteria

1. WHEN waktu Sistem mencapai akhir Jam_Terlambat_Selesai pada Hari_Kerja, THE Modul_Absensi SHALL membuat tepat satu catatan absensi dengan Status_Kehadiran bernilai "Alpha" untuk setiap Pegawai berstatus aktif yang pada hari tersebut belum memiliki catatan check-in, izin disetujui, sakit disetujui, atau cuti disetujui.
2. WHILE waktu Sistem berada di luar Hari_Kerja atau pada tanggal yang terdaftar sebagai hari libur nasional, THE Modul_Absensi SHALL tidak membuat catatan absensi dengan Status_Kehadiran "Alpha".
3. THE Modul_Absensi SHALL menyelesaikan proses penetapan status Alpha untuk seluruh Pegawai aktif dalam rentang 0 sampai 60 menit setelah Jam_Terlambat_Selesai berakhir.
4. IF proses penetapan status Alpha gagal untuk satu atau lebih Pegawai aktif, THEN THE Modul_Absensi SHALL mengulang proses tersebut maksimal 3 kali dengan jeda 5 menit antar percobaan dan menampilkan notifikasi kegagalan kepada Admin apabila seluruh percobaan tetap gagal.
5. WHEN catatan absensi dengan Status_Kehadiran "Alpha" dibuat oleh proses otomatis, THE Modul_Absensi SHALL menandai catatan tersebut sebagai hasil proses otomatis dan menyimpan waktu pembuatan dengan presisi detik untuk keperluan audit.

### Requirement 9: Pengajuan Izin atau Sakit oleh Pegawai

**User Story:** Sebagai Pegawai, saya ingin mengajukan izin atau sakit dengan keterangan dan lampiran, agar ketidakhadiran saya terdokumentasi.

#### Acceptance Criteria

1. WHEN Pegawai mengirim pengajuan izin dengan jenis bernilai "Izin" atau "Sakit", tanggal mulai, tanggal selesai, dan keterangan sepanjang 10 sampai 500 karakter, THE Modul_Izin SHALL menyimpan pengajuan dengan status awal "Menunggu" dalam waktu maksimum 3 detik.
2. IF salah satu field wajib (jenis, tanggal mulai, tanggal selesai, atau keterangan) kosong atau jenis bernilai selain "Izin" dan "Sakit", THEN THE Modul_Izin SHALL menolak pengajuan, tidak menyimpan data, dan menampilkan pesan kesalahan yang menyebutkan field yang tidak valid.
3. IF tanggal mulai pengajuan lebih besar dari tanggal selesai, THEN THE Modul_Izin SHALL menolak pengajuan, tidak menyimpan data, dan menampilkan pesan "Tanggal mulai harus sebelum atau sama dengan tanggal selesai".
4. IF rentang tanggal pengajuan beririsan dengan pengajuan izin lain milik Pegawai yang sama yang berstatus "Menunggu" atau "Disetujui", THEN THE Modul_Izin SHALL menolak pengajuan dan menampilkan pesan kesalahan yang menyatakan adanya tumpang tindih tanggal.
5. WHERE Pegawai mengunggah lampiran pendukung, THE Modul_Izin SHALL menerima berkas dengan format PDF, JPEG, atau PNG dan ukuran maksimum 2 MB (2.097.152 byte).
6. IF lampiran melebihi 2 MB atau berformat di luar PDF, JPEG, dan PNG, THEN THE Modul_Izin SHALL menolak pengajuan, tidak menyimpan data maupun berkas, dan menampilkan pesan "Berkas lampiran tidak valid".
7. WHEN pengajuan izin berhasil disimpan dengan status "Menunggu", THE Modul_Izin SHALL menampilkan konfirmasi keberhasilan kepada Pegawai berisi nomor referensi pengajuan dan ringkasan jenis serta rentang tanggal.
8. WHEN pengajuan izin disetujui untuk rentang tanggal tertentu, THE Modul_Izin SHALL menetapkan Status_Kehadiran pada setiap hari dalam rentang tersebut bernilai "Izin" atau "Sakit" sesuai jenis pengajuan dalam waktu maksimum 5 detik setelah persetujuan.

### Requirement 10: Persetujuan Izin oleh Admin

**User Story:** Sebagai Admin, saya ingin meninjau dan menyetujui atau menolak pengajuan izin Pegawai, agar setiap ketidakhadiran terverifikasi.

#### Acceptance Criteria

1. WHEN Admin membuka daftar pengajuan izin, THE Modul_Izin SHALL menampilkan pengajuan berstatus "Menunggu" terurut menaik berdasarkan timestamp pengiriman, dengan paginasi maksimum 25 entri per halaman, dalam waktu maksimum 3 detik.
2. IF tidak terdapat pengajuan berstatus "Menunggu", THEN THE Modul_Izin SHALL menampilkan pesan informatif yang menyatakan tidak ada pengajuan menunggu persetujuan.
3. WHEN Admin menyetujui pengajuan berstatus "Menunggu", THE Modul_Izin SHALL mengubah status pengajuan menjadi "Disetujui" dan mencatat identitas Admin (user ID dan nama) serta timestamp keputusan dengan presisi detik.
4. WHEN Admin menolak pengajuan berstatus "Menunggu" dengan alasan sepanjang 3 sampai 500 karakter setelah pemangkasan spasi, THE Modul_Izin SHALL mengubah status pengajuan menjadi "Ditolak", mencatat identitas Admin (user ID dan nama), timestamp dengan presisi detik, dan menyimpan alasan penolakan.
5. IF Admin menolak pengajuan dengan alasan kosong, hanya berisi spasi, atau kurang dari 3 karakter setelah pemangkasan spasi, THEN THE Modul_Izin SHALL menolak aksi, mempertahankan status pengajuan tetap "Menunggu", dan menampilkan pesan kesalahan yang menyatakan alasan penolakan wajib diisi.
6. IF Admin mencoba menyetujui atau menolak pengajuan yang sudah berstatus "Disetujui" atau "Ditolak", THEN THE Modul_Izin SHALL menolak aksi, mempertahankan status pengajuan, dan menampilkan pesan kesalahan yang menyatakan pengajuan sudah diputuskan.

### Requirement 11: Register Kegiatan Harian

**User Story:** Sebagai Pegawai, saya ingin mencatat kegiatan harian saya, agar Admin dan Kepala_Desa dapat melihat aktivitas saya.

#### Acceptance Criteria

1. WHEN Pegawai terautentikasi mengirim catatan kegiatan dengan nama kegiatan, waktu mulai (format HH:MM, 24 jam), dan waktu selesai (format HH:MM, 24 jam) pada hari yang berstatus Hari_Kerja, THE Modul_Kegiatan SHALL menyimpan catatan kegiatan terhubung dengan Pegawai dan tanggal hari berjalan dalam waktu maksimum 3 detik serta menampilkan konfirmasi keberhasilan kepada Pegawai.
2. IF waktu mulai lebih besar dari atau sama dengan waktu selesai, THEN THE Modul_Kegiatan SHALL menolak penyimpanan, mempertahankan input Pegawai, dan menampilkan pesan kesalahan yang mengindikasikan bahwa waktu mulai harus sebelum waktu selesai.
3. THE Modul_Kegiatan SHALL membatasi panjang nama kegiatan minimum 3 karakter dan maksimum 200 karakter setelah pemangkasan spasi di awal dan akhir.
4. IF nama kegiatan kurang dari 3 karakter atau lebih dari 200 karakter setelah pemangkasan spasi, THEN THE Modul_Kegiatan SHALL menolak penyimpanan, mempertahankan input Pegawai, dan menampilkan pesan kesalahan yang mengindikasikan batasan panjang nama kegiatan 3-200 karakter.
5. WHEN Pegawai membuka halaman register kegiatan, THE Modul_Kegiatan SHALL menampilkan daftar kegiatan miliknya pada tanggal hari berjalan diurutkan berdasarkan waktu mulai secara menaik dalam waktu maksimum 3 detik.
6. IF Pegawai mengirim catatan kegiatan pada hari yang bukan Hari_Kerja, THEN THE Modul_Kegiatan SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang mengindikasikan bahwa kegiatan hanya dapat dicatat pada Hari_Kerja.
7. WHILE daftar kegiatan Pegawai pada tanggal hari berjalan kosong, THE Modul_Kegiatan SHALL menampilkan pesan informatif yang mengindikasikan belum ada kegiatan tercatat untuk hari tersebut.

### Requirement 12: Manajemen Data Pegawai oleh Admin

**User Story:** Sebagai Admin, saya ingin menambah, mengubah, dan menonaktifkan data Pegawai, agar daftar Pegawai aktif selalu sesuai dengan kondisi nyata.

#### Acceptance Criteria

1. WHEN Admin menambah Pegawai dengan NIP sepanjang 18 digit numerik, nama lengkap sepanjang 3 sampai 100 karakter, jabatan sepanjang 3 sampai 100 karakter, username sepanjang 4 sampai 30 karakter alfanumerik, dan password awal sepanjang minimum 8 karakter, THE Modul_Pegawai SHALL membuat akun Pegawai dengan peran "Pegawai" dan status "Aktif" dalam waktu maksimum 3 detik.
2. IF salah satu field wajib (NIP, nama lengkap, jabatan, username, password awal) kosong atau tidak memenuhi format dan batas panjang yang ditentukan, THEN THE Modul_Pegawai SHALL menolak penyimpanan, tidak membuat akun, dan menampilkan pesan kesalahan yang menunjukkan field yang tidak valid.
3. IF NIP atau username sudah digunakan oleh akun lain, THEN THE Modul_Pegawai SHALL menolak penyimpanan, tidak membuat akun, dan menampilkan pesan "NIP atau username sudah terdaftar".
4. WHEN Admin mengubah data Pegawai pada field nama lengkap, jabatan, atau status, THE Modul_Pegawai SHALL menyimpan perubahan dan mencatat timestamp pembaruan dengan presisi detik.
5. WHEN Admin menonaktifkan Pegawai, THE Modul_Pegawai SHALL mengubah status Pegawai menjadi "Nonaktif", menginvalidasi seluruh Sesi aktif milik Pegawai tersebut, dan mencegah login akun tersebut.
6. THE Modul_Pegawai SHALL mempertahankan riwayat absensi, izin, dan kegiatan milik Pegawai yang dinonaktifkan untuk keperluan rekap dan audit.

### Requirement 13: Pengaturan Jam Kerja dan Lokasi

**User Story:** Sebagai Admin, saya ingin mengatur jam kerja dan koordinat Kantor_Desa, agar aturan absensi sesuai kebijakan desa terkini.

#### Acceptance Criteria

1. WHEN Admin menyimpan pengaturan dengan Jam_Masuk_Mulai, Jam_Masuk_Selesai, Jam_Terlambat_Selesai, dan Jam_Pulang_Mulai dalam format HH:MM (00:00-23:59), koordinat Kantor_Desa berupa latitude (-90 sampai 90) dan longitude (-180 sampai 180), serta Radius_Absensi dalam satuan meter, THE Modul_Pengaturan SHALL menyimpan nilai tersebut sebagai pengaturan aktif Sistem dan mencatat waktu penyimpanan.
2. IF Jam_Masuk_Mulai tidak lebih kecil dari Jam_Masuk_Selesai, atau Jam_Masuk_Selesai tidak lebih kecil dari Jam_Terlambat_Selesai, atau Jam_Terlambat_Selesai tidak lebih kecil dari Jam_Pulang_Mulai, THEN THE Modul_Pengaturan SHALL menolak penyimpanan dan menampilkan pesan "Urutan jam tidak valid" tanpa mengubah pengaturan aktif sebelumnya.
3. IF Radius_Absensi kurang dari 10 meter atau lebih dari 5000 meter, THEN THE Modul_Pengaturan SHALL menolak penyimpanan dan menampilkan pesan "Radius harus 10-5000 meter" tanpa mengubah pengaturan aktif sebelumnya.
4. THE Modul_Pengaturan SHALL menerapkan pengaturan baru pada absensi yang dilakukan setelah waktu penyimpanan pengaturan dan mempertahankan pengaturan sebelumnya untuk catatan absensi yang sudah tersimpan.
5. IF salah satu field wajib (Jam_Masuk_Mulai, Jam_Masuk_Selesai, Jam_Terlambat_Selesai, Jam_Pulang_Mulai, latitude, longitude, Radius_Absensi) kosong atau tidak sesuai format yang ditentukan, THEN THE Modul_Pengaturan SHALL menolak penyimpanan dan menampilkan pesan kesalahan yang menunjukkan field yang tidak valid.
6. IF pengguna yang melakukan penyimpanan pengaturan bukan Admin, THEN THE Modul_Pengaturan SHALL menolak permintaan dan menampilkan pesan kesalahan otorisasi tanpa mengubah pengaturan aktif.

### Requirement 14: Rekap Kehadiran Bulanan

**User Story:** Sebagai Admin, saya ingin melihat rekap kehadiran per bulan dan per nama Pegawai, agar saya dapat mengevaluasi kehadiran dengan cepat.

#### Acceptance Criteria

1. WHEN Admin memilih bulan (1-12) dan tahun (2020 sampai tahun berjalan) pada halaman rekap, THE Modul_Rekap SHALL menampilkan jumlah hari Hadir, Terlambat, Izin, Sakit, dan Alpha untuk setiap Pegawai aktif pada periode tersebut dalam waktu maksimum 5 detik.
2. WHERE Admin memilih nama Pegawai pada filter, THE Modul_Rekap SHALL menampilkan rekap harian Pegawai tersebut pada bulan terpilih dengan kolom tanggal, Status_Kehadiran, timestamp masuk, timestamp pulang, dan keterangan.
3. THE Modul_Rekap SHALL menghitung hanya Hari_Kerja sebagai dasar perhitungan total hari pada periode rekap.
4. WHEN data absensi pada periode terpilih tidak ditemukan, THE Modul_Rekap SHALL menampilkan pesan "Tidak ada data pada periode ini".
5. IF bulan atau tahun yang dipilih berada di luar rentang valid (bulan 1-12, tahun 2020 sampai tahun berjalan), THEN THE Modul_Rekap SHALL menolak permintaan dan menampilkan pesan kesalahan yang menyatakan periode tidak valid.

### Requirement 15: Cetak Laporan Bulanan PDF

**User Story:** Sebagai Admin, saya ingin mencetak laporan bulanan dalam format PDF, agar dapat diarsipkan atau diserahkan ke pimpinan.

#### Acceptance Criteria

1. WHEN Admin menekan tombol cetak PDF pada halaman rekap, THE Modul_Laporan SHALL menghasilkan berkas PDF berisi rekap bulan terpilih dengan kop laporan, periode laporan, dan tanggal cetak dalam waktu maksimum 10 detik.
2. THE Modul_Laporan SHALL menyertakan kolom Nama, Jabatan, Hadir, Terlambat, Izin, Sakit, Alpha, dan Total Hari Kerja pada laporan rekap seluruh Pegawai.
3. WHERE Admin memilih nama Pegawai pada filter, THE Modul_Laporan SHALL menghasilkan PDF detail harian Pegawai tersebut pada bulan terpilih dengan kolom tanggal, Status_Kehadiran, timestamp masuk, timestamp pulang, dan keterangan.
4. THE Modul_Laporan SHALL menyediakan tampilan ramah cetak peramban sebagai alternatif PDF dengan tata letak yang sama.
5. IF proses pembuatan PDF gagal, THEN THE Modul_Laporan SHALL menampilkan pesan kesalahan yang menyatakan kegagalan pembuatan laporan dan tidak mengirim berkas PDF kepada Admin.

### Requirement 16: Dashboard Monitoring Kepala Desa

**User Story:** Sebagai Kepala_Desa, saya ingin melihat ringkasan kehadiran hari ini, agar saya dapat memantau perangkat desa secara cepat.

#### Acceptance Criteria

1. WHEN Kepala_Desa membuka Modul_Dashboard, THE Modul_Dashboard SHALL menampilkan persentase kehadiran hari berjalan yang dihitung dari jumlah Pegawai berstatus Hadir atau Terlambat dibagi total Pegawai aktif, ditampilkan dalam format persentase dengan dua angka desimal, dalam waktu maksimum 3 detik setelah halaman dimuat.
2. IF total Pegawai aktif sama dengan nol saat Kepala_Desa membuka Modul_Dashboard, THEN THE Modul_Dashboard SHALL menampilkan nilai persentase "0%" disertai indikator informasi yang menyatakan tidak terdapat Pegawai aktif tanpa menghasilkan kesalahan sistem.
3. WHEN Kepala_Desa membuka Modul_Dashboard, THE Modul_Dashboard SHALL menampilkan daftar Pegawai dengan Status_Kehadiran hari ini yang dikelompokkan ke dalam lima kategori Hadir, Terlambat, Izin, Sakit, dan Alpha, di mana setiap kategori menampilkan jumlah Pegawai dan nama Pegawai pada kategori tersebut diurutkan berdasarkan nama secara alfabet menaik.
4. WHEN Kepala_Desa membuka Modul_Dashboard, THE Modul_Dashboard SHALL menyajikan ringkasan rekap bulan berjalan dalam mode hanya-baca yang mencakup total hari kerja, total Hadir, total Terlambat, total Izin, total Sakit, dan total Alpha untuk seluruh Pegawai aktif.
5. WHEN Kepala_Desa membuka halaman selain Modul_Dashboard dan Modul_Laporan, THE Sistem SHALL menolak akses sesuai Requirement 2.

### Requirement 17: Display Board untuk TV

**User Story:** Sebagai pengelola informasi desa, saya ingin menampilkan papan kehadiran pada TV di kantor, agar tamu dan pegawai dapat melihat status kehadiran hari ini.

#### Acceptance Criteria

1. WHEN halaman Display_Board dibuka, THE Display_Board SHALL menampilkan daftar Pegawai aktif dengan Status_Kehadiran hari berjalan (zona waktu WIB UTC+7) yang dikelompokkan ke dalam lima kategori Hadir, Terlambat, Izin, Sakit, dan Alpha dalam waktu maksimum 5 detik setelah halaman dimuat.
2. THE Display_Board SHALL memuat ulang data setiap 60 detik tanpa interaksi pengguna.
3. THE Display_Board SHALL dapat diakses tanpa login dan menampilkan hanya nama Pegawai, jabatan, dan Status_Kehadiran tanpa data sensitif lainnya.
4. THE Display_Board SHALL menggunakan tata letak yang terbaca pada layar dengan resolusi minimal 1280 x 720 piksel dengan ukuran font minimum 24 piksel dan rasio kontras teks terhadap latar minimum 4.5:1.
5. IF pemuatan ulang data gagal akibat kegagalan jaringan atau server, THEN THE Display_Board SHALL mempertahankan tampilan data terakhir yang berhasil dimuat dan menampilkan indikator status koneksi yang menunjukkan kegagalan pembaruan.
6. WHILE tidak terdapat Pegawai aktif pada Sistem, THE Display_Board SHALL menampilkan pesan informatif yang menyatakan belum ada data Pegawai aktif tanpa menghasilkan kesalahan sistem.

### Requirement 18: Riwayat Pribadi Pegawai

**User Story:** Sebagai Pegawai, saya ingin melihat riwayat absensi dan izin saya, agar saya dapat memeriksa rekam kehadiran pribadi.

#### Acceptance Criteria

1. WHEN Pegawai membuka halaman riwayat, THE Sistem SHALL menampilkan daftar absensi dan pengajuan izin milik Pegawai tersebut pada bulan kalender berjalan (tanggal 1 hingga tanggal terakhir bulan saat ini) secara default, diurutkan berdasarkan tanggal terbaru ke terlama, dalam waktu maksimal 3 detik.
2. WHEN Pegawai memilih bulan dan tahun lain pada filter dalam rentang maksimal 12 bulan terakhir dari tanggal saat ini, THE Sistem SHALL menampilkan riwayat absensi dan izin pada periode terpilih dalam waktu maksimal 3 detik.
3. THE Sistem SHALL membatasi riwayat hanya pada data milik Pegawai yang sedang login berdasarkan identitas pada sesi autentikasi aktif, dan menolak permintaan untuk mengakses data milik Pegawai lain.
4. IF tidak terdapat data absensi maupun izin pada periode yang ditampilkan, THEN THE Sistem SHALL menampilkan pesan kosong yang menyatakan bahwa tidak ada riwayat pada periode tersebut tanpa menampilkan pesan kesalahan.
5. IF Pegawai memilih periode di luar rentang 12 bulan terakhir, THEN THE Sistem SHALL menolak permintaan filter dan menampilkan pesan indikasi bahwa periode yang dipilih berada di luar rentang yang diizinkan.

### Requirement 19: Audit Log Aksi Sensitif

**User Story:** Sebagai Admin, saya ingin tindakan sensitif tercatat pada log audit, agar penyalahgunaan dapat ditelusuri.

#### Acceptance Criteria

1. WHEN Admin membuat, mengubah, atau menonaktifkan akun Pegawai, THE Sistem SHALL mencatat entri pada audit log yang berisi identitas Admin (user ID dan nama), jenis aksi (create, update, atau deactivate), identitas akun Pegawai yang terdampak, dan timestamp dalam format ISO 8601 dengan zona waktu WIB (UTC+7), dalam waktu paling lambat 2 detik setelah aksi selesai.
2. WHEN Admin menyetujui atau menolak pengajuan izin, THE Sistem SHALL mencatat entri pada audit log yang berisi identitas Admin (user ID dan nama), jenis aksi (approve atau reject), ID pengajuan izin, identitas Pegawai pemohon, dan timestamp dalam format ISO 8601 dengan zona waktu WIB (UTC+7).
3. WHEN Admin mengubah pengaturan jam kerja atau lokasi, THE Sistem SHALL mencatat entri pada audit log yang berisi identitas Admin (user ID dan nama), jenis pengaturan yang diubah, nilai sebelum perubahan, nilai setelah perubahan, dan timestamp dalam format ISO 8601 dengan zona waktu WIB (UTC+7).
4. THE Sistem SHALL menjadikan seluruh entri audit log bersifat append-only sehingga tidak dapat diubah atau dihapus oleh pengguna mana pun, termasuk Admin, melalui antarmuka aplikasi.
5. THE Sistem SHALL menyimpan setiap entri audit log minimal 365 hari sejak tanggal pencatatan sebelum dapat diarsipkan.
6. IF pencatatan audit log gagal saat aksi sensitif dilakukan, THEN THE Sistem SHALL membatalkan aksi tersebut dan menampilkan pesan kesalahan kepada Admin yang mengindikasikan kegagalan pencatatan audit log.

### Requirement 20: Keamanan Aplikasi

**User Story:** Sebagai pemilik Sistem, saya ingin Sistem terlindungi dari serangan dasar web, agar data perangkat desa tetap aman.

#### Acceptance Criteria

1. THE Sistem SHALL menggunakan PDO dengan prepared statement untuk seluruh kueri MySQL yang menerima parameter dari masukan pengguna atau sumber eksternal.
2. WHEN pengguna mengirim formulir yang melakukan perubahan data (create, update, atau delete), THE Sistem SHALL memvalidasi token CSRF yang dikaitkan dengan sesi pengguna dan berusia tidak lebih dari 60 menit sebelum memproses permintaan.
3. IF token CSRF tidak ada, tidak cocok dengan sesi pengguna, atau telah kedaluwarsa, THEN THE Sistem SHALL menolak permintaan, tidak melakukan perubahan data, dan menampilkan pesan kesalahan yang menunjukkan kegagalan validasi keamanan.
4. WHEN Sistem menampilkan data masukan pengguna pada HTML, THE Sistem SHALL melakukan escaping menggunakan htmlspecialchars dengan flag ENT_QUOTES dan charset UTF-8.
5. IF berkas yang diunggah memiliki ekstensi di luar daftar yang diizinkan (jpg, jpeg, png untuk Swafoto; jpg, jpeg, png, pdf untuk lampiran izin) atau MIME type yang tidak sesuai dengan ekstensi yang diizinkan, THEN THE Sistem SHALL menolak unggahan, tidak menyimpan berkas, dan menampilkan pesan kesalahan yang menunjukkan jenis berkas tidak diizinkan.
6. IF ukuran berkas yang diunggah melebihi 5 MB, THEN THE Sistem SHALL menolak unggahan, tidak menyimpan berkas, dan menampilkan pesan kesalahan yang menunjukkan batas ukuran berkas terlampaui.
7. THE Sistem SHALL menyimpan berkas Swafoto dan lampiran izin di direktori yang dikonfigurasi agar tidak dapat dieksekusi sebagai skrip oleh server web dan hanya dapat diakses melalui mekanisme pengunduhan terotentikasi yang disediakan Sistem.
