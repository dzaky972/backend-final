<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `gambar` (nullable) ke tabel jasa & portofolio.
 * Kolom menyimpan PATH relatif file (mis. 'uploads/jasa/abc123.jpg'),
 * BUKAN URL lengkap. URL dibentuk di controller via asset('storage/'.$path).
 *
 * Pengaturan beranda (hero image) tidak butuh migrasi terpisah —
 * sudah otomatis didukung tabel `pengaturan` (kunci = 'hero_image').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jasa', function (Blueprint $table) {
            $table->string('gambar')->nullable()->after('img_bg');
        });

        Schema::table('portofolio', function (Blueprint $table) {
            $table->string('gambar')->nullable()->after('img_bg');
        });
    }

    public function down(): void
    {
        Schema::table('jasa', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });

        Schema::table('portofolio', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });
    }
};
