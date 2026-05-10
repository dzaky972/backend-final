<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'id_admin';
    public $incrementing = false;

    protected $fillable = ['id_admin', 'role_level'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_admin');
    }
}
