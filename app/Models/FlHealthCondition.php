<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlHealthCondition extends Model
{
    protected $table = 'fl_health_conditions';

    protected $fillable = [
        'name', 'slug', 'aliases', 'category', 'description',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
        ];
    }

    // --- Relaciones ---

    public function references(): BelongsToMany
    {
        return $this->belongsToMany(
            FlProductReference::class,
            'fl_reference_conditions',
            'condition_id',
            'reference_id'
        )->withPivot('relevance', 'notes')->withTimestamps();
    }

    // --- Scopes ---

    public function scopeSearch($query, string $term)
    {
        $term = mb_strtolower($term);

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(JSON_EXTRACT(aliases, \'$[*]\')) LIKE ?', ["%{$term}%"]);
        });
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // --- Helpers ---

    public function matchesTerm(string $term): bool
    {
        $term = mb_strtolower($term);

        if (str_contains(mb_strtolower($this->name), $term)) {
            return true;
        }

        if (is_array($this->aliases)) {
            foreach ($this->aliases as $alias) {
                if (str_contains(mb_strtolower($alias), $term)) {
                    return true;
                }
            }
        }

        return false;
    }
}
