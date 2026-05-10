<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pemesanan', function (Blueprint $table) {
            $table->id('id_detail');
            $table->unsignedBigInteger('id_pemesanan');
            $table->unsignedBigInteger('id_jasa');
            $table->string('paket_id')->nullable();        // ex: basic/standard/premium
            $table->string('paket_label')->nullable();     // ex: "Paket Standard"
            $table->json('addons')->nullable();            // list addon terpilih
            $table->integer('kuantitas')->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_pemesanan')->references('id_pemesanan')->on('pemesanan')->onDelete('cascade');
            $table->foreign('id_jasa')->references('id_jasa')->on('jasa')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pemesanan');
    }
};
