<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_telp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id_user', 'id_user');
    }

    public function pelanggan()
    {
        return $this->hasOne(Pelanggan::class, 'id_user', 'id_user');
    }

    public function pemesanan()
    {
        return $this->hasMany(Pemesanan::class, 'id_pelanggan', 'id_user');
    }

    /**
     * Cek admin tanpa query tambahan jika `admin` relasi sudah loaded.
     * RULE: User adalah ADMIN jika punya record `admin`. Admin TIDAK bisa memesan.
     */
    public function isAdmin(): bool
    {
        if ($this->relationLoaded('admin')) {
            return $this->admin !== null;
        }
        return $this->admin()->exists();
    }

    /**
     * Cek pelanggan.
     * RULE STRICT: User adalah PELANGGAN HANYA jika
     *   1. Punya record di tabel `pelanggan`, DAN
     *   2. TIDAK punya record di tabel `admin` (mutually exclusive).
     * Admin tidak boleh memesan walaupun secara teknis bisa punya pelanggan record.
     */
    public function isPelanggan(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }
        if ($this->relationLoaded('pelanggan')) {
            return $this->pelanggan !== null;
        }
        return $this->pelanggan()->exists();
    }

    /**
     * Role string untuk response API.
     */
    public function getRoleAttribute(): string
    {
        if ($this->isAdmin()) return 'admin';
        if ($this->isPelanggan()) return 'pelanggan';
        return 'guest';
    }
}
