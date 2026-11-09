<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_evento_con_valores(): void
    {
        $registro = Bitacora::registrar(
            evento: 'PRUEBA',
            anterior: ['cantidad' => 2],
            nuevo: ['cantidad' => 3],
            motivo: 'faltante en bodega',
            usuarioId: 7,
        );

        $this->assertDatabaseHas('bitacora', ['id' => $registro->id, 'evento' => 'PRUEBA', 'usuario_id' => 7]);
        $this->assertSame(['cantidad' => 3], $registro->fresh()->valores_nuevos);
    }

    public function test_no_se_puede_modificar_desde_la_aplicacion(): void
    {
        $registro = Bitacora::registrar(evento: 'PRUEBA');

        $this->expectException(LogicException::class);

        $registro->update(['evento' => 'OTRO']);
    }

    public function test_no_se_puede_eliminar_desde_la_aplicacion(): void
    {
        $registro = Bitacora::registrar(evento: 'PRUEBA');

        $this->expectException(LogicException::class);

        $registro->delete();
    }

    public function test_trigger_mysql_impide_update_por_sql(): void
    {
        $hayTriggers = DB::getDriverName() === 'mysql'
            && DB::table('information_schema.TRIGGERS')
                ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
                ->where('EVENT_OBJECT_TABLE', 'bitacora')
                ->exists();

        if (!$hayTriggers) {
            $this->markTestSkipped('Triggers de bitácora no instalados (el usuario de BD no tiene privilegio para crearlos).');
        }

        $registro = Bitacora::registrar(evento: 'PRUEBA');

        $this->expectException(QueryException::class);

        DB::table('bitacora')->where('id', $registro->id)->update(['evento' => 'X']);
    }
}
