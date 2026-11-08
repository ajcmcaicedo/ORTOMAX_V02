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
             * Grupo principal del contenido.
             *
             * Ejemplos:
             * IMPLANTES
             * INSTRUMENTAL
             */
            $table->string('grupo', 50)
                ->nullable()
                ->after('tipo_contenido');

            /*
             * Subgrupo del implante.
             *
             * Ejemplos:
             * CORTICAL
             * BLOQUEADO
             * ESPONJOSO
             */
            $table->string('subgrupo', 50)
                ->nullable()
                ->after('grupo');

            /*
             * Bandeja física para instrumental.
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
             * Orden en que aparecerá el contenido
             * dentro del equipo.
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
        Schema::table('equipo_detalles', function (Blueprint $table) {

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