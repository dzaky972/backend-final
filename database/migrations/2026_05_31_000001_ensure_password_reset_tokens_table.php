<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ini HANYA dijalankan jika tabel password_reset_tokens
 * BELUM ADA di database. Cek dulu di phpMyAdmin.
 *
 * Laravel 10+ biasanya sudah punya tabel ini secara default
 * di migration 0001_01_01_000000_create_users_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu — kalau sudah ada, skip
        if (Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        // Tidak drop karena ini tabel default Laravel
        // Schema::dropIfExists('password_reset_tokens');
    }
};
