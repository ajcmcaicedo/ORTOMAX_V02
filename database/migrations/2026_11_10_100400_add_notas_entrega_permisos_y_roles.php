<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Permisos y roles del flujo de Notas de Entrega
|--------------------------------------------------------------------------
|
| Migración de datos, igual que add_instituciones_permissions.
| - Crea los permisos si no existen.
| - Crea los roles si no existen (no modifica roles existentes).
| - Asigna permisos a cada rol (solo agrega, nunca quita).
| - El rol id 1 (administrador actual del sistema) recibe todos.
*/
return new class extends Migration
{
    private array $permisos = [
        'notas.ver' => 'Ver notas de entrega propias o asignadas',
        'notas.ver_todas' => 'Ver todas las notas de entrega',
        'notas.crear' => 'Crear notas de entrega (solicitud / borrador)',
        'notas.alistar' => 'Alistar equipos y modificar contenido por nota',
        'notas.despachar' => 'Despachar notas de entrega',
        'notas.confirmar_entrega' => 'Confirmar recepción en la institución',
        'notas.anular' => 'Anular notas de entrega (incluye reversas)',
        'notas.ajustar' => 'Autorizar ajustes posteriores al despacho',
        'consumos.registrar' => 'Registrar consumo en cirugía',
        'consumos.corregir' => 'Corregir consumos ya enviados',
        'devoluciones.verificar' => 'Verificar devoluciones y cerrar notas',
        'bajas.aprobar' => 'Aprobar cuarentena y bajas de inventario',
        'equipos.composicion.editar' => 'Modificar la composición permanente de equipos',
        'plantillas_cirugia.editar' => 'Editar plantillas por tipo de cirugía',
        'facturacion.ver' => 'Ver consumos para facturación',
        'facturacion.facturar' => 'Marcar consumos como facturados',
        'facturacion.anular' => 'Anular registros de facturación',
        'pacientes.ver' => 'Ver datos del paciente',
        'kardex.ver' => 'Consultar kardex por producto y lote',
    ];

    private array $roles = [
        'GERENTE' => [
            'descripcion' => 'Solicita notas, ve todo, autoriza anulaciones y ajustes',
            'permisos' => [
                'notas.ver', 'notas.ver_todas', 'notas.crear', 'notas.anular',
                'notas.ajustar', 'kardex.ver', 'pacientes.ver',
            ],
        ],
        'PREPARADOR' => [
            'descripcion' => 'Crea y alista notas, agrega equipos y despacha',
            'permisos' => [
                'notas.ver', 'notas.crear', 'notas.alistar', 'notas.despachar',
            ],
        ],
        'INSTRUMENTADOR' => [
            'descripcion' => 'Ve sus notas asignadas, confirma recepción y registra consumo',
            'permisos' => [
                'notas.ver', 'notas.confirmar_entrega', 'consumos.registrar', 'pacientes.ver',
            ],
        ],
        'JEFE_BODEGA' => [
            'descripcion' => 'Composición permanente de equipos, devoluciones y bajas',
            'permisos' => [
                'notas.ver', 'notas.ver_todas', 'equipos.composicion.editar',
                'devoluciones.verificar', 'bajas.aprobar', 'kardex.ver',
            ],
        ],
        'FACTURACION' => [
            'descripcion' => 'Gestiona la tabla de consumos para facturación',
            'permisos' => [
                'facturacion.ver', 'facturacion.facturar', 'facturacion.anular',
            ],
        ],
        'ADMINISTRADOR' => [
            'descripcion' => 'Configuración, plantillas y usuarios',
            'permisos' => [
                'notas.ver', 'notas.ver_todas', 'plantillas_cirugia.editar', 'kardex.ver',
            ],
        ],
    ];

    public function up(): void
    {
        $ahora = now();

        /*
         * Se determina ANTES de crear roles nuevos: en una base vacía
         * el primer rol creado aquí tomaría el id 1 y recibiría todo.
         */
        $existeAdministradorActual = DB::table('roles')->where('id', 1)->exists();

        foreach ($this->permisos as $nombre => $descripcion) {
            if (!DB::table('permissions')->where('nombre', $nombre)->exists()) {
                DB::table('permissions')->insert([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }

        $idsPermisos = DB::table('permissions')
            ->whereIn('nombre', array_keys($this->permisos))
            ->pluck('id', 'nombre');

        foreach ($this->roles as $nombreRol => $rol) {
            $rolId = DB::table('roles')->where('nombre', $nombreRol)->value('id');

            if (!$rolId) {
                $rolId = DB::table('roles')->insertGetId([
                    'nombre' => $nombreRol,
                    'descripcion' => $rol['descripcion'],
                    'activo' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            foreach ($rol['permisos'] as $permiso) {
                $this->asignar($rolId, $idsPermisos[$permiso], $ahora);
            }
        }

        // Administrador actual del sistema (rol id 1): todos los permisos.
        if ($existeAdministradorActual) {
            foreach ($idsPermisos as $permisoId) {
                $this->asignar(1, $permisoId, $ahora);
            }
        }
    }

    public function down(): void
    {
        $idsPermisos = DB::table('permissions')
            ->whereIn('nombre', array_keys($this->permisos))
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $idsPermisos)->delete();
        DB::table('permissions')->whereIn('id', $idsPermisos)->delete();

        // Solo se eliminan los roles que no tengan usuarios ni otros permisos.
        foreach (array_keys($this->roles) as $nombreRol) {
            $rolId = DB::table('roles')->where('nombre', $nombreRol)->value('id');

            if (
                $rolId
                && $rolId !== 1
                && !DB::table('role_user')->where('role_id', $rolId)->exists()
                && !DB::table('permission_role')->where('role_id', $rolId)->exists()
            ) {
                DB::table('roles')->where('id', $rolId)->delete();
            }
        }
    }

    private function asignar(int $rolId, int $permisoId, $ahora): void
    {
        $existe = DB::table('permission_role')
            ->where('role_id', $rolId)
            ->where('permission_id', $permisoId)
            ->exists();

        if (!$existe) {
            DB::table('permission_role')->insert([
                'role_id' => $rolId,
                'permission_id' => $permisoId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }
};
