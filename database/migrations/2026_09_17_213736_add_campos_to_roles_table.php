<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('nombre', 100)
                ->unique()
                ->after('id');

            $table->string('descripcion', 255)
                ->nullable()
                ->after('nombre');

            $table->boolean('activo')
                ->default(true)
                ->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->dropColumn([
                'nombre',
                'descripcion',
                'activo',
            ]);
        });
    }
};