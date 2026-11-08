<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equiposdetallex', function (Blueprint $table) {
            $table->id();
            $table->string('codigequipo');
            $table->string('codigo_bodega');
            $table->string('codigo_regional');
            $table->string('codigoprod');
            $table->string('grupo')->nullable();
            $table->string('subgrupo')->nullable();
            $table->integer('ord')->nullable();
            $table->string('lote')->nullable();
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->index(['codigequipo', 'codigo_bodega']);
            $table->index('codigoprod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equiposdetallex');
    }
};