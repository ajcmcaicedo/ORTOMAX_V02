<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_detalles', function (Blueprint $table) {

            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            /*
             * Producto para implantes o instrumental
             * que esté registrado como producto.
             */
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Tipo de contenido:
             *
             * IMPLANTE
             * INSTRUMENTAL
             * ACTIVO
             * ADICIONAL
             */
            $table->string('tipo_contenido', 30);

            /*
             * Cantidad que normalmente debe contener
             * el equipo.
             */
            $table->decimal('cantidad_configurada', 12, 2)
                ->default(0);

            /*
             * Indica si al elemento se le debe controlar lote.
             */
            $table->boolean('requiere_lote')
                ->default(false);

            /*
             * Indica si requiere número de serie.
             */
            $table->boolean('requiere_serial')
                ->default(false);

            /*
             * Permite desactivar temporalmente un elemento
             * de la configuración sin eliminar el historial.
             */
            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->index('equipo_id');
            $table->index('producto_id');
            $table->index('tipo_contenido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_detalles');
    }
};