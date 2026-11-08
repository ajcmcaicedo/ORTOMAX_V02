<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {

            $table->foreign('nota_entrega_id')
                ->references('id')
                ->on('notas_entrega')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
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