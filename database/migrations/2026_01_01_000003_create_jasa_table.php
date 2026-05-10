<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jasa', function (Blueprint $table) {
            $table->id('id_jasa');
            $table->string('nama_jasa');
            $table->text('deskripsi');
            $table->decimal('harga', 15, 2)->default(0);
            $table->string('status_tersedia')->default('tersedia');
            // Field tambahan untuk mendukung tampilan frontend
            $table->string('icon')->nullable();
            $table->string('emoji')->nullable();
            $table->string('tag')->nullable();
            $table->string('tag_color')->nullable();
            $table->string('img_bg')->nullable();
            $table->json('features')->nullable();
            $table->json('packages')->nullable();
            $table->json('addons')->nullable();
            $table->string('addon_label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jasa');
    }
};
