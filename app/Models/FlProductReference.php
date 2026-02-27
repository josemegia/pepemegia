<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlProductReference extends Model
{
    protected $table = 'fl_products_reference';

    protected $fillable = [
        'name', 'slug', 'category', 'format',
        'serving_size', 'servings_per_container',
        'ingredients', 'key_ingredients_summary',
        'mechanism', 'dosage_instructions', 'benefits', 'warnings',
        'image_url', 'source_country', 'source_url', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ingredients' => 'array',
            'is_active'   => 'boolean',
        ];
    }

    // --- Relaciones ---

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(
            FlHealthCondition::class,
            'fl_reference_conditions',
            'reference_id',
            'condition_id'
        )->withPivot('relevance', 'notes')->withTimestamps();
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(FlProductAlias::class, 'reference_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(FlProduct::class, 'reference_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCondition($query, int $conditionId)
    {
        return $query->whereHas('conditions', function ($q) use ($conditionId) {
            $q->where('fl_health_conditions.id', $conditionId);
        });
    }

    public function scopeForConditions($query, array $conditionIds)
    {
        return $query->whereHas('conditions', function ($q) use ($conditionIds) {
            $q->whereIn('fl_health_conditions.id', $conditionIds);
        });
    }

    // --- Helpers ---

    public function getLocalProduct(int $countryId): ?FlProduct
    {
        return $this->products()->where('country_id', $countryId)->first();
    }

    public function getAlias(int $countryId): ?FlProductAlias
    {
        return $this->aliases()->where('country_id', $countryId)->first();
    }
}
