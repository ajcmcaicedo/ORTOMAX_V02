<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Aditiva. Personas responsables de la nota (necesarias para las
 * Policies: el instrumentador solo ve sus notas asignadas) y versión
 * para bloqueo optimista.
 *
 * Tipo de cirugía, médico, paciente y fechas de cirugía se agregan
 * en la Fase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_entrega', function (Blueprint $table) {
            $table->foreignId('solicitado_por_id')->nullable()->after('bodega_origen_id')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('creado_por_id')->nullable()->after('solicitado_por_id')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('preparador_id')->nullable()->after('creado_por_id')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('instrumentador_id')->nullable()->after('preparador_id')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->dateTime('fecha_entrega')->nullable()->after('fecha_despacho');
            $table->dateTime('fecha_anulacion')->nullable()->after('fecha_cierre');

            $table->unsignedInteger('version')->default(1)->after('observaciones');

            $table->index('instrumentador_id');
            $table->index('preparador_id');
        });
    }

    public function down(): void
    {
        Schema::table('notas_entrega', function (Blueprint $table) {
            $table->dropForeign(['solicitado_por_id']);
            $table->dropForeign(['creado_por_id']);
            $table->dropForeign(['preparador_id']);
            $table->dropForeign(['instrumentador_id']);
            $table->dropColumn([
                'solicitado_por_id',
                'creado_por_id',
                'preparador_id',
                'instrumentador_id',
                'fecha_entrega',
                'fecha_anulacion',
                'version',
            ]);
        });
    }
};
