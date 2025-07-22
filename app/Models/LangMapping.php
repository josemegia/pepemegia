<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LangMapping extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 't_lang';

    /**
     * La llave primaria para el modelo.
     *
     * @var string
     */
    protected $primaryKey = 'lang_code';

    /**
     * Indica si la llave primaria es auto-incremental.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * El tipo de dato de la llave primaria.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indica si el modelo debe tener timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lang_code',
        'country_code',
    ];
}