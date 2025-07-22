<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    use HasFactory;

    // Permite la asignación masiva para estos campos
    protected $fillable = [
        'long_url',
        'short_code',
        'clicks',
    ];
}
