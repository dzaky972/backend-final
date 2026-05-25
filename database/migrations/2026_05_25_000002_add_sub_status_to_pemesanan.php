<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `sub_status_pesanan` (nullable) ke tabel pemesanan.
 *
 * Strategi mapping:
 *   status_pesanan='menunggu' → "Menunggu Pembayaran"
 *   status_pesanan='proses' + sub_status_pesanan='dikonfirmasi'   → "Pesanan Dikonfirmasi"
 *   status_pesanan='proses' + sub_status_pesanan='persiapan'      → "Tim Sedang Persiapan"
 *   status_pesanan='proses' + sub_status_pesanan='berlangsung'    → "Acara Sedang Berlangsung"
 *   status_pesanan='proses' + sub_status_pesanan='acara_selesai'  → "Acara Selesai"
 *   status_pesanan='selesai' → "File Dikirim / Pesanan Selesai"
 *   status_pesanan='batal'   → "Dibatalkan"
 *
 * Data lama yang status='proses' otomatis di-backfill dengan sub_status='dikonfirmasi'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->string('sub_status_pesanan', 30)
                  ->nullable()
                  ->after('status_pesanan')
                  ->comment('dikonfirmasi|persiapan|berlangsung|acara_selesai');
        });

        // Backfill: pesanan yang sudah ada di status 'proses' dianggap baru dikonfirmasi
        DB::table('pemesanan')
            ->where('status_pesanan', 'proses')
            ->whereNull('sub_status_pesanan')
            ->update(['sub_status_pesanan' => 'dikonfirmasi']);
    }

    public function down(): void
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->dropColumn('sub_status_pesanan');
        });
    }
};