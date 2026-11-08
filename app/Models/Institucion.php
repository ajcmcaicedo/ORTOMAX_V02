<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Institucion extends Model
{
    protected $table = 'instituciones';

    protected $fillable = [
        'codigo',
        'tipo',
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ciudad',
        'regional_id',
        'telefono',
        'email',
        'campo1',
        'campo2',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Regional a la que pertenece la institución.
     */
    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }
}