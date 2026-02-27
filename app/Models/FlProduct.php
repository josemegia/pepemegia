<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlProduct extends Model
{
    protected $table = 'fl_products';

    protected $fillable = [
        'country_id', 'reference_id', 'external_id',
        'name', 'slug', 'url', 'image_url', 'description',
        'price', 'price_wholesale', 'price_loyalty', 'currency_code',
        'format', 'units_per_container', 'serving_size',
        'is_pack', 'is_available', 'is_on_promotion', 'promotion_details',
        'category', 'sort_order', 'raw_html', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'price'           => 'decimal:2',
            'price_wholesale' => 'decimal:2',
            'price_loyalty'   => 'decimal:2',
            'is_pack'         => 'boolean',
            'is_available'    => 'boolean',
            'is_on_promotion' => 'boolean',
            'last_seen_at'    => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function country(): BelongsTo
    {
        return $this->belongsTo(FlCountry::class, 'country_id');
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(FlProductReference::class, 'reference_id');
    }

    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(
            FlPack::class,
            'fl_pack_items',
            'product_id',
            'pack_id'
        )->withPivot('quantity')->withTimestamps();
    }

    // --- Scopes ---

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeOnPromotion($query)
    {
        return $query->where('is_on_promotion', true);
    }

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeLinked($query)
    {
        return $query->whereNotNull('reference_id');
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('reference_id');
    }

    // --- Helpers ---

    /**
     * Obtiene el formato efectivo: local si existe, o heredado de referencia.
     */
    public function getEffectiveFormat(): ?string
    {
        return $this->format ?? $this->reference?->format;
    }

    /**
     * Obtiene las instrucciones de dosificación: heredadas de referencia.
     */
    public function getEffectiveDosage(): ?string
    {
        return $this->reference?->dosage_instructions;
    }

    /**
     * Genera un resumen para enviar a la IA.
     */
    public function toAiArray(): array
    {
        $ref = $this->reference;

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'format'             => $this->getEffectiveFormat(),
            'units_per_container'=> $this->units_per_container ?? $ref?->servings_per_container,
            'serving_size'       => $this->serving_size ?? $ref?->serving_size,
            'dosage'             => $ref?->dosage_instructions,
            'key_ingredients'    => $ref?->key_ingredients_summary,
            'mechanism'          => $ref?->mechanism,
            'benefits'           => $ref?->benefits,
            'price'              => $this->price,
            'currency'           => $this->currency_code,
            'is_available'       => $this->is_available,
            'is_on_promotion'    => $this->is_on_promotion,
            'promotion_details'  => $this->promotion_details,
            'url'                => $this->url,
        ];
    }
}
