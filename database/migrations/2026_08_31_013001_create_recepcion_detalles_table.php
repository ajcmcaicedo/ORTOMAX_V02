<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_detalles', function (Blueprint $table) {

            $table->id();

            /*
             * Recepción a la que pertenece este detalle.
             */
            $table->foreignId('recepcion_id')
                ->constrained('recepciones')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            /*
             * Línea original de la Nota de Entrega.
             * Nos permite comparar exactamente contra
             * lo que fue enviado.
             */
            $table->foreignId('nota_entrega_detalle_id')
                ->nullable()
                ->constrained('nota_entrega_detalles')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Equipo relacionado.
             */
            $table->foreignId('equipo_id')
                ->nullable()
                ->constrained('equipos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Producto recibido.
             */
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Lote recibido.
             */
            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Activo individual recibido.
             * Ejemplo: MOTOR-001
             */
            $table->foreignId('activo_id')
                ->nullable()
                ->constrained('activos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Tipo de elemento recibido:
             *
             * EQUIPO
             * IMPLANTE
             * INSTRUMENTAL
             * ACTIVO
             * ADICIONAL
             */
            $table->string('tipo_linea', 30);

            /*
             * Código y descripción conservados
             * como referencia histórica.
             */
            $table->string('codigo', 100)
                ->nullable();

            $table->string('descripcion', 255);

            /*
             * Cantidades.
             */
            $table->decimal('cantidad_enviada', 12, 2)
                ->default(0);

            $table->decimal('cantidad_consumida', 12, 2)
                ->default(0);

            $table->decimal('cantidad_esperada', 12, 2)
                ->default(0);

            $table->decimal('cantidad_recibida', 12, 2)
                ->default(0);

            $table->decimal('cantidad_faltante', 12, 2)
                ->default(0);

            /*
             * Situación encontrada durante la recepción:
             *
             * DEVUELTO
             * CONSUMIDO
             * FALTANTE
             * PRESTADO
             * DAÑADO
             * NO_UTILIZADO
             * DIFERENCIA
             */
            $table->string('resultado', 30)
                ->default('PENDIENTE');

            /*
             * Motivo cuando existe una diferencia.
             *
             * Ejemplo:
             * "Motor prestado para otra cirugía"
             */
            $table->text('motivo')
                ->nullable();

            /*
             * Usuario que realizó la revisión.
             */
            $table->foreignId('usuario_revisor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->dateTime('fecha_revision')
                ->nullable();

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('recepcion_id');
            $table->index('nota_entrega_detalle_id');
            $table->index('equipo_id');
            $table->index('producto_id');
            $table->index('lote_id');
            $table->index('activo_id');
            $table->index('tipo_linea');
            $table->index('resultado');
            $table->index('usuario_revisor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_detalles');
    }
};