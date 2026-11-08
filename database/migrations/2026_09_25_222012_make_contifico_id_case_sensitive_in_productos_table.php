<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_contifico_id_unique');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('contifico_id')
                ->collation('utf8mb4_bin')
                ->change();

            $table->unique('contifico_id', 'productos_contifico_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_contifico_id_unique');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('contifico_id')
                ->collation('utf8mb4_unicode_ci')
                ->change();

            $table->unique('contifico_id', 'productos_contifico_id_unique');
        });
    }
};