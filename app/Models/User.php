<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $name
 * @property string $username  <-- SUDAH GANTI JADI USERNAME
 * @property string|null $avatar
 * @property string $password
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username', // Ganti email jadi username
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        // email_verified_at dihapus karena kita tidak pakai email lagi
        'password' => 'hashed',
    ];
}