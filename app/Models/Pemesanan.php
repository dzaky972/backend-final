<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pemesanan extends Model
{
    protected $table = 'pemesanan';
    protected $primaryKey = 'id_pemesanan';

    protected $fillable = [
        'kode_pemesanan',
        'id_pelanggan',
        'tgl_pemesanan',
        'tgl_pelaksanaan',
        'waktu_pelaksanaan',
        'total_harga',
        'status_pesanan',
        'nama_pic',
        'telepon_pic',
        'perusahaan',
        'catatan',
    ];

    protected $casts = [
        'tgl_pemesanan'   => 'datetime',
        'tgl_pelaksanaan' => 'datetime',
        'total_harga'     => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_pelanggan', 'id_user');
    }

    public function details()
    {
        return $this->hasMany(DetailPemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function hitungTotal(): float
    {
        return $this->details->sum('subtotal');
    }

    public function updateStatus(string $status): bool
    {
        $this->status_pesanan = $status;
        return $this->save();
    }
}
