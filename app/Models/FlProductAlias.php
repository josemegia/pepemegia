<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlProductAlias extends Model
{
    protected $table = 'fl_product_aliases';

    protected $fillable = [
        'reference_id', 'country_id', 'product_id',
        'local_name', 'match_confidence', 'composition_notes', 'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'is_confirmed' => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function reference(): BelongsTo
    {
        return $this->belongsTo(FlProductReference::class, 'reference_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(FlCountry::class, 'country_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FlProduct::class, 'product_id');
    }

    // --- Scopes ---

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('product_id');
    }

    /**
     * Busca un alias por nombre local (para auto-vincular tras scraping).
     */
    public function scopeMatchName($query, string $name, int $countryId)
    {
        return $query->where('country_id', $countryId)
            ->whereRaw('LOWER(local_name) = ?', [mb_strtolower($name)]);
    }
}
