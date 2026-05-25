<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jasa extends Model
{
    protected $table = 'jasa';
    protected $primaryKey = 'id_jasa';

    protected $fillable = [
        'nama_jasa',
        'deskripsi',
        'harga',
        'status_tersedia',
        'icon',
        'emoji',
        'tag',
        'tag_color',
        'img_bg',
        'gambar',          // ← BARU: path file gambar (nullable)
        'features',
        'packages',
        'addons',
        'addon_label',
    ];

    protected $casts = [
        'features' => 'array',
        'packages' => 'array',
        'addons'   => 'array',
        'harga'    => 'decimal:2',
    ];

    public function detailPemesanan()
    {
        return $this->hasMany(DetailPemesanan::class, 'id_jasa', 'id_jasa');
    }
}