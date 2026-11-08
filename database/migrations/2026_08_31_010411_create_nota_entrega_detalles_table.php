<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_entrega_detalles', function (Blueprint $table) {

            $table->id();

            /*
             * Nota a la que pertenece este detalle.
             */
            $table->foreignId('nota_entrega_id')
                ->constrained('notas_entrega')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            /*
             * Equipo al que pertenece el elemento,
             * cuando corresponda.
             *
             * Ejemplo:
             * P14NCC100
             */
            $table->foreignId('equipo_id')
                ->nullable()
                ->constrained('equipos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Producto físico.
             *
             * Se utiliza para tornillos, placas, clavos,
             * instrumental registrado como producto, etc.
             */
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Lote específico del producto enviado.
             */
            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Activo individual.
             *
             * Ejemplo:
             * MOTOR-001
             */
            $table->foreignId('activo_id')
                ->nullable()
                ->constrained('activos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Tipo de línea:
             *
             * EQUIPO
             * IMPLANTE
             * INSTRUMENTAL
             * ACTIVO
             * ADICIONAL
             */
            $table->string('tipo_linea', 30);

            /*
             * Código/nombre que aparecerá en la Nota.
             *
             * Lo conservamos como fotografía histórica
             * de lo que decía la Nota al momento del despacho.
             */
            $table->string('codigo', 100)->nullable();

            $table->string('descripcion', 255);

            /*
             * Cantidad enviada.
             */
            $table->decimal('cantidad_enviada', 12, 2)
                ->default(0);

            /*
             * Cantidad recibida posteriormente.
             */
            $table->decimal('cantidad_recibida', 12, 2)
                ->default(0);

            /*
             * Cantidad consumida durante cirugía.
             */
            $table->decimal('cantidad_consumida', 12, 2)
                ->default(0);

            /*
             * Cantidad pendiente / faltante.
             */
            $table->decimal('cantidad_faltante', 12, 2)
                ->default(0);

            /*
             * Precio aplicado a esta institución
             * en esta Nota de Entrega.
             *
             * Es importante guardar el precio aquí como
             * histórico y no depender solamente de una
             * tabla de precios futura.
             */
            $table->decimal('precio_unitario', 15, 4)
                ->nullable();

            $table->decimal('subtotal', 15, 4)
                ->nullable();

            /*
             * Estado de esta línea:
             *
             * PENDIENTE
             * ENVIADO
             * CONSUMIDO
             * DEVUELTO
             * FALTANTE
             * CORREGIDO
             */
            $table->string('estado', 30)
                ->default('ENVIADO');

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('nota_entrega_id');
            $table->index('equipo_id');
            $table->index('producto_id');
            $table->index('lote_id');
            $table->index('activo_id');
            $table->index('tipo_linea');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_entrega_detalles');
    }
};