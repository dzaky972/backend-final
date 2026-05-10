<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPemesanan extends Model
{
    protected $table = 'detail_pemesanan';
    protected $primaryKey = 'id_detail';

    protected $fillable = [
        'id_pemesanan',
        'id_jasa',
        'paket_id',
        'paket_label',
        'addons',
        'kuantitas',
        'subtotal',
    ];

    protected $casts = [
        'addons'   => 'array',
        'subtotal' => 'decimal:2',
    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function jasa()
    {
        return $this->belongsTo(Jasa::class, 'id_jasa', 'id_jasa');
    }
}
