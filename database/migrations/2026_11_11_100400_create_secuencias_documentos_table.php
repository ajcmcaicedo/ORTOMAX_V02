<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Contadores de numeración de documentos (sin huecos por concurrencia:
 * se bloquea la fila al tomar el siguiente número).
 *
 * clave: p. ej. "NE-2026" para Notas de Entrega del año 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias_documentos');
    }
};
