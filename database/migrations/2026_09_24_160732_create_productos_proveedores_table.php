<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_proveedores', function (Blueprint $table) {

            $table->id();

            $table->foreignId('producto_maestro_id')
                ->constrained('productos_maestros')
                ->cascadeOnDelete();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->string('codigo_producto', 100)
                ->nullable();

            $table->string('registro_sanitario', 150)
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'producto_maestro_id',
                    'proveedor_id',
                    'producto_id'
                ],
                'productos_proveedores_unico'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_proveedores');
    }
};