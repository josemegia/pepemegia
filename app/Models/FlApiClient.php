<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlApiClient extends Model
{
    protected $table = 'fl_api_clients';

    protected $fillable = [
        'name', 'email', 'api_key', 'api_secret',
        'domain', 'plan', 'rate_limit_per_minute',
        'daily_limit', 'monthly_limit', 'is_active',
        'allowed_endpoints', 'metadata'
    ];

    protected $casts = [
        'allowed_endpoints' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['api_secret'];

    public static function generateCredentials(): array
    {
        return [
            'api_key' => 'fl_' . Str::random(48),
            'api_secret' => hash('sha256', Str::random(64)),
        ];
    }

    public function hasReachedDailyLimit(): bool
    {
        return $this->requests_today >= $this->daily_limit;
    }

    public function hasReachedMonthlyLimit(): bool
    {
        return $this->requests_this_month >= $this->monthly_limit;
    }

    public function incrementUsage(): void
    {
        $this->increment('requests_today');
        $this->increment('requests_this_month');
        $this->update(['last_request_at' => now()]);
    }
}
