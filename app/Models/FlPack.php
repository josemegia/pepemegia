<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlPack extends Model
{
    protected $table = 'fl_packs';

    protected $fillable = [
        'country_id', 'product_id', 'name', 'type',
        'price', 'regular_price_sum', 'savings', 'savings_pct',
        'currency_code', 'url', 'is_available',
        'promotion_details', 'valid_from', 'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'price'             => 'decimal:2',
            'regular_price_sum' => 'decimal:2',
            'savings'           => 'decimal:2',
            'savings_pct'       => 'decimal:2',
            'is_available'      => 'boolean',
            'valid_from'        => 'date',
            'valid_until'       => 'date',
        ];
    }

    // --- Relaciones ---

    public function country(): BelongsTo
    {
        return $this->belongsTo(FlCountry::class, 'country_id');
    }

    public function storeProduct(): BelongsTo
    {
        return $this->belongsTo(FlProduct::class, 'product_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            FlProduct::class,
            'fl_pack_items',
            'pack_id',
            'product_id'
        )->withPivot('quantity')->withTimestamps();
    }

    // --- Scopes ---

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeCurrentPromos($query)
    {
        return $query->where('is_available', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now()->toDateString());
            });
    }

    // --- Helpers ---

    public function calculateSavings(): void
    {
        $sum = $this->items->sum(function ($product) {
            return $product->price * $product->pivot->quantity;
        });

        $this->regular_price_sum = $sum;
        $this->savings = $sum - $this->price;
        $this->savings_pct = $sum > 0 ? round(($this->savings / $sum) * 100, 2) : 0;
        $this->save();
    }
}
