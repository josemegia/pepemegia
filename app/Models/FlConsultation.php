<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlConsultation extends Model
{
    protected $table = 'fl_consultations';

    protected $fillable = [
        'country_id', 'session_id', 'ip_hash', 'input_type',
        'input_text', 'input_file_path',
        'detected_conditions', 'products_sent',
        'ai_model', 'ai_response',
        'ai_tokens_input', 'ai_tokens_output', 'ai_cost_usd',
        'response_time_ms', 'user_rating',
    ];

    protected function casts(): array
    {
        return [
            'detected_conditions' => 'array',
            'products_sent'       => 'array',
            'ai_cost_usd'        => 'decimal:6',
        ];
    }

    // --- Relaciones ---

    public function country(): BelongsTo
    {
        return $this->belongsTo(FlCountry::class, 'country_id');
    }

    // --- Scopes ---

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeRated($query)
    {
        return $query->whereNotNull('user_rating');
    }
}
