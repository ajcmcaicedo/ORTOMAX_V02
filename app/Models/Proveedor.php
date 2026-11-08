<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'codigo',
        'nombre',
        'ruc',
        'sigla',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function productosProveedores(): HasMany
    {
        return $this->hasMany(
            ProductoProveedor::class,
            'proveedor_id'
        );
    }
}