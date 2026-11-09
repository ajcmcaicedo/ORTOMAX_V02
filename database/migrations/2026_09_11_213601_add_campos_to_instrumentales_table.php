<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Idempotente: create_instrumentales_table ya define estas columnas.
     * En una base nueva (migrate:fresh) no hace nada; en bases donde
     * falten, las agrega como antes.
     */
    public function up(): void
    {
        Schema::table('instrumentales', function (Blueprint $table) {
            if (!Schema::hasColumn('instrumentales', 'codigo')) {
                $table->string('codigo', 50)->unique()->after('id');
            }
            if (!Schema::hasColumn('instrumentales', 'nombre')) {
                $table->string('nombre', 200)->after('codigo');
            }
            if (!Schema::hasColumn('instrumentales', 'marca')) {
                $table->string('marca', 100)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('instrumentales', 'familia')) {
                $table->string('familia', 100)->nullable()->after('marca');
            }
            if (!Schema::hasColumn('instrumentales', 'subfamilia')) {
                $table->string('subfamilia', 100)->nullable()->after('familia');
            }
            if (!Schema::hasColumn('instrumentales', 'estado')) {
                $table->string('estado', 30)
                    ->default('ACTIVO')
                    ->after('subfamilia');
                $table->index('estado');
            }
            if (!Schema::hasColumn('instrumentales', 'activo')) {
                $table->boolean('activo')
                    ->default(true)
                    ->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instrumentales', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropUnique(['codigo']);

            $table->dropColumn([
                'codigo',
                'nombre',
                'marca',
                'familia',
                'subfamilia',
                'estado',
                'activo',
            ]);
        });
    }
};