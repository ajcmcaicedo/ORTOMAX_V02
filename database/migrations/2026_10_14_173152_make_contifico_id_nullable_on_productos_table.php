<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Permitir productos sin contifico_id
        |--------------------------------------------------------------------------
        |
        | El UNIQUE existente se conserva.
        | MySQL permite múltiples NULL dentro de un índice UNIQUE.
        |
        */
        DB::statement("
            ALTER TABLE productos
            MODIFY contifico_id VARCHAR(255)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_bin
            NULL
        ");

        /*
        |--------------------------------------------------------------------------
        | 2. Origen del producto
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasColumn('productos', 'origen_producto')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->string('origen_producto', 30)
                    ->default('CONTIFICO')
                    ->after('contifico_id');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Estado de sincronización con Contífico
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasColumn('productos', 'estado_contifico')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->string('estado_contifico', 30)
                    ->default('SINCRONIZADO')
                    ->after('origen_producto');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Clasificar los productos existentes
        |--------------------------------------------------------------------------
        |
        | Actualmente sabemos que los 10.558 productos tienen contifico_id.
        | Por lo tanto, todos los existentes son productos provenientes
        | de Contífico.
        |
        */
        DB::table('productos')
            ->whereNotNull('contifico_id')
            ->update([
                'origen_producto' => 'CONTIFICO',
                'estado_contifico' => 'SINCRONIZADO',
            ]);
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        |
        | No permitimos volver contifico_id a NOT NULL si ya existen
        | productos PRE_REGISTRO sin contifico_id.
        |
        */

        $pendientes = DB::table('productos')
            ->whereNull('contifico_id')
            ->count();

        if ($pendientes > 0) {
            throw new RuntimeException(
                "No se puede revertir la migración: existen {$pendientes} "
                . "productos sin contifico_id."
            );
        }

        if (Schema::hasColumn('productos', 'estado_contifico')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('estado_contifico');
            });
        }

        if (Schema::hasColumn('productos', 'origen_producto')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('origen_producto');
            });
        }

        DB::statement("
            ALTER TABLE productos
            MODIFY contifico_id VARCHAR(255)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_bin
            NOT NULL
        ");
    }
};