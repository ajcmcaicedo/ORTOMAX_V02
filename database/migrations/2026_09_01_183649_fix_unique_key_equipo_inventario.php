<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {
            // Eliminamos la restricción que mezcla productos y activos.
            $table->dropUnique('uk_equipo_producto_lote');

            // Un activo físico solo puede aparecer una vez
            // dentro del mismo equipo.
            $table->unique(
                ['equipo_id', 'activo_id'],
                'uk_equipo_activo'
            );
        });
    }

    public function down(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {
            $table->dropUnique('uk_equipo_activo');

            // Restauramos la restricción anterior.
            $table->unique(
                ['equipo_id', 'producto_id', 'lote_id'],
                'uk_equipo_producto_lote'
            );
        });
    }
};