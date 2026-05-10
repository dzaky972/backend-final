<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JasaJadwalBlocked extends Model
{
    protected $table = 'jasa_jadwal_blocked';
    protected $primaryKey = 'id_blocked';

    protected $fillable = [
        'id_jasa',
        'tanggal',
        'alasan',
        'blocked_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function jasa()
    {
        return $this->belongsTo(Jasa::class, 'id_jasa', 'id_jasa');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'blocked_by', 'id_user');
    }
}
