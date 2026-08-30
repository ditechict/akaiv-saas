<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'surname', 'email', 'phone', 'role', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    const ROLE_ADMIN = 'Admin';
    const ROLE_USER = 'User';

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
