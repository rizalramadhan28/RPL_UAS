-- Sistem Absensi Desa Wadas - Skema Database
-- MySQL 8.x, InnoDB, utf8mb4

CREATE DATABASE IF NOT EXISTS sapa_desa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sapa_desa;

SET time_zone = '+07:00';

-- =========================================================================
-- users
-- =========================================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip             VARCHAR(18)  DEFAULT NULL,
    username        VARCHAR(30)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    nama            VARCHAR(100) NOT NULL,
    jabatan         VARCHAR(100) NOT NULL,
    role            ENUM('Pegawai','Admin','KepalaDesa') NOT NULL,
    status          ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_nip (nip),
    KEY idx_users_status_role (status, role)
) ENGINE=InnoDB;

-- =========================================================================
-- sessions
-- =========================================================================
CREATE TABLE IF NOT EXISTS sessions (
    id              CHAR(64) PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    created_at      DATETIME NOT NULL,
    last_seen_at    DATETIME NOT NULL,
    revoked_at      DATETIME DEFAULT NULL,
    ip              VARCHAR(45) DEFAULT NULL,
    user_agent      VARCHAR(255) DEFAULT NULL,
    KEY idx_sessions_user (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================================
-- login_attempts
-- =========================================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(30) NOT NULL,
    success         TINYINT(1) NOT NULL DEFAULT 0,
    ip              VARCHAR(45) DEFAULT NULL,
    attempted_at    DATETIME NOT NULL,
    KEY idx_login_attempts_username_time (username, attempted_at)
) ENGINE=InnoDB;

-- =========================================================================
-- pengaturan
-- =========================================================================
CREATE TABLE IF NOT EXISTS pengaturan (
    id                       TINYINT UNSIGNED PRIMARY KEY,
    jam_masuk_mulai          TIME NOT NULL,
    jam_masuk_selesai        TIME NOT NULL,
    jam_terlambat_selesai    TIME NOT NULL,
    jam_pulang_mulai         TIME NOT NULL,
    latitude                 DECIMAL(10,7) NOT NULL,
    longitude                DECIMAL(10,7) NOT NULL,
    radius_meter             INT UNSIGNED NOT NULL,
    hari_kerja_mask          TINYINT UNSIGNED NOT NULL DEFAULT 31, -- bit0=Senin..bit6=Minggu, default Sen-Jum (0b0011111 = 31)
    nama_desa                VARCHAR(100) NOT NULL DEFAULT 'Desa Wadas',
    updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by               INT UNSIGNED DEFAULT NULL
) ENGINE=InnoDB;

-- =========================================================================
-- holidays
-- =========================================================================
CREATE TABLE IF NOT EXISTS holidays (
    tanggal     DATE PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- =========================================================================
-- absensi
-- =========================================================================
CREATE TABLE IF NOT EXISTS absensi (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pegawai_id          INT UNSIGNED NOT NULL,
    tanggal             DATE NOT NULL,
    status              ENUM('Hadir','Terlambat','Izin','Sakit','Alpha') NOT NULL,
    ts_masuk            DATETIME DEFAULT NULL,
    ts_pulang           DATETIME DEFAULT NULL,
    lat_masuk           DECIMAL(10,7) DEFAULT NULL,
    lon_masuk           DECIMAL(10,7) DEFAULT NULL,
    lat_pulang          DECIMAL(10,7) DEFAULT NULL,
    lon_pulang          DECIMAL(10,7) DEFAULT NULL,
    swafoto_masuk       VARCHAR(255) DEFAULT NULL,
    swafoto_pulang      VARCHAR(255) DEFAULT NULL,
    alasan_terlambat    VARCHAR(500) DEFAULT NULL,
    keterangan          VARCHAR(500) DEFAULT NULL,
    sumber              ENUM('manual','auto') NOT NULL DEFAULT 'manual',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_absensi_pegawai_tanggal (pegawai_id, tanggal),
    KEY idx_absensi_tanggal (tanggal),
    CONSTRAINT fk_absensi_pegawai FOREIGN KEY (pegawai_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================================
-- izin
-- =========================================================================
CREATE TABLE IF NOT EXISTS izin (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pegawai_id          INT UNSIGNED NOT NULL,
    jenis               ENUM('Izin','Sakit') NOT NULL,
    tanggal_mulai       DATE NOT NULL,
    tanggal_selesai     DATE NOT NULL,
    keterangan          VARCHAR(500) NOT NULL,
    status              ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
    lampiran_path       VARCHAR(255) DEFAULT NULL,
    alasan_penolakan    VARCHAR(500) DEFAULT NULL,
    decided_by          INT UNSIGNED DEFAULT NULL,
    decided_at          DATETIME DEFAULT NULL,
    nomor_referensi     CHAR(14) NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_izin_nomor_referensi (nomor_referensi),
    KEY idx_izin_pegawai_status (pegawai_id, status, tanggal_mulai, tanggal_selesai),
    CONSTRAINT fk_izin_pegawai FOREIGN KEY (pegawai_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_izin_decided_by FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================================
-- kegiatan
-- =========================================================================
CREATE TABLE IF NOT EXISTS kegiatan (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pegawai_id      INT UNSIGNED NOT NULL,
    tanggal         DATE NOT NULL,
    nama            VARCHAR(200) NOT NULL,
    jam_mulai       TIME NOT NULL,
    jam_selesai     TIME NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_kegiatan_pegawai_tanggal (pegawai_id, tanggal, jam_mulai),
    CONSTRAINT fk_kegiatan_pegawai FOREIGN KEY (pegawai_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================================
-- audit_log (append-only)
-- =========================================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id   INT UNSIGNED DEFAULT NULL,
    actor_nama      VARCHAR(100) DEFAULT NULL,
    actor_role      VARCHAR(20)  DEFAULT NULL,
    action          VARCHAR(50)  NOT NULL,
    target_type     VARCHAR(50)  DEFAULT NULL,
    target_id       BIGINT UNSIGNED DEFAULT NULL,
    before_json     JSON DEFAULT NULL,
    after_json      JSON DEFAULT NULL,
    ip              VARCHAR(45) DEFAULT NULL,
    created_at      DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    KEY idx_audit_action_time (action, created_at),
    KEY idx_audit_actor (actor_user_id)
) ENGINE=InnoDB;
