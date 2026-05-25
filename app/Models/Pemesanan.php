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
        'sub_status_pesanan', // ← BARU: dikonfirmasi|persiapan|berlangsung|acara_selesai
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
}