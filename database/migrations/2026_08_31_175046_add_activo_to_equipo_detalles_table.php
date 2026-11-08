<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_detalles', function (Blueprint $table) {

            /*
             * Activo individual perteneciente al equipo.
             *
             * Se utiliza para:
             * - Instrumental reutilizable
             * - Motores
             * - Baterías
             * - Maletas
             * - Otros activos individualizables
             *
             * Para implantes, este campo permanece NULL
             * y se utiliza producto_id.
             */
            $table->foreignId('activo_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('activos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index('activo_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipo_detalles', function (Blueprint $table) {

            $table->dropForeign(['activo_id']);
            $table->dropIndex(['activo_id']);
            $table->dropColumn('activo_id');
        });
    }
};