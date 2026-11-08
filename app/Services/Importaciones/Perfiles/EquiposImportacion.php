<?php

namespace App\Services\Importaciones\Perfiles;

use App\Models\Equipo;
use App\Models\Producto;
use App\Services\Importaciones\PerfilImportacion;

class EquiposImportacion implements PerfilImportacion
{
    /**
     * Nombre del perfil.
     */
    public function nombre(): string
    {
        return 'equipos';
    }

    /**
     * Columnas mínimas que debe contener el Excel.
     */
    public function columnasRequeridas(): array
    {
        return [
            'regional',
            'codigo referencia',
            'codigo',
            'tipo',
            'grupo',
            'subgrupo',
            'bandeja',
            'orden',
            'producto',
            'serie',
            'descripcion',
            'numero lote',
            'cantidad',
            'estado',
        ];
    }

    /**
     * Normaliza los datos de una fila.
     *
     * IMPORTANTE:
     * Esta normalización es solamente para trabajar internamente.
     * No modifica los valores originales del Excel.
     */
    public function normalizarFila(array $fila): array
    {
        $camposTexto = [
            'regional',
            'codigo referencia',
            'codigo',
            'tipo',
            'grupo',
            'subgrupo',
            'bandeja',
            'producto',
            'serie',
            'descripcion',
            'numero lote',
            'estado',
        ];

        foreach ($camposTexto as $campo) {
            if (array_key_exists($campo, $fila)) {
                $fila[$campo] = $this->normalizarTexto($fila[$campo]);
            }
        }

        return $fila;
    }

    /**
     * Validaciones de cada fila.
     */
    public function validarFila(array $fila, int $numeroFila): array
    {
        $errores = [];

        if ($this->vacio($fila['regional'] ?? null)) {
            $errores[] = "Fila {$numeroFila}: REGIONAL es obligatorio.";
        }

        if ($this->vacio($fila['codigo'] ?? null)) {
            $errores[] = "Fila {$numeroFila}: CODIGO es obligatorio.";
        }

        if ($this->vacio($fila['codigo referencia'] ?? null)) {
            $errores[] = "Fila {$numeroFila}: CODIGO_REFERENCIA es obligatorio.";
        }

        if ($this->vacio($fila['producto'] ?? null)) {
            $errores[] = "Fila {$numeroFila}: PRODUCTO es obligatorio.";
        }

        /*
         * La cantidad debe ser numérica cuando exista.
         */
        if (
            isset($fila['cantidad']) &&
            $fila['cantidad'] !== '' &&
            !is_numeric($fila['cantidad'])
        ) {
            $errores[] = "Fila {$numeroFila}: CANTIDAD debe ser numérica.";
        }

        /*
         * ORDEN puede estar vacío en el Excel actual,
         * por eso NO lo hacemos obligatorio.
         */
        return $errores;
    }

    /**
     * Clave del equipo físico.
     *
     * CODIGO identifica al equipo.
     * CODIGO_REFERENCIA NO se utiliza como clave.
     */
    public function clave(array $fila): string
    {
        return $this->normalizarClave($fila['codigo'] ?? '');
    }

    /**
     * Busca el equipo físico existente.
     */
    public function buscarExistente(array $fila): mixed
    {
        $codigo = $this->normalizarClave($fila['codigo'] ?? '');

        if ($codigo === '') {
            return null;
        }

        return Equipo::where('codigo', $codigo)->first();
    }

    /**
     * Determina si el equipo es nuevo o debe actualizarse.
     *
     * IMPORTANTE:
     * Aquí solamente analizamos la cabecera del equipo.
     * El contenido se manejará posteriormente mediante
     * EquipoInventario.
     */
    public function determinarOperacion(
        array $fila,
        mixed $existente
    ): string {
        if (!$existente) {
            return 'nuevo';
        }

        $cambios = $this->compararCambios($fila, $existente);

        return empty($cambios)
            ? 'sin_cambios'
            : 'actualizar';
    }

    /**
     * Compara los datos del equipo físico.
     */
    public function compararCambios(
        array $fila,
        mixed $existente
    ): array {
        $cambios = [];

        $campos = [
            'codigo' => 'codigo',
            'codigo referencia' => 'codigo_referencia',
            'tipo' => 'tipo_equipo',
            'descripcion' => 'descripcion',
            'estado' => 'estado',
        ];

        foreach ($campos as $excel => $bd) {

            $nuevo = $this->normalizarTexto($fila[$excel] ?? '');
            $actual = $this->normalizarTexto($existente->{$bd} ?? '');

            /*
             * Un valor vacío del Excel NO borra
             * información existente en la BD.
             */
            if ($nuevo === '') {
                continue;
            }

            if ($nuevo !== $actual) {
                $cambios[$bd] = [
                    'anterior' => $existente->{$bd},
                    'nuevo' => $fila[$excel],
                ];
            }
        }

        return $cambios;
    }

    /**
     * Crea el equipo.
     *
     * NOTA:
     * Esta primera versión NO se utilizará todavía para
     * ejecutar la importación definitiva.
     *
     * Primero validaremos toda la lógica con el Excel.
     */
    public function crear(array $fila): mixed
    {
        return Equipo::create([
            'codigo' => $this->normalizarClave($fila['codigo'] ?? ''),
            'codigo_referencia' => $this->normalizarClave(
                $fila['codigo referencia'] ?? ''
            ),
            'nombre' => $fila['descripcion'] ?? null,
            'descripcion' => $fila['descripcion'] ?? null,
            'tipo_equipo' => $fila['tipo'] ?? null,
            'estado' => $fila['estado'] ?: 'ACTIVO',
            'activo' => true,
        ]);
    }

    /**
     * Actualiza un equipo existente.
     *
     * Los datos de regional y bodega NO se tocan aquí.
     * Serán resueltos por una capa específica de ubicación.
     */
    public function actualizar(
        mixed $existente,
        array $fila
    ): mixed {
        $datos = [];

        $mapa = [
            'codigo referencia' => 'codigo_referencia',
            'tipo' => 'tipo_equipo',
            'descripcion' => 'descripcion',
            'estado' => 'estado',
        ];

        foreach ($mapa as $excel => $bd) {

            $valor = $this->normalizarTexto($fila[$excel] ?? '');

            if ($valor === '') {
                continue;
            }

            $datos[$bd] = $fila[$excel];
        }

        if (!empty($datos)) {
            $existente->update($datos);
        }

        return $existente->fresh();
    }

    /**
     * Normalización de texto.
     */
    private function normalizarTexto(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        $valor = (string) $valor;

        $valor = str_replace(
            ["\xC2\xA0", "\xE2\x80\xAF"],
            ' ',
            $valor
        );

        $valor = preg_replace('/\s+/u', ' ', $valor);

        return trim($valor);
    }

    /**
     * Normalización de claves.
     */
    private function normalizarClave(mixed $valor): string
    {
        return strtoupper($this->normalizarTexto($valor));
    }

    /**
     * Determina si un valor está vacío.
     */
    private function vacio(mixed $valor): bool
    {
        return $this->normalizarTexto($valor) === '';
    }
}