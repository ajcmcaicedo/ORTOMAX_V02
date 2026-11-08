<?php

namespace App\Services\Importaciones\Perfiles;

use App\Models\Institucion;
use App\Services\Importaciones\PerfilImportacion;
use App\Services\Importaciones\ResultadoImportacion;

class InstitucionesImportacion implements PerfilImportacion
{
    /**
     * Nombre del perfil.
     */
    public function nombre(): string
    {
        return 'instituciones';
    }

    /**
     * Columnas obligatorias del Excel.
     */
    public function columnasRequeridas(): array
    {
        return [
            'codigo',
            'tipo',
            'ruc',
            'razon social cliente',
            'clinica hospital',
            'direccion',
            'campo1',
            'campo2',
        ];
    }

    /**
     * Normaliza los datos de una fila.
     */
    public function normalizarFila(array $fila): array
    {
        $resultado = [];

        foreach ($fila as $campo => $valor) {
            $resultado[$campo] = $this->normalizarTexto($valor);
        }

        if (isset($resultado['codigo'])) {
            $resultado['codigo'] = trim($resultado['codigo']);
        }

        if (isset($resultado['tipo'])) {
            $resultado['tipo'] = trim($resultado['tipo']);
        }

        if (isset($resultado['ruc'])) {
            $resultado['ruc'] = trim($resultado['ruc']);
        }

        if (isset($resultado['razon social cliente'])) {
            $resultado['razon social cliente'] =
                trim($resultado['razon social cliente']);
        }

        if (isset($resultado['clinica hospital'])) {
            $resultado['clinica hospital'] =
                trim($resultado['clinica hospital']);
        }

        if (isset($resultado['direccion'])) {
            $resultado['direccion'] =
                trim($resultado['direccion']);
        }

        if (isset($resultado['campo1'])) {
            $resultado['campo1'] =
                trim($resultado['campo1']);
        }

        if (isset($resultado['campo2'])) {
            $resultado['campo2'] =
                trim($resultado['campo2']);
        }

        return $resultado;
    }

        /**
         * Normaliza texto proveniente de Excel.
         *
         * - Convierte espacios especiales (NBSP) en espacios normales.
         * - Elimina espacios al inicio y al final.
         * - Reduce espacios consecutivos.
         */
        protected function normalizarTexto(mixed $valor): string
        {
            $valor = (string) $valor;

            // Espacio no separable (NBSP)
            $valor = str_replace("\xC2\xA0", ' ', $valor);

            // Otros espacios Unicode
            $valor = preg_replace('/\s+/u', ' ', $valor);

            return trim($valor);
        }




    /**
     * Valida una fila.
     */
    public function validarFila(
        array $fila,
        int $numeroFila
    ): array {
        $errores = [];

        if (empty($fila['codigo'])) {
            $errores[] = 'C�digo obligatorio.';
        }

        if (empty($fila['tipo'])) {
            $errores[] = 'Tipo obligatorio.';
        }

        if (empty($fila['razon social cliente'])) {
            $errores[] = 'Raz�n Social_Cliente obligatoria.';
        }

        /*
         * El RUC no se marca como obligatorio
         * para conservar el comportamiento actual.
         */

        return $errores;
    }

    /**
     * Clave �nica del registro.
     */
    public function clave(array $fila): string
    {
        return trim((string) $fila['codigo']);
    }

    /**
     * Busca una instituci�n existente.
     */
    public function buscarExistente(array $fila): mixed
    {
        return Institucion::where(
            'codigo',
            $this->clave($fila)
        )->first();
    }

    /**
     * Determina la operaci�n.
     */
    public function determinarOperacion(
        array $fila,
        mixed $existente
    ): string {
        if ($existente === null) {
            return ResultadoImportacion::NUEVO;
        }

        return ResultadoImportacion::ACTUALIZAR;
    }

