<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Aditiva. Datos de la cirugía en la nota. Todos opcionales porque el
 * borrador se crea con la información que llegó por teléfono/WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_entrega', function (Blueprint $table) {
            $table->foreignId('tipo_cirugia_id')->nullable()->after('bodega_origen_id')
                ->constrained('tipos_cirugia')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('medico_id')->nullable()->after('tipo_cirugia_id')
                ->constrained('medicos')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('fecha_cirugia_programada')->nullable()->after('fecha_emision');
            $table->dateTime('fecha_cirugia_efectiva')->nullable()->after('fecha_cirugia_programada');

            $table->index('fecha_cirugia_programada');
        });
    }

    public function down(): void
    {
        Schema::table('notas_entrega', function (Blueprint $table) {
            $table->dropForeign(['tipo_cirugia_id']);
            $table->dropForeign(['medico_id']);
            $table->dropIndex(['fecha_cirugia_programada']);
            $table->dropColumn([
                'tipo_cirugia_id',
                'medico_id',
                'fecha_cirugia_programada',
                'fecha_cirugia_efectiva',
            ]);
        });
    }
};
