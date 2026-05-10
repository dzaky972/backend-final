<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Jasa;
use App\Models\Pelanggan;
use App\Models\Pengaturan;
use App\Models\Portofolio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────
        //  ADMIN — HANYA 1 super admin, TIDAK punya record pelanggan
        // ─────────────────────────────────────────────────────
        $admin = User::create([
            'nama'     => 'Super Admin IMA',
            'email'    => 'admin@ima.test',
            'password' => Hash::make('admin123'),
            'no_telp'  => '081234567890',
        ]);
        Admin::create([
            'id_user'    => $admin->id_user,
            'role_level' => 'super_admin',
        ]);
        // CATATAN: Admin TIDAK dibuatkan record di tabel pelanggan.
        // Admin hanya bisa mengelola sistem, tidak bisa memesan.

        // ─────────────────────────────────────────────────────
        //  PELANGGAN DEMO — 3 user untuk demo/testing
        // ─────────────────────────────────────────────────────
        $pelangganDemo = [
            [
                'nama' => 'Budi Santoso', 'email' => 'budi@demo.test',
                'password' => 'password',  'no_telp' => '081298765432',
                'alamat' => 'Jl. Merdeka No. 10, Jakarta Pusat',
                'perusahaan' => 'PT Demo Indonesia',
            ],
            [
                'nama' => 'Siti Nurhaliza', 'email' => 'siti@demo.test',
                'password' => 'password',   'no_telp' => '081345678901',
                'alamat' => 'Jl. Sudirman No. 25, Jakarta Selatan',
                'perusahaan' => 'CV Maju Bersama',
            ],
            [
                'nama' => 'Andi Wijaya', 'email' => 'andi@demo.test',
                'password' => 'password',   'no_telp' => '081387654321',
                'alamat' => 'Jl. Gatot Subroto No. 7, Bandung',
                'perusahaan' => '',
            ],
        ];

        foreach ($pelangganDemo as $p) {
            $user = User::create([
                'nama'     => $p['nama'],
                'email'    => $p['email'],
                'password' => Hash::make($p['password']),
                'no_telp'  => $p['no_telp'],
            ]);
            Pelanggan::create([
                'id_user'    => $user->id_user,
                'alamat'     => $p['alamat'],
                'perusahaan' => $p['perusahaan'],
            ]);
            // CATATAN: Pelanggan TIDAK dibuatkan record admin.
            // Pelanggan hanya bisa memesan, tidak bisa mengelola sistem.
        }

        $this->seedJasa();
        $this->seedPortofolio();
        $this->seedPengaturan();

        $this->command->info('');
        $this->command->info('✅ Database seeded berhasil!');
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  AKUN ADMIN (akses /admin):');
        $this->command->info('  • admin@ima.test / admin123 (super_admin)');
        $this->command->info('');
        $this->command->info('  AKUN PELANGGAN (akses /):');
        $this->command->info('  • budi@demo.test / password');
        $this->command->info('  • siti@demo.test / password');
        $this->command->info('  • andi@demo.test / password');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    private function seedJasa(): void
    {
        $jasaData = [
            [
                'nama_jasa' => 'Live Streaming',
                'deskripsi' => 'Produksi live streaming profesional untuk event korporat, konser, konferensi, dan acara khusus. Kami menggunakan peralatan broadcast kelas dunia dengan konfigurasi multi-kamera, switcher profesional, dan distribusi CDN untuk jangkauan nasional.',
                'harga'     => 2500000,
                'icon'      => '📡',
                'emoji'     => '📡',
                'tag'       => 'Broadcasting',
                'tag_color' => '#1B4FD8',
                'img_bg'    => 'linear-gradient(135deg,#1a2a6c,#1B4FD8 60%,#23d5ab)',
                'features'  => ['Multi-Camera Setup','CDN Distribution','Real-time Switching','4K Quality','Backup System'],
                'packages'  => [
                    ['id' => 'basic',    'label' => 'Paket Basic',    'hours' => '4 Jam',    'price' => 2500000, 'features' => ['1 Kamera','1 Operator','Streaming 720p','Rekam File']],
                    ['id' => 'standard', 'label' => 'Paket Standard', 'hours' => '8 Jam',    'price' => 4500000, 'features' => ['2 Kamera','2 Operator','Streaming 1080p','Multi-Platform','Rekam File']],
                    ['id' => 'premium',  'label' => 'Paket Premium',  'hours' => 'Full Day', 'price' => 8000000, 'features' => ['4 Kamera','Tim Penuh','Streaming 4K','Multi-Platform','Rekam+Edit','Grafis Overlay']],
                ],
                'addons' => [
                    ['id' => 'hd',    'name' => 'Kamera HD',    'desc' => '1080p Full HD, cocok untuk indoor',     'price' => 500000,  'icon' => '📷'],
                    ['id' => 'ptz',   'name' => 'Kamera PTZ',   'desc' => 'Pan-Tilt-Zoom, kontrol jarak jauh',     'price' => 750000,  'icon' => '🎥'],
                    ['id' => '4k',    'name' => 'Kamera 4K',    'desc' => 'Ultra HD, kualitas cinema profesional', 'price' => 1200000, 'icon' => '🎞️'],
                    ['id' => 'drone', 'name' => 'Drone Aerial', 'desc' => 'Pengambilan gambar dari udara',          'price' => 1500000, 'icon' => '🚁'],
                ],
                'addon_label' => 'Tambah Kamera',
            ],
            [
                'nama_jasa' => 'Zoom Hybrid Meeting',
                'deskripsi' => 'Pengelolaan acara hybrid yang memadukan peserta fisik dan virtual secara seamless. Kami memastikan kualitas audio-visual terbaik di kedua sisi.',
                'harga'     => 1800000,
                'icon'      => '💻',
                'emoji'     => '💻',
                'tag'       => 'Hybrid Event',
                'tag_color' => '#7C3AED',
                'img_bg'    => 'linear-gradient(135deg,#4c1d95,#7c3aed)',
                'features'  => ['Multi-Platform Support','HD Video Quality','Interactive Q&A','Live Polling','Recording'],
                'packages'  => [
                    ['id' => 'basic',    'label' => 'Hybrid Basic',    'hours' => '3 Jam',    'price' => 1800000, 'features' => ['50 Peserta Online','1 Operator','1 Kamera','Zoom']],
                    ['id' => 'standard', 'label' => 'Hybrid Standard', 'hours' => '6 Jam',    'price' => 3500000, 'features' => ['200 Peserta','2 Operator','2 Kamera','Multi-Platform','Rekam']],
                    ['id' => 'premium',  'label' => 'Hybrid Premium',  'hours' => 'Full Day', 'price' => 6500000, 'features' => ['Unlimited Peserta','Tim Penuh','4 Kamera','All Platform','Rekam+Edit']],
                ],
                'addons' => [
                    ['id' => 'platform', 'name' => 'Multi-Platform', 'desc' => 'YouTube + Zoom + Teams sekaligus', 'price' => 500000,  'icon' => '🖥️'],
                    ['id' => 'record',   'name' => 'Full Recording', 'desc' => 'Rekaman HD seluruh sesi',           'price' => 350000,  'icon' => '🎞️'],
                    ['id' => 'polling',  'name' => 'Live Polling',   'desc' => 'Interaktif polling peserta',         'price' => 250000,  'icon' => '📊'],
                    ['id' => 'interp',   'name' => 'Interpreter',    'desc' => 'Penerjemah simultan',               'price' => 800000,  'icon' => '🗣️'],
                ],
                'addon_label' => 'Tambah Fitur Hybrid',
            ],
            [
                'nama_jasa' => 'Seminar & Workshop',
                'deskripsi' => 'Penyelenggaraan seminar dan workshop dengan dukungan teknis lengkap — sound system, lighting, layar presentasi, dan dokumentasi profesional.',
                'harga'     => 2000000,
                'icon'      => '🎤',
                'emoji'     => '🎤',
                'tag'       => 'Event',
                'tag_color' => '#D97706',
                'img_bg'    => 'linear-gradient(135deg,#92400e,#d97706)',
                'features'  => ['Sound System Pro','Stage Lighting','Live Documentation','Simultaneous Interpretation','Venue Setup'],
                'packages'  => [
                    ['id' => 'basic',    'label' => 'Seminar Basic',    'hours' => '4 Jam',    'price' => 2000000, 'features' => ['Sound System','1 Mic','Layar Presentasi','1 Operator']],
                    ['id' => 'standard', 'label' => 'Seminar Standard', 'hours' => '8 Jam',    'price' => 4000000, 'features' => ['Sound Pro','4 Mic','LED Screen','Lighting','2 Operator']],
                    ['id' => 'premium',  'label' => 'Seminar Premium',  'hours' => 'Full Day', 'price' => 7500000, 'features' => ['Full Production','Tim Penuh','LED Besar','Lighting Show','Dokumentasi']],
                ],
                'addons' => [
                    ['id' => 'sound',  'name' => 'Sound System Pro', 'desc' => 'Speaker + mixer profesional',  'price' => 600000,  'icon' => '🔊'],
                    ['id' => 'light',  'name' => 'Stage Lighting',   'desc' => 'Lighting dekoratif',           'price' => 800000,  'icon' => '💡'],
                    ['id' => 'screen', 'name' => 'LED Screen Besar', 'desc' => 'Layar LED 4×3 meter',          'price' => 1200000, 'icon' => '📺'],
                    ['id' => 'doc',    'name' => 'Dokumentasi',      'desc' => 'Foto + video dokumenter',      'price' => 500000,  'icon' => '📸'],
                ],
                'addon_label' => 'Tambah Peralatan Seminar',
            ],
            [
                'nama_jasa' => 'Video Production',
                'deskripsi' => 'Produksi video kreatif dari hulu ke hilir — konsep kreatif, pre-production, shooting multi-kamera, hingga post-production lengkap dengan color grading dan motion graphics.',
                'harga'     => 3500000,
                'icon'      => '🎬',
                'emoji'     => '🎬',
                'tag'       => 'Production',
                'tag_color' => '#DC2626',
                'img_bg'    => 'linear-gradient(135deg,#7f1d1d,#dc2626)',
                'features'  => ['Creative Concept','4K Shooting','Color Grading','Motion Graphics','Aerial Drone'],
                'packages'  => [
                    ['id' => 'basic',    'label' => 'Video Basic',    'hours' => '1 Hari', 'price' => 3500000,  'features' => ['1 Kamera','Editing Dasar','Full HD','2 Revisi']],
                    ['id' => 'standard', 'label' => 'Video Standard', 'hours' => '2 Hari', 'price' => 6500000,  'features' => ['2 Kamera','Editing Pro','4K Output','Color Grading','4 Revisi']],
                    ['id' => 'premium',  'label' => 'Video Premium',  'hours' => '3 Hari', 'price' => 12000000, 'features' => ['Multi-Kamera','Full Post-Pro','4K+Motion','Drone','Unlimited Revisi']],
                ],
                'addons' => [
                    ['id' => 'drone',  'name' => 'Aerial Drone',     'desc' => 'Shot dari udara 4K',           'price' => 1500000, 'icon' => '🚁'],
                    ['id' => 'grade',  'name' => 'Color Grading',    'desc' => 'Warna cinematic profesional',  'price' => 800000,  'icon' => '🎨'],
                    ['id' => 'motion', 'name' => 'Motion Graphics',  'desc' => 'Animasi & grafis bergerak',    'price' => 1000000, 'icon' => '✨'],
                    ['id' => 'actor',  'name' => 'Talent/Aktor',     'desc' => 'Talent profesional on-camera', 'price' => 1200000, 'icon' => '🎭'],
                ],
                'addon_label' => 'Tambah Produksi',
            ],
            [
                'nama_jasa' => 'Perekaman Audio',
                'deskripsi' => 'Layanan rekaman audio profesional untuk podcast, voice-over iklan, rekaman musik, dan konten digital di studio dengan engineer berpengalaman.',
                'harga'     => 1500000,
                'icon'      => '🎙️',
                'emoji'     => '🎙️',
                'tag'       => 'Audio',
                'tag_color' => '#059669',
                'img_bg'    => 'linear-gradient(135deg,#064e3b,#059669)',
                'features'  => ['Studio Recording','Voice-Over','Podcast Production','Audio Mixing','Sound Design'],
                'packages'  => [
                    ['id' => 'basic',    'label' => 'Audio Basic',    'hours' => '3 Jam Studio', 'price' => 1500000, 'features' => ['Studio Rekam','Mixing Dasar','2 Revisi','WAV+MP3']],
                    ['id' => 'standard', 'label' => 'Audio Standard', 'hours' => '6 Jam Studio', 'price' => 2800000, 'features' => ['Studio Pro','Mixing+Master','4 Revisi','Multi-Format']],
                    ['id' => 'premium',  'label' => 'Audio Premium',  'hours' => 'Full Day',     'price' => 5000000, 'features' => ['Studio Premium','Full Post-Pro','Unlimited Revisi','All Format','Distribusi']],
                ],
                'addons' => [
                    ['id' => 'mix',   'name' => 'Audio Mixing',  'desc' => 'Mix & master profesional',     'price' => 500000,  'icon' => '🎚️'],
                    ['id' => 'vo',    'name' => 'Voice-Over',    'desc' => 'Talent voice-over profesional', 'price' => 700000,  'icon' => '🎙️'],
                    ['id' => 'fx',    'name' => 'Sound Design',  'desc' => 'Efek suara & atmosfer',         'price' => 600000,  'icon' => '🔉'],
                    ['id' => 'score', 'name' => 'Music Scoring', 'desc' => 'Musik latar original',          'price' => 1500000, 'icon' => '🎵'],
                ],
                'addon_label' => 'Tambah Layanan Audio',
            ],
            [
                'nama_jasa' => 'Event Management',
                'deskripsi' => 'Manajemen event end-to-end — perencanaan konsep, koordinasi vendor, dekorasi, hingga pelaksanaan dan evaluasi pasca acara.',
                'harga'     => 5000000,
                'icon'      => '🎪',
                'emoji'     => '🎪',
                'tag'       => 'Full Service',
                'tag_color' => '#BE185D',
                'img_bg'    => 'linear-gradient(135deg,#831843,#be185d)',
                'features'  => ['Concept Planning','Vendor Coordination','Stage Design','On-site Management','Post-Event Report'],
                'packages'  => [
                    ['id' => 'basic',    'label' => 'Event Basic',    'hours' => 'Half Day',  'price' => 5000000,  'features' => ['Koordinasi','Vendor Mgmt','1 PIC','Basic Setup']],
                    ['id' => 'standard', 'label' => 'Event Standard', 'hours' => 'Full Day',  'price' => 10000000, 'features' => ['Full Koordinasi','Multi-Vendor','Tim Penuh','Dekorasi Dasar','Evaluasi']],
                    ['id' => 'premium',  'label' => 'Event Premium',  'hours' => 'Multi-Day', 'price' => 20000000, 'features' => ['End-to-End','Semua Vendor','Tim Besar','Full Dekor','Dokumentasi','Laporan']],
                ],
                'addons' => [
                    ['id' => 'deco',     'name' => 'Dekorasi',        'desc' => 'Dekorasi panggung & venue',       'price' => 2000000, 'icon' => '🎨'],
                    ['id' => 'catering', 'name' => 'Catering',        'desc' => 'Konsumsi peserta tamu',           'price' => 150000,  'icon' => '🍽️'],
                    ['id' => 'mc',       'name' => 'MC Profesional',  'desc' => 'Master of Ceremony berpengalaman','price' => 1500000, 'icon' => '🎤'],
                    ['id' => 'photo',    'name' => 'Foto & Video',    'desc' => 'Dokumentasi profesional',         'price' => 1000000, 'icon' => '📸'],
                ],
                'addon_label' => 'Tambah Layanan Event',
            ],
        ];

        foreach ($jasaData as $j) {
            Jasa::create(array_merge($j, ['status_tersedia' => 'tersedia']));
        }
    }

    private function seedPortofolio(): void
    {
        $items = [
            ['judul' => 'Konser Live Musik Tahunan',     'kategori' => 'Live Streaming', 'klien' => 'Universal Music',      'icon' => '🎵', 'tag' => 'BROADCASTING', 'tag_color' => '#1B4FD8', 'img_bg' => 'linear-gradient(135deg,#1a2a6c,#1B4FD8 60%,#23d5ab)', 'is_featured' => true,  'urutan' => 1, 'deskripsi' => 'Live streaming konser musik dengan multi-camera setup dan distribusi nasional.'],
            ['judul' => 'Webinar Hybrid Internasional',  'kategori' => 'Hybrid Event',   'klien' => 'PT Telkom Indonesia',  'icon' => '💻', 'tag' => 'HYBRID',      'tag_color' => '#7C3AED', 'img_bg' => 'linear-gradient(135deg,#4c1d95,#7c3aed)',                  'is_featured' => true,  'urutan' => 2, 'deskripsi' => 'Penyelenggaraan webinar dengan peserta on-site dan online dari 5 negara.'],
            ['judul' => 'Seminar Nasional Pendidikan',   'kategori' => 'Seminar',        'klien' => 'Kementerian Pendidikan','icon' => '🎓', 'tag' => 'SEMINAR',     'tag_color' => '#D97706', 'img_bg' => 'linear-gradient(135deg,#92400e,#d97706)',                  'is_featured' => false, 'urutan' => 3, 'deskripsi' => 'Seminar nasional dengan peserta 1000+ orang.'],
            ['judul' => 'Video Profil Perusahaan BUMN',  'kategori' => 'Video Production','klien' => 'PT Pertamina',         'icon' => '📹', 'tag' => 'PRODUCTION',  'tag_color' => '#DC2626', 'img_bg' => 'linear-gradient(135deg,#7f1d1d,#dc2626)',                  'is_featured' => false, 'urutan' => 4, 'deskripsi' => 'Produksi video corporate profile dengan kualitas sinematik 4K.'],
            ['judul' => 'Podcast Series Korporat',       'kategori' => 'Audio',          'klien' => 'Bank Mandiri',         'icon' => '🎙️', 'tag' => 'AUDIO',       'tag_color' => '#059669', 'img_bg' => 'linear-gradient(135deg,#064e3b,#059669)',                  'is_featured' => false, 'urutan' => 5, 'deskripsi' => 'Produksi podcast series 12 episode untuk konten internal.'],
            ['judul' => 'Grand Opening Mall',            'kategori' => 'Event Management','klien' => 'Lippo Group',          'icon' => '🎪', 'tag' => 'EVENT',       'tag_color' => '#BE185D', 'img_bg' => 'linear-gradient(135deg,#831843,#be185d)',                  'is_featured' => false, 'urutan' => 6, 'deskripsi' => 'Manajemen end-to-end grand opening mall di Jakarta.'],
        ];

        foreach ($items as $item) {
            Portofolio::create($item);
        }
    }

    private function seedPengaturan(): void
    {
        $defaults = [
            // Beranda — Hero Section
            ['kunci' => 'hero_label',       'nilai' => 'PRODUCTION HOUSE TERPERCAYA', 'grup' => 'beranda', 'tipe' => 'text'],
            ['kunci' => 'hero_title',       'nilai' => 'Solusi Kreatif & Teknologi untuk Bisnis Anda', 'grup' => 'beranda', 'tipe' => 'longtext'],
            ['kunci' => 'hero_subtitle',    'nilai' => 'PT. IMA Creative Production bergerak di bidang IT, Multimedia, dan Teknologi. Kami hadir memberikan kontribusi nyata melalui kreasi inovatif dan integrasi teknologi informasi.', 'grup' => 'beranda', 'tipe' => 'longtext'],

            // Beranda — Stats
            ['kunci' => 'stat_klien',       'nilai' => '200+',  'grup' => 'beranda', 'tipe' => 'text'],
            ['kunci' => 'stat_proyek',      'nilai' => '500+',  'grup' => 'beranda', 'tipe' => 'text'],
            ['kunci' => 'stat_pengalaman',  'nilai' => '10+',   'grup' => 'beranda', 'tipe' => 'text'],
            ['kunci' => 'stat_kota',        'nilai' => '15+',   'grup' => 'beranda', 'tipe' => 'text'],

            // Beranda — About
            ['kunci' => 'about_title',      'nilai' => 'Inovasi & Kreativitas Tanpa Batas', 'grup' => 'beranda', 'tipe' => 'text'],
            ['kunci' => 'about_text_1',     'nilai' => 'PT. IMA Creative Production adalah perusahaan yang bergerak di bidang IT, Information and Technology, Multimedia dan berbagai perdagangan besar beberapa jenis barang.', 'grup' => 'beranda', 'tipe' => 'longtext'],
            ['kunci' => 'about_text_2',     'nilai' => 'Dengan tujuan memberikan kontribusi sosial dan budaya yang berharga bagi kesejahteraan bangsa melalui kreasi inovatif dan integrasi teknologi informasi, media, dan telekomunikasi.', 'grup' => 'beranda', 'tipe' => 'longtext'],

            // Director Quote
            ['kunci' => 'director_quote',   'nilai' => 'Saya pikir aturan bisnis yang sederhana adalah, jika Anda melakukan hal-hal yang lebih mudah terlebih dahulu, maka Anda benar-benar dapat membuat kemajuan.', 'grup' => 'beranda', 'tipe' => 'longtext'],
            ['kunci' => 'director_name',    'nilai' => 'Direktur Utama, PT. IMA Creative Production', 'grup' => 'beranda', 'tipe' => 'text'],

            // Kontak
            ['kunci' => 'kontak_email',     'nilai' => 'info@imacreative.id',     'grup' => 'kontak', 'tipe' => 'text'],
            ['kunci' => 'kontak_telp',      'nilai' => '+62 21 1234 5678',         'grup' => 'kontak', 'tipe' => 'text'],
            ['kunci' => 'kontak_alamat',    'nilai' => 'Jakarta, Indonesia',       'grup' => 'kontak', 'tipe' => 'text'],
        ];

        foreach ($defaults as $d) {
            Pengaturan::updateOrCreate(['kunci' => $d['kunci']], $d);
        }
    }
}
