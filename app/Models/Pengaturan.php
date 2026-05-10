<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $primaryKey = 'id_pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
        'grup',
        'tipe',
    ];

    /**
     * Helper untuk get value berdasarkan kunci.
     */
    public static function get(string $kunci, $default = null)
    {
        $row = static::where('kunci', $kunci)->first();
        return $row ? $row->nilai : $default;
    }

    /**
     * Helper untuk set value.
     */
    public static function set(string $kunci, $nilai, string $grup = 'umum', string $tipe = 'text')
    {
        return static::updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => $nilai, 'grup' => $grup, 'tipe' => $tipe]
        );
    }
}
