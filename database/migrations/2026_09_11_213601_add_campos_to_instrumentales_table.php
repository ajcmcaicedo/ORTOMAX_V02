<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instrumentales', function (Blueprint $table) {
            $table->string('codigo', 50)->unique()->after('id');
            $table->string('nombre', 200)->after('codigo');

            $table->string('marca', 100)->nullable()->after('nombre');
            $table->string('familia', 100)->nullable()->after('marca');
            $table->string('subfamilia', 100)->nullable()->after('familia');

            $table->string('estado', 30)
                ->default('ACTIVO')
                ->after('subfamilia');

            $table->boolean('activo')
                ->default(true)
                ->after('estado');

            $table->index('estado');
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