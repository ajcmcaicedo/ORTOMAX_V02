<?php

namespace App\Services\NotasEntrega;

use App\Enums\EstadoNotaEntrega;
use App\Enums\TipoItem;
use App\Models\Bitacora;
use App\Models\Equipo;
use App\Models\Institucion;
use App\Models\NotaEntrega;
use App\Models\NotaEntregaDetalle;
use App\Models\NotaEntregaPaciente;
use App\Models\User;
use App\Services\Documentos\NumeradorDocumentos;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ReglaNegocioException;

/**
 * Creación y edición de la Nota de Entrega antes del despacho:
 * cabecera, paciente y equipos.
 *
 * La autorización (Policy) se valida en el controlador; aquí se validan
 * las reglas de negocio y se audita cada cambio.
 */
class ServicioBorradorNota
{
    /** Campos de cabecera editables en borrador/alistamiento. */
    public const CAMPOS_CABECERA = [
        'institucion_id',
        'regional_id',
        'bodega_origen_id',
        'tipo_cirugia_id',
        'medico_id',
        'solicitado_por_id',
        'preparador_id',
        'instrumentador_id',
        'fecha_cirugia_programada',
        'observaciones',
    ];

    public const CAMPOS_PACIENTE = [
        'identificacion',
        'nombres',
        'historia_clinica',
        'aseguradora',
        'confirmado',
    ];

    public function __construct(
        private NumeradorDocumentos $numerador,
        private OcupacionEquipos $ocupacion,
        private TransicionEstadoNota $transicion,
    ) {
    }

    public function crear(array $datos, User $usuario, ?array $paciente = null): NotaEntrega
    {
        return DB::transaction(function () use ($datos, $usuario, $paciente) {
            $cabecera = array_intersect_key($datos, array_flip(self::CAMPOS_CABECERA));

            if (empty($cabecera['regional_id']) && !empty($cabecera['institucion_id'])) {
                $cabecera['regional_id'] = Institucion::whereKey($cabecera['institucion_id'])->value('regional_id');
            }

            $nota = NotaEntrega::create($cabecera + [
                'numero' => $this->numerador->siguienteNotaEntrega(),
                'fecha_emision' => now(),
                'estado' => EstadoNotaEntrega::BORRADOR,
                'creado_por_id' => $usuario->id,
                'solicitado_por_id' => $cabecera['solicitado_por_id'] ?? $usuario->id,
            ]);

            Bitacora::registrar(
                evento: 'NOTA_CREADA',
                auditable: $nota,
                nuevo: $this->valoresAuditables($nota->only(self::CAMPOS_CABECERA)),
                usuarioId: $usuario->id,
            );

            if ($paciente && array_filter($paciente, fn ($v) => $v !== null && $v !== '')) {
                $this->escribirPaciente($nota, $paciente, $usuario);
            }

            return $nota->refresh();
        });
    }

    public function actualizar(NotaEntrega $nota, array $datos, User $usuario, ?int $versionEsperada = null): NotaEntrega
    {
        return DB::transaction(function () use ($nota, $datos, $usuario, $versionEsperada) {
            $bloqueada = $this->bloquearEditable($nota, $versionEsperada);

            $cabecera = array_intersect_key($datos, array_flip(self::CAMPOS_CABECERA));
            $bloqueada->fill($cabecera);

            $cambios = $bloqueada->getDirty();

            if ($cambios === []) {
                return $bloqueada;
            }

            $anterior = array_intersect_key($bloqueada->getOriginal(), $cambios);

            $bloqueada->version++;
            $bloqueada->save();

            Bitacora::registrar(
                evento: 'NOTA_ACTUALIZADA',
                auditable: $bloqueada,
                anterior: $this->valoresAuditables($anterior),
                nuevo: $this->valoresAuditables($cambios),
                usuarioId: $usuario->id,
            );

            return $this->sincronizar($nota, $bloqueada);
        });
    }

    /**
     * Crea o actualiza los datos del paciente. En la bitácora se guardan
     * solo los NOMBRES de los campos cambiados, nunca los valores.
     */
    public function guardarPaciente(NotaEntrega $nota, array $datos, User $usuario): NotaEntregaPaciente
    {
        return DB::transaction(function () use ($nota, $datos, $usuario) {
            $bloqueada = NotaEntrega::whereKey($nota->id)->lockForUpdate()->firstOrFail();

            if ($bloqueada->estado->esFinal()) {
                throw new ReglaNegocioException('La nota está ' . $bloqueada->estado->etiqueta() . '.');
            }

            return $this->escribirPaciente($bloqueada, $datos, $usuario);
        });
    }

