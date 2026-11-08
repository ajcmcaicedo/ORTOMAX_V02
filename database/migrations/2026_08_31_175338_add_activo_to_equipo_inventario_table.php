<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {

            /*
             * Un activo individual dentro del equipo.
             *
             * Se utiliza para:
             * - Instrumental reutilizable
             * - Motores
             * - Baterías
             * - Maletas
             * - Otros activos individualizables
             *
             * Para implantes:
             * activo_id permanece NULL.
             */
            $table->foreignId('activo_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('activos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index('activo_id');
        });

        /*
         * producto_id debe permitir NULL porque una línea
         * de inventario puede representar exclusivamente
         * un activo.
         */
        Schema::table('equipo_inventario', function (Blueprint $table) {
            $table->foreignId('producto_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {

            $table->dropForeign(['activo_id']);
            $table->dropIndex(['activo_id']);
            $table->dropColumn('activo_id');

            /*
             * Restaurar producto_id como obligatorio.
             *
             * Esto solamente será posible si no existen
             * registros con producto_id = NULL.
             */
            $table->foreignId('producto_id')
                ->nullable(false)
                ->change();
        });
    }
};