<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permisos = [
            [
                'nombre' => 'instituciones.ver',
                'descripcion' => 'Ver instituciones',
            ],
            [
                'nombre' => 'instituciones.crear',
                'descripcion' => 'Crear instituciones',
            ],
            [
                'nombre' => 'instituciones.editar',
                'descripcion' => 'Editar instituciones',
            ],
            [
                'nombre' => 'instituciones.eliminar',
                'descripcion' => 'Eliminar instituciones',
            ],
            [
                'nombre' => 'instituciones.importar',
                'descripcion' => 'Importar instituciones desde Excel',
            ],
            [
                'nombre' => 'instituciones.exportar',
                'descripcion' => 'Exportar instituciones a Excel',
            ],
        ];

        foreach ($permisos as $permiso) {
            DB::table('permissions')->updateOrInsert(
                ['nombre' => $permiso['nombre']],
                [
                    'descripcion' => $permiso['descripcion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $roleId = DB::table('roles')
            ->where('id', 1)
            ->value('id');

        if ($roleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('nombre', array_column($permisos, 'nombre'))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('nombre', [
                'instituciones.ver',
                'instituciones.crear',
                'instituciones.editar',
                'instituciones.eliminar',
                'instituciones.importar',
                'instituciones.exportar',
            ])
            ->pluck('id');

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};