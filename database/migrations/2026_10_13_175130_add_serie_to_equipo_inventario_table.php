<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {
            $table->string('serie', 100)
                ->nullable()
                ->after('lote_id');

            $table->index('serie');
        });
    }

    public function down(): void
    {
        Schema::table('equipo_inventario', function (Blueprint $table) {
            $table->dropIndex(['serie']);
            $table->dropColumn('serie');
        });
    }
};