    /**
     * Mapeo entre las columnas del Excel
     * y las columnas reales de la base de datos.
     *
     * Excel                         BD
     * ------------------------------------------------
     * codigo                        codigo
     * tipo                          tipo
     * ruc                           ruc
     * razon social cliente          razon_social
     * clinica hospital              nombre_comercial
     * direccion                     direccion
     * campo1                        campo1
     * campo2                        campo2
     */
    protected function datosImportacion(array $fila): array
    {
        return [
            'codigo' => $fila['codigo'] ?? null,

            'tipo' => $fila['tipo'] ?? null,

            'ruc' => $fila['ruc'] ?? null,

            'razon_social' =>
                $fila['razon social cliente'] ?? null,

            'nombre_comercial' =>
                $fila['clinica hospital'] ?? null,

            'direccion' =>
                $fila['direccion'] ?? null,

            'campo1' =>
                $fila['campo1'] ?? null,

            'campo2' =>
                $fila['campo2'] ?? null,
        ];
    }

    /**
     * Compara los datos del Excel contra
     * la instituci�n existente.
     *
     * NO modifica la base de datos.
     *
     * Devuelve �nicamente los campos
     * que realmente presentan diferencias.
     */
    public function compararCambios(
        array $fila,
        mixed $existente
    ): array {
        $datos = $this->datosImportacion($fila);

        /*
         * Aqu� se establece expl�citamente
         * qu� campo de BD corresponde a cada
         * dato importado.
         */
        $campos = [
            'codigo' => [
                'nombre' => 'C�digo',
            ],

            'tipo' => [
                'nombre' => 'Tipo',
            ],

            'ruc' => [
                'nombre' => 'RUC',
            ],

            'razon_social' => [
                'nombre' => 'Raz�n Social',
            ],

            'nombre_comercial' => [
                'nombre' => 'Cl�nica/Hospital',
            ],

            'direccion' => [
                'nombre' => 'Direcci�n',
            ],

            'campo1' => [
                'nombre' => 'Campo1',
            ],

            'campo2' => [
                'nombre' => 'Campo2',
            ],
        ];

        $cambios = [];

        foreach ($campos as $campo => $config) {

            $valorAnterior = trim(
                (string) ($existente->{$campo} ?? '')
            );

            $valorNuevo = trim(
                (string) ($datos[$campo] ?? '')
            );

            if (
                $valorNuevo !== '' &&
                $valorAnterior !== $valorNuevo
            ) {

                $cambios[] = [
                    'campo' => $campo,
                    'nombre' => $config['nombre'],
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo,
                ];
            }
        }

        return $cambios;
    }

    /**
     * Crea una nueva instituci�n.
     */
    public function crear(array $fila): mixed
    {
        $datos = $this->datosImportacion($fila);

        /*
         * Valores iniciales que NO vienen
         * del Excel.
         */
        $datos['activo'] = true;
        $datos['regional_id'] = null;
        $datos['ciudad'] = null;
        $datos['telefono'] = null;
        $datos['email'] = null;

        return Institucion::create($datos);
    }

    /**
     * Actualiza una instituci�n existente.
     *
     * Solamente modifica los campos
     * provenientes del Excel.
     *
     * NO modifica:
     * regional_id
     * ciudad
     * telefono
     * email
     * activo
     */
    public function actualizar(
        mixed $existente,
        array $fila
    ): mixed {
        $datos = $this->datosImportacion($fila);
    
        foreach ($datos as $campo => $valor) {
    
            // Nunca borrar un dato existente con una celda vacía.
            if (
                ($valor === null || $valor === '') &&
                $existente->{$campo} !== null &&
                trim((string) $existente->{$campo}) !== ''
            ) {
                unset($datos[$campo]);
            }
        }
    
        /*
         * NO modificamos:
         *
         * regional_id
         * ciudad
         * telefono
         * email
         * activo
         */
        $existente->update($datos);
    
        return $existente->fresh();
    }
}