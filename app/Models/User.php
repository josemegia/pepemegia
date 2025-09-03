<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'social_provider_name',
        'social_provider_id',
        'social_provider_token',
        'social_provider_refresh_token',
        'profile_photo_path',
        'address',
        'city',
        'country',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'social_provider_token',
        'social_provider_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasRole($role): bool
    {
        // Esta es la implementación más simple, asumiendo una columna 'role' en tu tabla de usuarios.
        // Por ejemplo, el campo 'role' podría almacenar 'admin', 'user', 'editor', etc.
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
    
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmail);
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return !empty($this->two_factor_secret) &&
               !empty($this->two_factor_confirmed_at);
    }
}