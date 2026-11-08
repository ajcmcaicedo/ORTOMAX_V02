<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoMaestro extends Model
{
    use HasFactory;

    protected $table = 'productos_maestros';

    protected $fillable = [
        'codigo_maestro',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function productosProveedores(): HasMany
    {
        return $this->hasMany(
            ProductoProveedor::class,
            'producto_maestro_id'
        );
    }
}