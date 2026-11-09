<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Idempotente: la migración create_movimientos_inventario_table
     * ya crea estas FK con constrained(). En una base nueva (migrate:fresh)
     * se omiten; en bases donde no existan se crean como antes.
     */
    public function up(): void
    {
        $existentes = collect(Schema::getForeignKeys('movimientos_inventario'))
            ->flatMap(fn ($fk) => $fk['columns'])
            ->all();

        Schema::table('movimientos_inventario', function (Blueprint $table) use ($existentes) {

            if (!in_array('nota_entrega_id', $existentes, true)) {
                $table->foreign('nota_entrega_id')
                    ->references('id')
                    ->on('notas_entrega')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            if (!in_array('usuario_id', $existentes, true)) {
                $table->foreign('usuario_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {

            $table->dropForeign([
                'nota_entrega_id'
            ]);

            $table->dropForeign([
                'usuario_id'
            ]);
        });
    }
};