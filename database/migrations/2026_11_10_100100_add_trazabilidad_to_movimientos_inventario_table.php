<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Aditiva.
 *
 * idempotency_key: evita que un doble clic (p. ej. en "Despachar")
 *                  genere dos movimientos.
 * movimiento_reversado_id: un movimiento REVERSA apunta al movimiento
 *                  que anula; cada movimiento se puede reversar una sola vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->string('idempotency_key', 120)
                ->nullable()
                ->after('referencia');

            $table->foreignId('movimiento_reversado_id')
                ->nullable()
                ->after('idempotency_key')
                ->constrained('movimientos_inventario')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->unique('idempotency_key', 'uk_movimientos_idempotency_key');
            $table->unique('movimiento_reversado_id', 'uk_movimientos_reversado');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['movimiento_reversado_id']);
            $table->dropUnique('uk_movimientos_idempotency_key');
            $table->dropUnique('uk_movimientos_reversado');
            $table->dropColumn(['idempotency_key', 'movimiento_reversado_id']);
        });
    }
};
