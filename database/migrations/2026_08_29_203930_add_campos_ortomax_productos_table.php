<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos propios de ORTOMAX a productos.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {

            $table->string('codigojbg')
                ->nullable()
                ->index()
                ->after('codigo');

            $table->string('marca')
                ->nullable()
                ->after('codigojbg');

            $table->string('codigo_proveedor')
                ->nullable()
                ->index()
                ->after('marca');

            $table->string('desc_proveedor')
                ->nullable()
                ->after('codigo_proveedor');

            $table->string('familia')
                ->nullable()
                ->index()
                ->after('desc_proveedor');

            $table->string('subfamilia')
                ->nullable()
                ->index()
                ->after('familia');

            $table->string('proced_principal')
                ->nullable()
                ->index()
                ->after('subfamilia');

            $table->string('region_anatomica_principal')
                ->nullable()
                ->index()
                ->after('proced_principal');

            $table->string('tipo_cirugia')
                ->nullable()
                ->index()
                ->after('region_anatomica_principal');
        });
    }

    /**
     * Revierte los cambios realizados.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {

            $table->dropIndex(['codigojbg']);
            $table->dropIndex(['codigo_proveedor']);
            $table->dropIndex(['familia']);
            $table->dropIndex(['subfamilia']);
            $table->dropIndex(['proced_principal']);
            $table->dropIndex(['region_anatomica_principal']);
            $table->dropIndex(['tipo_cirugia']);

            $table->dropColumn([
                'codigojbg',
                'marca',
                'codigo_proveedor',
                'desc_proveedor',
                'familia',
                'subfamilia',
                'proced_principal',
                'region_anatomica_principal',
                'tipo_cirugia',
            ]);
        });
    }
};