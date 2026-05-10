<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->id('id_pemesanan');
            $table->string('kode_pemesanan')->unique(); // Contoh: IMA-10234
            $table->unsignedBigInteger('id_pelanggan');
            $table->dateTime('tgl_pemesanan');
            $table->dateTime('tgl_pelaksanaan');
            $table->string('waktu_pelaksanaan')->nullable(); // jam mulai (ex: 09:00)
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->string('status_pesanan')->default('menunggu'); // menunggu, proses, selesai, batal
            $table->string('nama_pic')->nullable();
            $table->string('telepon_pic')->nullable();
            $table->string('perusahaan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('id_pelanggan')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemesanan');
    }
};
