<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'social_provider_token',       // Ocultar tokens sensibles
        'social_provider_refresh_token', // Ocultar tokens sensibles
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Verifica si el usuario tiene un rol específico.
     *
     * @param string|array $role Los roles a verificar (ej. 'admin', ['editor', 'admin'])
     * @return bool
     */
    public function hasRole($role): bool
    {
        // Esta es la implementación más simple, asumiendo una columna 'role' en tu tabla de usuarios.
        // Por ejemplo, el campo 'role' podría almacenar 'admin', 'user', 'editor', etc.
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        return $this->role === $role;
    }

    /**
     * Conveniencia para verificar si el usuario es un administrador.
     * Asume que el rol de administrador se llama 'admin'.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
    
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmail);
    }
}