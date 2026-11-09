<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * El preparador recibe los pedidos (a veces creados por el gerente, sin
 * preparador asignado) y crea notas con los datos que llegan, incluido el
 * paciente. Necesita:
 * - notas.ver_todas: ver las notas pendientes de alistar.
 * - pacientes.ver: ver/registrar el paciente (cada acceso queda en bitácora).
 *
 * El instrumentador sigue viendo solo sus notas asignadas.
 */
return new class extends Migration
{
    private array $permisos = ['notas.ver_todas', 'pacientes.ver'];

    public function up(): void
    {
        $rolId = DB::table('roles')->where('nombre', 'PREPARADOR')->value('id');

        if (!$rolId) {
            return;
        }

        foreach (DB::table('permissions')->whereIn('nombre', $this->permisos)->pluck('id') as $permisoId) {
            $existe = DB::table('permission_role')
                ->where('role_id', $rolId)
                ->where('permission_id', $permisoId)
                ->exists();

            if (!$existe) {
                DB::table('permission_role')->insert([
                    'role_id' => $rolId,
                    'permission_id' => $permisoId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $rolId = DB::table('roles')->where('nombre', 'PREPARADOR')->value('id');

        DB::table('permission_role')
            ->where('role_id', $rolId)
            ->whereIn('permission_id', DB::table('permissions')->whereIn('nombre', $this->permisos)->pluck('id'))
            ->delete();
    }
};
