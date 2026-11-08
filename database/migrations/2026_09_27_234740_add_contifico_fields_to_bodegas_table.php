<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::table('bodegas', function (Blueprint $table) {

            $table->string('contifico_id', 255)
                ->nullable()
                ->collation('utf8mb4_bin')
                ->unique()
                ->after('activo');

            $table->boolean('contifico_venta')
                ->nullable()
                ->after('contifico_id');

            $table->boolean('contifico_produccion')
                ->nullable()
                ->after('contifico_venta');

            $table->boolean('contifico_compra')
                ->nullable()
                ->after('contifico_produccion');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('bodegas', function (Blueprint $table) {

            $table->dropUnique('bodegas_contifico_id_unique');

            $table->dropColumn([
                'contifico_id',
                'contifico_venta',
                'contifico_produccion',
                'contifico_compra',
            ]);
        });
    }
};