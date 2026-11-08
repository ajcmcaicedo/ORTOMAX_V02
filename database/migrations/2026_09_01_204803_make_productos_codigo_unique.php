<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_codigo_index');

            $table->unique(
                'codigo',
                'productos_codigo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_codigo_unique');

            $table->index(
                'codigo',
                'productos_codigo_index'
            );
        });
    }
};