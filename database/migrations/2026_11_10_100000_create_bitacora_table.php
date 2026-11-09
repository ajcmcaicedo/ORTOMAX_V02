<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Bitácora de auditoría (inmutable)
|--------------------------------------------------------------------------
|
| Registra cambios de estado, cambios de contenido por nota, consumos,
| accesos a datos de pacientes, anulaciones y reversas.
|
| Inmutabilidad:
| 1. El modelo App\Models\Bitacora bloquea update y delete.
| 2. En MySQL se crean triggers que impiden UPDATE/DELETE incluso por SQL.
|    Si el usuario de base de datos no tiene privilegios para crear
|    triggers (binlog activo sin SUPER), la migración continúa y deja una
|    advertencia en el log: la protección del modelo sigue vigente.
|
| Sin claves foráneas a propósito: la bitácora debe sobrevivir aunque
| se eliminen usuarios u otros registros.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();

            // Ej.: NOTA_ESTADO, NOTA_EQUIPO_AGREGADO, NOTA_CONTENIDO_MODIFICADO,
            // CONSUMO_REGISTRADO, PACIENTE_ACCESO, NOTA_ANULADA, MOVIMIENTO_REVERSADO
            $table->string('evento', 60);

            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->unsignedBigInteger('nota_entrega_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();

            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->text('motivo')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('evento');
            $table->index(['auditable_type', 'auditable_id'], 'idx_bitacora_auditable');
            $table->index('nota_entrega_id');
            $table->index('usuario_id');
            $table->index('created_at');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::unprepared("
                CREATE TRIGGER trg_bitacora_no_update
                BEFORE UPDATE ON bitacora
                FOR EACH ROW
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La bitacora no se puede modificar.'
            ");

            DB::unprepared("
                CREATE TRIGGER trg_bitacora_no_delete
                BEFORE DELETE ON bitacora
                FOR EACH ROW
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La bitacora no se puede eliminar.'
            ");
        } catch (\Throwable $e) {
            Log::warning(
                'No se pudieron crear los triggers de inmutabilidad de bitacora: '
                . $e->getMessage()
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_bitacora_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_bitacora_no_delete');
        }

        Schema::dropIfExists('bitacora');
    }
};
