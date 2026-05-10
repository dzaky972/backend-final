<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id('id_pengaturan');
            $table->string('kunci')->unique(); // contoh: hero_title, about_text, dll
            $table->text('nilai')->nullable();
            $table->string('grup')->default('umum'); // beranda, tentang, kontak
            $table->string('tipe')->default('text'); // text, longtext, json
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
