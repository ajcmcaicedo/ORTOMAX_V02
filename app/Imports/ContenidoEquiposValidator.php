<?php

namespace App\Imports;


use App\Models\Producto;
use App\Models\Activo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ContenidoEquiposValidator implements ToCollection
{
    public function __construct(
        protected ValidadorOrtomaImport $validador
    ) {
    }

    public function collection(Collection $rows)
    {
            // Productos por código
        $productos = Producto::query()
            ->whereNotNull('codigo')
            ->get(['id', 'codigo'])
            ->mapWithKeys(function ($producto) {
                return [
                    strtoupper(trim((string) $producto->codigo)) => $producto->id
                ];
            })
            ->all();


        // Activos por código
        $activos = Activo::query()
            ->whereNotNull('codigo')
            ->get(['id', 'codigo', 'producto_id'])
            ->mapWithKeys(function ($activo) {
                return [
                    strtoupper(trim((string) $activo->codigo)) => [
                        'id' => $activo->id,
                        'producto_id' => $activo->producto_id,
                    ]
                ];
            })
            ->all();

        /*
         * Para detectar duplicados dentro del Excel.
         */
        $filasContenido = [];

        foreach ($rows as $index => $row) {

            // Primera fila = encabezados
            if ($index === 0) {
                continue;
            }

            $fila = $index + 1;

            /*
             * Lectura de columnas.
             */
            $codigoEquipo = trim((string) ($row[0] ?? ''));
            $tipo = strtoupper(trim((string) ($row[1] ?? '')));
            $grupo = trim((string) ($row[2] ?? ''));
            $nombreGrupo = trim((string) ($row[3] ?? ''));
            $subgrupo = trim((string) ($row[4] ?? ''));
            $nombreSubgrupo = trim((string) ($row[5] ?? ''));
            $bandeja = trim((string) ($row[6] ?? ''));
            $orden = $row[7] ?? null;
            $codigo = trim((string) ($row[8] ?? ''));
            $codigoActivo = trim((string) ($row[9] ?? ''));
            $numeroLote = trim((string) ($row[10] ?? ''));
            $cantidad = $row[11] ?? null;
            $estado = strtoupper(trim((string) ($row[12] ?? '')));

            /*
             * Si toda la fila está vacía, la ignoramos.
             */
            if (
                $codigoEquipo === '' &&
                $tipo === '' &&
                $grupo === '' &&
                $subgrupo === '' &&
                $codigo === '' &&
                $codigoActivo === '' &&
                $numeroLote === '' &&
                ($cantidad === null || $cantidad === '') &&
                $estado === ''
            ) {
                continue;
            }

            /*
             * Código de equipo obligatorio.
             */
            if ($codigoEquipo === '') {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'codigo_equipo',
                    'El código del equipo es obligatorio.'
                );

                continue;
            }

            /*
             * Verificar que el equipo exista.
             */
            $codigoEquipoBusqueda = strtoupper($codigoEquipo);

            if (!isset($this->validador->equipos[$codigoEquipoBusqueda])) {
            
                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'codigo_equipo',
                    "El equipo '$codigoEquipo' no existe en la hoja EQUIPOS."
                );
            
                continue;
            }
            
            $equipo = $this->validador->equipos[$codigoEquipoBusqueda];

            /*
             * Tipo obligatorio.
             */
            if ($tipo === '') {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'tipo',
                    'El tipo es obligatorio. Use IMPLANTE o INSTRUMENTAL.'
                );
            } elseif (!in_array($tipo, ['IMPLANTE', 'INSTRUMENTAL'], true)) {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'tipo',
                    "Tipo inválido '$tipo'. Use IMPLANTE o INSTRUMENTAL."
                );
            }

            /*
             * Código obligatorio.
             */
            if ($codigo === '') {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'codigo',
                    'El código es obligatorio.'
                );

                continue;
            }

            $codigoBusqueda = strtoupper($codigo);

            /*
             * IMPLANTE
             *
             * Debe existir en productos.
             */
            if ($tipo === 'IMPLANTE') {

                if (!isset($productos[$codigoBusqueda])) {

                    $this->validador->error(
                        'CONTENIDO_EQUIPOS',
                        $fila,
                        'codigo',
                        "El implante '$codigo' no existe en productos."
                    );
                }
            }

            /*
             * INSTRUMENTAL
             *
             * Puede ser un código interno ORTOMAX.
             *
             * No exigimos que exista en productos.
             */
            if ($tipo === 'INSTRUMENTAL') {

                /*
                 * Si se proporciona un código de activo,
                 * lo validaremos más adelante.
                 */
            }

            /*
             * Validación de grupo.
             */
            if ($grupo === '') {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'grupo',
                    'El grupo es obligatorio.'
                );
            }

            /*
             * Validación de subgrupo.
             */
            if ($subgrupo === '') {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'subgrupo',
                    'El subgrupo es obligatorio.'
                );
            }

            /*
             * Validación de orden.
             */
            if (
                $orden === null ||
                $orden === '' ||
                !is_numeric($orden)
            ) {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'orden',
                    'El orden debe ser un número.'
                );
            }

            /*
             * Validación de cantidad.
             */
            if (
                $cantidad === null ||
                $cantidad === '' ||
                !is_numeric($cantidad) ||
                (float) $cantidad <= 0
            ) {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'cantidad',
                    'La cantidad debe ser un número mayor que cero.'
                );
            }

            /*
             * Estado.
             */
            if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'estado',
                    "Estado inválido '$estado'. Use ACTIVO o INACTIVO."
                );
            }

            /*
             * Lote:
             *
             * Por ahora solamente lo validamos cuando
             * existe un número de lote y el producto existe.
             */
            if ($numeroLote !== '' && isset($productos[$codigoBusqueda])) {

                $claveLote =
                    $codigoBusqueda . '|' .
                    strtoupper($numeroLote);
            
                if (!isset($this->validador->lotes[$claveLote])) {
            
                    $this->validador->error(
                        'CONTENIDO_EQUIPOS',
                        $fila,
                        'numero_lote',
                        "El lote '$numeroLote' no existe para el producto '$codigo' en la hoja LOTES."
                    );
                }
            }

            /*
             * Código de activo.
             */
            if ($codigoActivo !== '') {

                $codigoActivoBusqueda = strtoupper($codigoActivo);

                if (!isset($activos[$codigoActivoBusqueda])) {

                    $this->validador->error(
                        'CONTENIDO_EQUIPOS',
                        $fila,
                        'codigo_activo',
                        "El activo '$codigoActivo' no existe en MySQL."
                    );
                } elseif (isset($productos[$codigoBusqueda])) {

                    /*
                     * Si ambos existen, comprobamos que el activo
                     * pertenezca al producto indicado.
                     */
                    $productoId = $productos[$codigoBusqueda];

                    if (
                        $activos[$codigoActivoBusqueda]['producto_id']
                        != $productoId
                    ) {

                        $this->validador->error(
                            'CONTENIDO_EQUIPOS',
                            $fila,
                            'codigo_activo',
                            "El activo '$codigoActivo' no pertenece al producto '$codigo'."
                        );
                    }
                }
            }

            /*
             * Detectar filas duplicadas.
             *
             * No consideramos lote porque el mismo producto
             * puede aparecer con diferentes lotes.
             */
            $claveDuplicado =
                $codigoEquipoBusqueda . '|' .
                $tipo . '|' .
                $grupo . '|' .
                $subgrupo . '|' .
                $bandeja . '|' .
                $orden . '|' .
                $codigoBusqueda . '|' .
                strtoupper($codigoActivo);

            if (isset($filasContenido[$claveDuplicado])) {

                $this->validador->error(
                    'CONTENIDO_EQUIPOS',
                    $fila,
                    'codigo',
                    'Esta fila está duplicada en CONTENIDO_EQUIPOS.'
                );

            } else {

                $filasContenido[$claveDuplicado] = $fila;

            }

             /*
             * Guardar la fila validada para que el importador
             * pueda procesarla posteriormente.
             */
            $this->validador->contenidos[] = [
                'fila' => $fila,
                'codigo_equipo' => $codigoEquipo,
                'tipo' => $tipo,
                'grupo' => $grupo,
                'nombre_grupo' => $nombreGrupo,
                'subgrupo' => $subgrupo,
                'nombre_subgrupo' => $nombreSubgrupo,
                'bandeja' => $bandeja,
                'orden' => is_numeric($orden) ? (int) $orden : null,
                'codigo' => $codigo,
                'codigo_activo' => $codigoActivo !== '' ? $codigoActivo : null,
                'numero_lote' => $numeroLote !== '' ? $numeroLote : null,
                'cantidad' => is_numeric($cantidad) ? (float) $cantidad : null,
                'estado' => $estado,
            ];

        }
    }
}