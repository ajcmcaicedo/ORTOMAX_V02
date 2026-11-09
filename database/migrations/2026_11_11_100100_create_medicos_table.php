<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('identificacion', 30)->nullable()->unique();
            $table->string('nombres', 200);
            $table->string('especialidad', 100)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombres');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
