<?php  // app/Models/RecaptchaBlockedIp.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecaptchaBlockedIp extends Model
{
    protected $fillable = ['ip', 'attempts', 'metadata', 'last_attempt_at', 'blocked_at'];

    protected $casts = [
        'metadata' => 'array',
        'last_attempt_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }
}
