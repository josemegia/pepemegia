<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlCountry extends Model
{
    protected $table = 'fl_countries';

    protected $fillable = [
        'code', 'name', 'iso_code', 'shop_url',
        'currency_code', 'currency_symbol', 'locale',
        'is_active', 'scrape_method', 'scrape_status',
        'scrape_days', 'last_scraped_at', 'last_error', 'products_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'last_scraped_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function products(): HasMany
    {
        return $this->hasMany(FlProduct::class, 'country_id');
    }

    public function packs(): HasMany
    {
        return $this->hasMany(FlPack::class, 'country_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(FlProductAlias::class, 'country_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(FlConsultation::class, 'country_id');
    }

    public function scrapeLogs(): HasMany
    {
        return $this->hasMany(FlScrapeLog::class, 'country_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNeedsUpdate($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_scraped_at')
                  ->orWhere('last_scraped_at', '<', now()->subDays(13));
            });
    }

    public function scopeByIso($query, string $isoCode)
    {
        return $query->where('iso_code', strtoupper($isoCode));
    }

    // --- Helpers ---

    public function needsUpdate(): bool
    {
        if (is_null($this->last_scraped_at)) {
            return true;
        }

        $scrapeDays = explode(',', $this->scrape_days);
        $today = (int) now()->format('d');
        $lastDay = (int) $this->last_scraped_at->format('d');

        foreach ($scrapeDays as $day) {
            $day = (int) trim($day);
            if ($today >= $day && $lastDay < $day) {
                return true;
            }
        }

        return $this->last_scraped_at->diffInDays(now()) >= 13;
    }
}
