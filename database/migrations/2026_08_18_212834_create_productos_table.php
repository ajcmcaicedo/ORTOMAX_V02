<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {

            $table->id();

            $table->string('contifico_id')->unique();

            $table->string('codigo')->nullable()->index();

            $table->string('nombre')->nullable();

            $table->string('codigo_barra')->nullable()->index();

            $table->text('descripcion')->nullable();

            $table->string('estado')->nullable();

            $table->string('tipo')->nullable();

            $table->string('tipo_producto')->nullable();

            $table->decimal('porcentaje_iva', 5, 2)->nullable();

            $table->decimal('costo_maximo', 15, 4)->nullable();

            $table->timestamp('fecha_contifico')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};