    /**
     * Devuelve los datos del paciente y deja constancia del acceso.
     */
    public function verPaciente(NotaEntrega $nota, User $usuario): ?array
    {
        $paciente = $nota->paciente;

        if (!$paciente) {
            return null;
        }

        Bitacora::registrar(
            evento: 'PACIENTE_ACCESO',
            auditable: $paciente,
            notaEntregaId: $nota->id,
            usuarioId: $usuario->id,
        );

        return [
            'identificacion' => $paciente->identificacion,
            'nombres' => $paciente->nombres,
            'historia_clinica' => $paciente->historia_clinica,
            'aseguradora' => $paciente->aseguradora,
            'confirmado' => $paciente->confirmado,
        ];
    }

    /**
     * Agrega un equipo a la nota (línea tipo EQUIPO).
     *
     * - Rechaza equipos no operativos (mantenimiento, inactivos).
     * - Rechaza equipos ocupados en otra nota activa (informa cuál).
     * - Permite equipos que estén en otro BORRADOR (solo advertencia);
     *   el conflicto se resuelve al iniciar el alistamiento.
     *
     * @param string $origen MANUAL | SUGERENCIA
     */
    public function agregarEquipo(NotaEntrega $nota, Equipo $equipo, User $usuario, string $origen = 'MANUAL'): NotaEntregaDetalle
    {
        return DB::transaction(function () use ($nota, $equipo, $usuario, $origen) {
            $bloqueada = $this->bloquearEditable($nota);

            /** @var Equipo $equipo */
            $equipo = Equipo::whereKey($equipo->id)->lockForUpdate()->firstOrFail();

            if (!$equipo->estaOperativo()) {
                throw new EquipoNoDisponibleException(
                    "El equipo {$equipo->codigo} está en estado {$equipo->estado} y no se puede usar."
                );
            }

            $yaEsta = NotaEntregaDetalle::where('nota_entrega_id', $bloqueada->id)
                ->where('tipo_linea', TipoItem::EQUIPO->value)
                ->where('equipo_id', $equipo->id)
                ->exists();

            if ($yaEsta) {
                throw new EquipoNoDisponibleException("El equipo {$equipo->codigo} ya está en esta nota.");
            }

            $bloqueantes = $this->ocupacion->bloqueantes([$equipo->id], $bloqueada->id, true)->get($equipo->id);

            if ($bloqueantes) {
                throw new EquipoNoDisponibleException(
                    "El equipo {$equipo->codigo} está ocupado en " . OcupacionEquipos::describir($bloqueantes->first()) . '.',
                    $bloqueantes->all()
                );
            }

            $linea = NotaEntregaDetalle::create([
                'nota_entrega_id' => $bloqueada->id,
                'equipo_id' => $equipo->id,
                'tipo_linea' => TipoItem::EQUIPO,
                'codigo' => $equipo->codigo,
                'descripcion' => mb_substr($equipo->nombre, 0, 255),
                'cantidad_enviada' => 1,
                'estado' => 'PENDIENTE',
            ]);

            $bloqueada->version++;
            $bloqueada->save();

            Bitacora::registrar(
                evento: 'NOTA_EQUIPO_AGREGADO',
                auditable: $linea,
                notaEntregaId: $bloqueada->id,
                nuevo: [
                    'equipo_id' => $equipo->id,
                    'codigo' => $equipo->codigo,
                    'origen' => $origen,
                ],
                usuarioId: $usuario->id,
            );

            $this->sincronizar($nota, $bloqueada);

            return $linea;
        });
    }

    /**
     * Agrega los equipos de la sugerencia que el preparador confirmó.
     * Cada equipo se procesa por separado: si uno ya no está disponible
     * (otro usuario lo tomó), los demás se agregan igual.
     *
     * @param array<int> $equipoIds
     * @return array{agregados: array<int, NotaEntregaDetalle>, rechazados: array<int, array{equipo_id:int, motivo:string}>}
     */
    public function aceptarSugerencias(NotaEntrega $nota, array $equipoIds, User $usuario): array
    {
        $resultado = ['agregados' => [], 'rechazados' => []];

        foreach (Equipo::whereIn('id', array_unique($equipoIds))->orderBy('codigo')->get() as $equipo) {
            try {
                $resultado['agregados'][] = $this->agregarEquipo($nota, $equipo, $usuario, 'SUGERENCIA');
            } catch (EquipoNoDisponibleException $e) {
                $resultado['rechazados'][] = ['equipo_id' => $equipo->id, 'codigo' => $equipo->codigo, 'motivo' => $e->getMessage()];
            }
        }

        return $resultado;
    }

