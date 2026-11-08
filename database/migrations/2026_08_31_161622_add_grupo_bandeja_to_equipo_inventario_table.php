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
             * Grupo principal del elemento.
             *
             * Ejemplos:
             * IMPLANTES
             * INSTRUMENTAL
             */
            $table->string('grupo', 50)
                ->nullable()
                ->after('lote_id');

            /*
             * Subgrupo de implantes.
             *
             * Ejemplos:
             * CORTICAL
             * BLOQUEO
             * ESPONJOSO
             */
            $table->string('subgrupo', 50)
                ->nullable()
                ->after('grupo');

            /*
             * Bandeja donde se encuentra el instrumental.
             *
             * Ejemplos:
             * SUPERIOR
             * MEDIA
             * INFERIOR
             */
            $table->string('bandeja', 30)
                ->nullable()
                ->after('subgrupo');

            /*
             * Orden de presentación dentro del equipo.
             */
            $table->unsignedInteger('orden')
                ->default(0)
                ->after('bandeja');

            $table->index('grupo');
            $table->index('subgrupo');
            $table->index('bandeja');
            $table->index(['equipo_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {

            $table->dropIndex(['equipo_id', 'orden']);
            $table->dropIndex(['grupo']);
            $table->dropIndex(['subgrupo']);
            $table->dropIndex(['bandeja']);

            $table->dropColumn([
                'grupo',
                'subgrupo',
                'bandeja',
                'orden',
            ]);
        });
    }
};