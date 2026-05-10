<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->unsignedBigInteger('id_pemesanan');
            $table->dateTime('tgl_bayar')->nullable();
            $table->string('metode_bayar')->nullable();      // transfer, va, qris, midtrans
            $table->string('bukti_transfer')->nullable();    // path file upload (manual)
            $table->string('status_verifikasi')->default('pending'); // pending, settlement, success, failed, expired, cancel
            $table->decimal('jumlah', 15, 2)->default(0);

            // Field khusus Midtrans
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_snap_token')->nullable();
            $table->string('midtrans_payment_type')->nullable();
            $table->string('midtrans_fraud_status')->nullable();
            $table->json('midtrans_response')->nullable();

            $table->timestamps();

            $table->foreign('id_pemesanan')->references('id_pemesanan')->on('pemesanan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
