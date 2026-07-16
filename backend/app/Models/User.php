<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'google_id', 'avatar_url', 'last_login_at', 'last_login_ip_hash', 'is_active'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime', 'is_active' => 'boolean'];
    }
}
