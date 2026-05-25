<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portofolio extends Model
{
    protected $table = 'portofolio';
    protected $primaryKey = 'id_portofolio';

    protected $fillable = [
        'judul',
        'deskripsi',
        'kategori',
        'klien',
        'tanggal_proyek',
        'icon',
        'img_bg',
        'gambar',          // ← BARU: path file gambar (nullable)
        'tag',
        'tag_color',
        'is_featured',
        'urutan',
    ];

    protected $casts = [
        'is_featured'    => 'boolean',
        'tanggal_proyek' => 'date',
        'urutan'         => 'integer',
    ];
}