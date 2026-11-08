<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'contifico_id',
        'origen_producto',
        'estado_contifico',
        'codigo',
        'categoria_id',
        'codigojbg',
        'marca',
        'categoria',
        'codigo_proveedor',
        'desc_proveedor',
        'familia',
        'subfamilia',
        'proced_principal',
        'region_anatomica_principal',
        'tipo_cirugia',
        'nombre',
        'codigo_barra',
        'descripcion',
        'estado',
        'tipo',
        'tipo_producto',
        'porcentaje_iva',
        'costo_maximo',
        'fecha_contifico',
    ];

    protected $casts = [
        'porcentaje_iva' => 'decimal:2',
        'costo_maximo' => 'decimal:4',
        'fecha_contifico' => 'datetime',
    ];

        public function productosProveedores(): HasMany
    {
        return $this->hasMany(
            ProductoProveedor::class,
            'producto_id'
        );
    }

    /**
     * Lotes pertenecientes al producto.
     */
    public function lotes(): HasMany
    {
        return $this->hasMany(
            Lote::class,
            'producto_id'
        );
    }

    /**
     * Configuración de equipos donde aparece
     * este producto.
     */
    public function equipoDetalles(): HasMany
    {
        return $this->hasMany(
            EquipoDetalle::class,
            'producto_id'
        );
    }

    /**
     * Inventario real de equipos donde aparece
     * este producto.
     */
    public function equipoInventario(): HasMany
    {
        return $this->hasMany(
            EquipoInventario::class,
            'producto_id'
        );
    }
}