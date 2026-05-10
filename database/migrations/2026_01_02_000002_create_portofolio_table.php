<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portofolio', function (Blueprint $table) {
            $table->id('id_portofolio');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('kategori')->default('Umum');
            $table->string('klien')->nullable();
            $table->date('tanggal_proyek')->nullable();
            $table->string('icon')->nullable();
            $table->string('img_bg')->nullable();
            $table->string('tag')->nullable();
            $table->string('tag_color')->nullable();
            $table->boolean('is_featured')->default(false); // tampil di beranda preview
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portofolio');
    }
};
