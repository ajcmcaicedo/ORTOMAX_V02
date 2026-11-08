<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'categoria_id')) {
                $table->string('categoria_id', 50)
                    ->nullable()
                    ->after('codigo');
            }

            if (!Schema::hasColumn('productos', 'marca_id')) {
                $table->string('marca_id', 50)
                    ->nullable()
                    ->after('marca');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'categoria_id')) {
                $table->dropColumn('categoria_id');
            }

            if (Schema::hasColumn('productos', 'marca_id')) {
                $table->dropColumn('marca_id');
            }
        });
    }
};