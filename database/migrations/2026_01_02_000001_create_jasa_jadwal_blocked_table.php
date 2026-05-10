<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jasa_jadwal_blocked', function (Blueprint $table) {
            $table->id('id_blocked');
            $table->unsignedBigInteger('id_jasa');
            $table->date('tanggal');
            $table->string('alasan')->nullable(); // contoh: "Maintenance", "Tim Cuti"
            $table->unsignedBigInteger('blocked_by')->nullable(); // admin yang block
            $table->timestamps();

            $table->foreign('id_jasa')->references('id_jasa')->on('jasa')->onDelete('cascade');
            $table->foreign('blocked_by')->references('id_user')->on('users')->onDelete('set null');
            $table->unique(['id_jasa', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jasa_jadwal_blocked');
    }
};
