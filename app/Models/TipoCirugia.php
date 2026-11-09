<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCirugia extends Model
{
    protected $table = 'tipos_cirugia';

    protected $fillable = [
        'codigo',
        'nombre',
        'region_anatomica',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Plantilla de equipos sugeridos (por referencia de equipo).
     */
    public function plantilla(): HasMany
    {
        return $this->hasMany(TipoCirugiaEquipo::class, 'tipo_cirugia_id')
            ->orderBy('orden')
            ->orderBy('id');
    }
}
