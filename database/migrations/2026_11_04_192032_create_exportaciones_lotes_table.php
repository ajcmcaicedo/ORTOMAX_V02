<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exportaciones_lotes', function (Blueprint $table) {
            $table->id();

            $table->string('token', 64)->unique();
            $table->string('estado', 20)->default('pendiente');

            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('procesados')->default(0);
            $table->unsignedTinyInteger('porcentaje')->default(0);

            $table->string('archivo')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exportaciones_lotes');
    }
};
