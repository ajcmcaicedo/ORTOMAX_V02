<?php

namespace App\Services\Importaciones;

class ResultadoImportacion
{
    public const NUEVO = 'nuevo';
    public const ACTUALIZAR = 'actualizar';
    public const SIN_CAMBIOS = 'sin_cambios';
    public const ERROR = 'error';

    protected array $filas = [];

    protected array $nuevos = [];

    protected array $actualizar = [];

    protected array $sinCambios = [];

    protected array $errores = [];

    /**
     * Agrega una fila nueva.
     */
    public function agregarNuevo(array $fila): void
    {
        $this->nuevos[] = $fila;

        $this->filas[] = [
            'estado' => self::NUEVO,
            'fila' => $fila,
        ];
    }

    /**
     * Agrega una fila que será actualizada.
     *
     * Puede incluir los cambios detectados.
     */
    public function agregarActualizar(
        array $fila,
        array $cambios = []
    ): void {
        $registro = [
            'estado' => self::ACTUALIZAR,
            'fila' => $fila,
            'cambios' => $cambios,
        ];

        $this->actualizar[] = $registro;

        $this->filas[] = $registro;
    }

    /**
     * Agrega una fila sin cambios.
     */
    public function agregarSinCambios(
        array $fila
    ): void {
        $registro = [
            'estado' => self::SIN_CAMBIOS,
            'fila' => $fila,
            'cambios' => [],
        ];

        $this->sinCambios[] = $registro;

        $this->filas[] = $registro;
    }

    /**
     * Agrega una fila con errores.
     */
    public function agregarError(
        array $fila,
        array $errores
    ): void {
        $fila['errores'] = $errores;

        $this->errores[] = $fila;

        $this->filas[] = [
            'estado' => self::ERROR,
            'fila' => $fila,
            'errores' => $errores,
        ];
    }

    /**
     * Devuelve todas las filas analizadas.
     */
    public function filas(): array
    {
        return $this->filas;
    }

    /**
     * Devuelve las filas nuevas.
     */
    public function nuevos(): array
    {
        return $this->nuevos;
    }

    /**
     * Devuelve las filas que serán actualizadas.
     */
    public function actualizar(): array
    {
        return $this->actualizar;
    }

    /**
     * Devuelve las filas sin cambios.
     */
    public function sinCambios(): array
    {
        return $this->sinCambios;
    }

    /**
     * Devuelve las filas con errores.
     */
    public function errores(): array
    {
        return $this->errores;
    }

    /**
     * Cantidad de registros nuevos.
     */
    public function totalNuevos(): int
    {
        return count($this->nuevos);
    }

    /**
     * Cantidad de registros a actualizar.
     */
    public function totalActualizar(): int
    {
        return count($this->actualizar);
    }

    /**
     * Cantidad de registros sin cambios.
     */
    public function totalSinCambios(): int
    {
        return count($this->sinCambios);
    }

    /**
     * Cantidad de errores.
     */
    public function totalErrores(): int
    {
        return count($this->errores);
    }

    /**
     * Cantidad total de filas procesadas.
     */
    public function total(): int
    {
        return count($this->filas);
    }

    /**
     * Indica si existen errores.
     */
    public function tieneErrores(): bool
    {
        return !empty($this->errores);
    }

    /**
     * Convierte el resultado a un arreglo.
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total(),
            'nuevos' => $this->totalNuevos(),
            'actualizar' => $this->totalActualizar(),
            'sin_cambios' => $this->totalSinCambios(),
            'errores' => $this->totalErrores(),
            'filas' => $this->filas(),
        ];
    }
}