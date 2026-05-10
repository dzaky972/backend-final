-- ============================================================
-- DATABASE: backend_jasa
-- IMA Creative Production
-- Import di XAMPP phpMyAdmin:
--   1. Buka http://localhost/phpmyadmin
--   2. Klik "New" → buat database "backend_jasa" → pilih utf8mb4_unicode_ci
--   3. Klik tab "Import" → pilih file ini → klik "Go"
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── TABEL USERS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id_user`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`              VARCHAR(255) NOT NULL,
  `email`             VARCHAR(255) NOT NULL UNIQUE,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password`          VARCHAR(255) NOT NULL,
  `no_telp`           VARCHAR(30) NULL,
  `remember_token`    VARCHAR(100) NULL,
  `created_at`        TIMESTAMP NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL PASSWORD RESET TOKENS ─────────────────────────────
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL SESSIONS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sessions` (
  `id`            VARCHAR(255) NOT NULL,
  `user_id`       BIGINT UNSIGNED NULL,
  `ip_address`    VARCHAR(45) NULL,
  `user_agent`    TEXT NULL,
  `payload`       LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL CACHE ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cache` (
  `key`        VARCHAR(255) NOT NULL,
  `value`      MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key`        VARCHAR(255) NOT NULL,
  `owner`      VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL JOBS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `jobs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue`        VARCHAR(255) NOT NULL,
  `payload`      LONGTEXT NOT NULL,
  `attempts`     TINYINT UNSIGNED NOT NULL,
  `reserved_at`  INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at`   INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id`             VARCHAR(255) NOT NULL,
  `name`           VARCHAR(255) NOT NULL,
  `total_jobs`     INT NOT NULL,
  `pending_jobs`   INT NOT NULL,
  `failed_jobs`    INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options`        MEDIUMTEXT NULL,
  `cancelled_at`   INT NULL,
  `created_at`     INT NOT NULL,
  `finished_at`    INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`       VARCHAR(255) NOT NULL UNIQUE,
  `connection` TEXT NOT NULL,
  `queue`      TEXT NOT NULL,
  `payload`    LONGTEXT NOT NULL,
  `exception`  LONGTEXT NOT NULL,
  `failed_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL PELANGGAN ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pelanggan` (
  `id_user`    BIGINT UNSIGNED NOT NULL,
  `alamat`     VARCHAR(500) NULL,
  `perusahaan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  CONSTRAINT `pelanggan_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL ADMIN ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin` (
  `id_user`    BIGINT UNSIGNED NOT NULL,
  `role_level` VARCHAR(50) NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  CONSTRAINT `admin_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL JASA ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `jasa` (
  `id_jasa`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_jasa`       VARCHAR(255) NOT NULL,
  `deskripsi`       TEXT NOT NULL,
  `harga`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status_tersedia` VARCHAR(50) NOT NULL DEFAULT 'tersedia',
  `icon`            VARCHAR(50) NULL,
  `emoji`           VARCHAR(50) NULL,
  `tag`             VARCHAR(100) NULL,
  `tag_color`       VARCHAR(50) NULL,
  `img_bg`          VARCHAR(500) NULL,
  `features`        JSON NULL,
  `packages`        JSON NULL,
  `addons`          JSON NULL,
  `addon_label`     VARCHAR(255) NULL,
  `created_at`      TIMESTAMP NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL PEMESANAN ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pemesanan` (
  `id_pemesanan`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_pemesanan`    VARCHAR(255) NOT NULL UNIQUE,
  `id_pelanggan`      BIGINT UNSIGNED NOT NULL,
  `tgl_pemesanan`     DATETIME NOT NULL,
  `tgl_pelaksanaan`   DATETIME NOT NULL,
  `waktu_pelaksanaan` VARCHAR(20) NULL,
  `total_harga`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status_pesanan`    VARCHAR(50) NOT NULL DEFAULT 'menunggu',
  `nama_pic`          VARCHAR(255) NULL,
  `telepon_pic`       VARCHAR(30) NULL,
  `perusahaan`        VARCHAR(255) NULL,
  `catatan`           TEXT NULL,
  `created_at`        TIMESTAMP NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_pemesanan`),
  CONSTRAINT `pemesanan_id_pelanggan_foreign` FOREIGN KEY (`id_pelanggan`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL DETAIL PEMESANAN ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `detail_pemesanan` (
  `id_detail`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pemesanan` BIGINT UNSIGNED NOT NULL,
  `id_jasa`      BIGINT UNSIGNED NOT NULL,
  `paket_id`     VARCHAR(50) NULL,
  `paket_label`  VARCHAR(255) NULL,
  `addons`       JSON NULL,
  `kuantitas`    INT NOT NULL DEFAULT 1,
  `subtotal`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`   TIMESTAMP NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  CONSTRAINT `detail_id_pemesanan_foreign` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`) ON DELETE CASCADE,
  CONSTRAINT `detail_id_jasa_foreign`      FOREIGN KEY (`id_jasa`)      REFERENCES `jasa` (`id_jasa`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL PEMBAYARAN ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pembayaran` (
  `id_pembayaran`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pemesanan`           BIGINT UNSIGNED NOT NULL,
  `tgl_bayar`              DATETIME NULL,
  `metode_bayar`           VARCHAR(50) NULL,
  `bukti_transfer`         VARCHAR(500) NULL,
  `status_verifikasi`      VARCHAR(50) NOT NULL DEFAULT 'pending',
  `jumlah`                 DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `midtrans_order_id`      VARCHAR(255) NULL UNIQUE,
  `midtrans_transaction_id`VARCHAR(255) NULL,
  `midtrans_snap_token`    VARCHAR(500) NULL,
  `midtrans_payment_type`  VARCHAR(100) NULL,
  `midtrans_fraud_status`  VARCHAR(50) NULL,
  `midtrans_response`      JSON NULL,
  `created_at`             TIMESTAMP NULL DEFAULT NULL,
  `updated_at`             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran`),
  CONSTRAINT `pembayaran_id_pemesanan_foreign` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABEL PERSONAL ACCESS TOKENS (Sanctum) ──────────────────
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id`   BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(255) NOT NULL,
  `token`          VARCHAR(64) NOT NULL UNIQUE,
  `abilities`      TEXT NULL,
  `last_used_at`   TIMESTAMP NULL,
  `expires_at`     TIMESTAMP NULL,
  `created_at`     TIMESTAMP NULL DEFAULT NULL,
  `updated_at`     TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- DATA AWAL (SEEDER)
-- ════════════════════════════════════════════════════════════

-- ── User Admin (password: admin123) ─────────────────────────
INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `no_telp`, `created_at`, `updated_at`) VALUES
(1, 'Admin IMA', 'admin@ima.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', NOW(), NOW());

INSERT INTO `admin` (`id_user`, `role_level`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', NOW(), NOW());

-- ── User Pelanggan Demo (password: password) ─────────────────
INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `no_telp`, `created_at`, `updated_at`) VALUES
(2, 'Budi Santoso', 'budi@demo.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081298765432', NOW(), NOW());

INSERT INTO `pelanggan` (`id_user`, `alamat`, `perusahaan`, `created_at`, `updated_at`) VALUES
(2, 'Jl. Merdeka No. 10, Jakarta', 'PT Demo Indonesia', NOW(), NOW());

-- ── Data Jasa (6 layanan sesuai frontend) ────────────────────
INSERT INTO `jasa` (`id_jasa`, `nama_jasa`, `deskripsi`, `harga`, `status_tersedia`, `icon`, `emoji`, `tag`, `tag_color`, `img_bg`, `features`, `packages`, `addons`, `addon_label`, `created_at`, `updated_at`) VALUES
(1, 'Live Streaming',
 'Produksi live streaming profesional untuk event korporat, konser, konferensi, dan acara khusus. Kami menggunakan peralatan broadcast kelas dunia dengan konfigurasi multi-kamera, switcher profesional, dan distribusi CDN untuk jangkauan nasional.',
 2500000.00, 'tersedia', '📡', '📡', 'Broadcasting', '#1B4FD8',
 'linear-gradient(135deg,#1a2a6c,#1B4FD8 60%,#23d5ab)',
 '["Multi-Camera Setup","CDN Distribution","Real-time Switching","4K Quality","Backup System"]',
 '[{"id":"basic","label":"Paket Basic","hours":"4 Jam","price":2500000,"features":["1 Kamera","1 Operator","Streaming 720p","Rekam File"]},{"id":"standard","label":"Paket Standard","hours":"6 Jam","price":4500000,"features":["2 Kamera","2 Operator","Streaming 1080p","Multi-Platform","Editing Dasar"]},{"id":"premium","label":"Paket Premium","hours":"8 Jam","price":8000000,"features":["Multi-Kamera","Tim Full","4K Streaming","CDN Distribution","Full Editing","Drone"]}]',
 '[{"id":"hd","name":"Kamera HD","desc":"1080p Full HD, cocok untuk indoor","price":500000,"icon":"📷"},{"id":"ptz","name":"Kamera PTZ","desc":"Pan-Tilt-Zoom, kontrol jarak jauh","price":750000,"icon":"🎥"},{"id":"4k","name":"Kamera 4K","desc":"Ultra HD, kualitas cinema profesional","price":1200000,"icon":"🎞️"},{"id":"drone","name":"Drone Aerial","desc":"Pengambilan gambar dari udara","price":1500000,"icon":"🚁"}]',
 'Tambah Kamera', NOW(), NOW()),

(2, 'Zoom Hybrid Meeting',
 'Pengelolaan acara hybrid yang memadukan peserta fisik dan virtual secara seamless. Kami memastikan kualitas audio-visual terbaik di kedua sisi — baik peserta yang hadir di venue maupun yang bergabung secara online melalui berbagai platform.',
 1800000.00, 'tersedia', '💻', '💻', 'Hybrid Event', '#7C3AED',
 'linear-gradient(135deg,#4c1d95,#7c3aed)',
 '["Multi-Platform Support","HD Video Quality","Interactive Q&A","Live Polling","Recording"]',
 '[{"id":"basic","label":"Hybrid Basic","hours":"3 Jam","price":1800000,"features":["1 Host","Zoom Pro","Basic Setup","Recording"]},{"id":"standard","label":"Hybrid Standard","hours":"5 Jam","price":3500000,"features":["2 Operator","HD Quality","Q&A + Polling","Multi-Platform","Basic Editing"]},{"id":"premium","label":"Hybrid Premium","hours":"8 Jam","price":6500000,"features":["Tim Penuh","4K Quality","Multi-Platform","Custom Branding","Full Editing","Analitik"]}]',
 '[{"id":"mic","name":"Mic Wireless","desc":"Set mic wireless tambahan","price":300000,"icon":"🎤"},{"id":"trans","name":"Translator","desc":"Penerjemah simultan","price":2000000,"icon":"🗣️"},{"id":"branding","name":"Custom Branding","desc":"Overlay logo & branding","price":500000,"icon":"🎨"},{"id":"tech","name":"Tech Support","desc":"Operator standby saat acara","price":800000,"icon":"🛠️"}]',
 'Tambah Fasilitas', NOW(), NOW()),

(3, 'Seminar & Workshop',
 'Penyelenggaraan seminar dan workshop skala besar maupun kecil dengan dukungan teknis lengkap. Mulai dari sound system, lighting, layar presentasi, hingga dokumentasi acara secara profesional untuk kebutuhan korporat dan institusi.',
 3500000.00, 'tersedia', '🎤', '🎤', 'Event', '#D97706',
 'linear-gradient(135deg,#92400e,#d97706)',
 '["Sound System Pro","Stage Lighting","Live Documentation","Simultaneous Interpretation","Venue Setup"]',
 '[{"id":"basic","label":"Seminar Basic","hours":"Half Day","price":3500000,"features":["Sound System","1 Mic","Basic Lighting","Dokumentasi Foto"]},{"id":"standard","label":"Seminar Standard","hours":"Full Day","price":6500000,"features":["Sound Pro","3 Mic","Lighting Setup","Foto + Video","LED Screen"]},{"id":"premium","label":"Seminar Premium","hours":"2 Hari","price":12000000,"features":["Full Setup","Wireless Mic","Stage Lighting","Full Dokumentasi","Streaming","Simultaneous Translation"]}]',
 '[{"id":"led","name":"LED Screen","desc":"Layar LED tambahan","price":1500000,"icon":"🖥️"},{"id":"venue","name":"Venue Setup","desc":"Dekorasi panggung","price":2000000,"icon":"🎭"},{"id":"stream","name":"Live Stream","desc":"Streaming event online","price":1800000,"icon":"📡"},{"id":"photo","name":"Fotografer","desc":"Dokumentasi foto tambahan","price":800000,"icon":"📸"}]',
 'Tambah Fasilitas Event', NOW(), NOW()),

(4, 'Video Production',
 'Produksi video kreatif dari hulu ke hilir — konsep kreatif, pre-production, shooting multi-kamera, hingga post-production lengkap dengan color grading, motion graphics, dan mixing audio profesional untuk konten komersial, iklan, dan dokumentasi perusahaan.',
 3000000.00, 'tersedia', '🎬', '🎬', 'Production', '#DC2626',
 'linear-gradient(135deg,#7f1d1d,#dc2626)',
 '["Creative Concept","4K Shooting","Color Grading","Motion Graphics","Aerial Drone"]',
 '[{"id":"basic","label":"Video Basic","hours":"1 Hari","price":3000000,"features":["1 Kamera","Editing Dasar","Full HD","2 Revisi"]},{"id":"standard","label":"Video Standard","hours":"2 Hari","price":6500000,"features":["2 Kamera","Editing Pro","4K Output","Color Grading","4 Revisi"]},{"id":"premium","label":"Video Premium","hours":"3 Hari","price":12000000,"features":["Multi-Kamera","Full Post-Pro","4K+Motion","Drone","Unlimited Revisi"]}]',
 '[{"id":"drone","name":"Drone Aerial","desc":"Pengambilan gambar udara","price":1500000,"icon":"🚁"},{"id":"motion","name":"Motion Graphic","desc":"Animasi & motion graphic","price":2000000,"icon":"✨"},{"id":"vo","name":"Voice-Over","desc":"Talent voice-over","price":700000,"icon":"🎙️"},{"id":"color","name":"Color Grading","desc":"Color grading profesional","price":1000000,"icon":"🎨"}]',
 'Tambah Produksi', NOW(), NOW()),

(5, 'Perekaman Audio',
 'Layanan rekaman audio profesional untuk kebutuhan podcast, voice-over iklan, rekaman musik, dan konten digital. Studio rekaman kami dilengkapi dengan peralatan audio terkini dan engineer berpengalaman untuk menghasilkan kualitas suara terbaik.',
 1500000.00, 'tersedia', '🎙️', '🎙️', 'Audio', '#059669',
 'linear-gradient(135deg,#064e3b,#059669)',
 '["Studio Recording","Voice-Over","Podcast Production","Audio Mixing","Sound Design"]',
 '[{"id":"basic","label":"Audio Basic","hours":"3 Jam Studio","price":1500000,"features":["Studio Rekam","Mixing Dasar","2 Revisi","WAV+MP3"]},{"id":"standard","label":"Audio Standard","hours":"6 Jam Studio","price":2800000,"features":["Studio Pro","Mixing+Master","4 Revisi","Multi-Format"]},{"id":"premium","label":"Audio Premium","hours":"Full Day","price":5000000,"features":["Studio Premium","Full Post-Pro","Unlimited Revisi","All Format","Distribusi"]}]',
 '[{"id":"mix","name":"Audio Mixing","desc":"Mix & master profesional","price":500000,"icon":"🎚️"},{"id":"vo","name":"Voice-Over","desc":"Talent voice-over profesional","price":700000,"icon":"🎙️"},{"id":"fx","name":"Sound Design","desc":"Efek suara & atmosfer","price":600000,"icon":"🔉"},{"id":"score","name":"Music Scoring","desc":"Musik latar original","price":1500000,"icon":"🎵"}]',
 'Tambah Layanan Audio', NOW(), NOW()),

(6, 'Event Management',
 'Manajemen event end-to-end — dari perencanaan konsep, koordinasi vendor, dekorasi, hingga pelaksanaan dan evaluasi pasca acara. Tim berpengalaman kami memastikan setiap detail terlaksana sempurna untuk berbagai skala acara korporat dan privat.',
 5000000.00, 'tersedia', '🎪', '🎪', 'Full Service', '#BE185D',
 'linear-gradient(135deg,#831843,#be185d)',
 '["Concept Planning","Vendor Coordination","Stage Design","On-site Management","Post-Event Report"]',
 '[{"id":"basic","label":"Event Basic","hours":"Half Day","price":5000000,"features":["Koordinasi","Vendor Mgmt","1 PIC","Basic Setup"]},{"id":"standard","label":"Event Standard","hours":"Full Day","price":10000000,"features":["Full Koordinasi","Multi-Vendor","Tim Penuh","Dekorasi Dasar","Evaluasi"]},{"id":"premium","label":"Event Premium","hours":"Multi-Day","price":20000000,"features":["End-to-End","Semua Vendor","Tim Besar","Full Dekor","Dokumentasi","Laporan"]}]',
 '[{"id":"deco","name":"Dekorasi","desc":"Dekorasi panggung & venue","price":2000000,"icon":"🎨"},{"id":"catering","name":"Catering","desc":"Konsumsi peserta tamu","price":150000,"icon":"🍽️"},{"id":"mc","name":"MC Profesional","desc":"Master of Ceremony berpengalaman","price":1500000,"icon":"🎤"},{"id":"photo","name":"Foto & Video","desc":"Dokumentasi profesional","price":1000000,"icon":"📸"}]',
 'Tambah Layanan Event', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════
-- SELESAI
-- Login Admin  : admin@ima.test  / admin123
-- Login Demo   : budi@demo.test  / password
-- ════════════════════════════════════════════════════════════