    /**
     * Retira un equipo de la nota. En alistamiento el motivo es obligatorio.
     */
    public function quitarEquipo(NotaEntrega $nota, NotaEntregaDetalle $linea, User $usuario, ?string $motivo = null): void
    {
        DB::transaction(function () use ($nota, $linea, $usuario, $motivo) {
            $bloqueada = $this->bloquearEditable($nota);

            if ($linea->nota_entrega_id !== $bloqueada->id || $linea->tipo_linea !== TipoItem::EQUIPO) {
                throw new ReglaNegocioException('La línea no es un equipo de esta nota.');
            }

            if ($bloqueada->estado !== EstadoNotaEntrega::BORRADOR && trim((string) $motivo) === '') {
                throw new ReglaNegocioException('Debe indicar el motivo para retirar un equipo en alistamiento.');
            }

            $anterior = ['equipo_id' => $linea->equipo_id, 'codigo' => $linea->codigo];

            $linea->delete();

            $bloqueada->version++;
            $bloqueada->save();

            Bitacora::registrar(
                evento: 'NOTA_EQUIPO_RETIRADO',
                auditable: $linea,
                notaEntregaId: $bloqueada->id,
                anterior: $anterior,
                motivo: $motivo,
                usuarioId: $usuario->id,
            );

            $this->sincronizar($nota, $bloqueada);
        });
    }

    /**
     * BORRADOR → EN_ALISTAMIENTO.
     *
     * Verifica, con los equipos bloqueados, que ninguno esté en otra nota
     * activa (dos borradores con el mismo equipo: gana el primero que
     * inicie el alistamiento). Las reservas de stock se agregan en la Fase 3.
     */
    public function iniciarAlistamiento(NotaEntrega $nota, User $usuario, ?int $versionEsperada = null): NotaEntrega
    {
        return $this->transicion->ejecutar(
            $nota,
            EstadoNotaEntrega::EN_ALISTAMIENTO,
            $usuario,
            versionEsperada: $versionEsperada,
            efectos: function (NotaEntrega $bloqueada) use ($usuario) {
                $equipoIds = $bloqueada->lineasEquipo()->pluck('equipo_id')->filter()->sort()->values()->all();

                if ($equipoIds === []) {
                    throw new ReglaNegocioException('Agregue al menos un equipo antes de iniciar el alistamiento.');
                }

                // Orden fijo para evitar bloqueos cruzados entre notas.
                $equipos = Equipo::whereIn('id', $equipoIds)->orderBy('id')->lockForUpdate()->get();

                foreach ($equipos as $equipo) {
                    if (!$equipo->estaOperativo()) {
                        throw new EquipoNoDisponibleException(
                            "El equipo {$equipo->codigo} está en estado {$equipo->estado}."
                        );
                    }
                }

                $bloqueantes = $this->ocupacion->bloqueantes($equipoIds, $bloqueada->id, true);

                if ($bloqueantes->isNotEmpty()) {
                    $mensajes = $bloqueantes->map(function ($ocupaciones, $equipoId) use ($equipos) {
                        return $equipos->firstWhere('id', $equipoId)->codigo
                            . ' está en ' . OcupacionEquipos::describir($ocupaciones->first());
                    })->values()->implode('; ');

                    throw new EquipoNoDisponibleException(
                        'No se puede iniciar el alistamiento: ' . $mensajes . '.',
                        $bloqueantes->flatten(1)->all()
                    );
                }

                if (!$bloqueada->preparador_id) {
                    $bloqueada->preparador_id = $usuario->id;
                }
            },
        );
    }

    private function escribirPaciente(NotaEntrega $nota, array $datos, User $usuario): NotaEntregaPaciente
    {
        $paciente = NotaEntregaPaciente::firstOrNew(['nota_entrega_id' => $nota->id]);
        $paciente->fill(array_intersect_key($datos, array_flip(self::CAMPOS_PACIENTE)));

        $campos = array_keys($paciente->getDirty());
        $esNuevo = !$paciente->exists;

        if ($campos === [] && !$esNuevo) {
            return $paciente;
        }

        $paciente->save();

        Bitacora::registrar(
            evento: $esNuevo ? 'PACIENTE_REGISTRADO' : 'PACIENTE_ACTUALIZADO',
            auditable: $paciente,
            notaEntregaId: $nota->id,
            nuevo: ['campos' => array_values(array_diff($campos, ['identificacion_hash', 'nota_entrega_id']))],
            usuarioId: $usuario->id,
        );

        return $paciente;
    }

    private function bloquearEditable(NotaEntrega $nota, ?int $versionEsperada = null): NotaEntrega
    {
        /** @var NotaEntrega $bloqueada */
        $bloqueada = NotaEntrega::whereKey($nota->id)->lockForUpdate()->firstOrFail();

        if (!$bloqueada->estado->permiteEditarContenido()) {
            throw new ReglaNegocioException(
                'La nota está ' . $bloqueada->estado->etiqueta()
                . '; solo se modifica en Borrador o En alistamiento.'
            );
        }

        if ($versionEsperada !== null && $bloqueada->version !== $versionEsperada) {
            throw new NotaModificadaException('La nota fue modificada por otro usuario. Recargue la pantalla.');
        }

        return $bloqueada;
    }

    private function sincronizar(NotaEntrega $original, NotaEntrega $bloqueada): NotaEntrega
    {
        $original->setRawAttributes($bloqueada->getAttributes(), true);

        return $original;
    }

    private function valoresAuditables(array $valores): array
    {
        return collect($valores)
            ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d H:i') : $v)
            ->all();
    }
}
