<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlScrapeLog extends Model
{
    protected $table = 'fl_scrape_logs';

    protected $fillable = [
        'country_id', 'method', 'status', 'url',
        'products_found', 'products_created',
        'products_updated', 'products_missing',
        'duration_ms', 'error_message', 'response_code',
    ];

    // --- Relaciones ---

    public function country(): BelongsTo
    {
        return $this->belongsTo(FlCountry::class, 'country_id');
    }

    // --- Scopes ---

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
