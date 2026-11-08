<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('codigo_referencia', 100)
                ->nullable()
                ->after('codigo');

            $table->index('codigo_referencia');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['codigo_referencia']);
            $table->dropColumn('codigo_referencia');
        });
    }
};