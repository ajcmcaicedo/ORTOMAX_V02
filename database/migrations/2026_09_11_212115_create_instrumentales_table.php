<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrumentales', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);

            $table->string('marca', 100)->nullable();
            $table->string('familia', 100)->nullable();
            $table->string('subfamilia', 100)->nullable();

            $table->string('estado', 30)->default('ACTIVO');
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrumentales');
    }
};