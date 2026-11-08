<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoProveedor extends Model
{
    use HasFactory;

    protected $table = 'productos_proveedores';

    protected $fillable = [
        'producto_maestro_id',
        'proveedor_id',
        'producto_id',
        'codigo_producto',
        'registro_sanitario',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function productosProveedores(): HasMany
    {
        return $this->hasMany(
            ProductoProveedor::class,
            'producto_maestro_id'
        );
    }


    public function productoMaestro(): BelongsTo
    {
        return $this->belongsTo(
            ProductoMaestro::class,
            'producto_maestro_id'
        );
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(
            Proveedor::class,
            'proveedor_id'
        );
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }
}