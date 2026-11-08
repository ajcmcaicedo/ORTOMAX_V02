<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_detalles', function (Blueprint $table) {
            $table->foreignId('instrumental_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('instrumentales')
                ->nullOnDelete();

            $table->index('instrumental_id');
        });

        Schema::table('equipo_inventario', function (Blueprint $table) {
            $table->foreignId('instrumental_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('instrumentales')
                ->nullOnDelete();

            $table->index('instrumental_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {
            $table->dropForeign(['instrumental_id']);
            $table->dropIndex(['instrumental_id']);
            $table->dropColumn('instrumental_id');
        });

        Schema::table('equipo_detalles', function (Blueprint $table) {
            $table->dropForeign(['instrumental_id']);
            $table->dropIndex(['instrumental_id']);
            $table->dropColumn('instrumental_id');
        });
    }
};