<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'id_pemesanan',
        'tgl_bayar',
        'metode_bayar',
        'bukti_transfer',
        'status_verifikasi',
        'jumlah',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_snap_token',
        'midtrans_payment_type',
        'midtrans_fraud_status',
        'midtrans_response',
    ];

    protected $casts = [
        'tgl_bayar'         => 'datetime',
        'jumlah'            => 'decimal:2',
        'midtrans_response' => 'array',
    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }
}
