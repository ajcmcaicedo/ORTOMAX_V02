<?php

namespace App\Services\Importaciones;

interface PerfilImportacion
{
    public function nombre(): string;

    public function columnasRequeridas(): array;

    public function normalizarFila(array $fila): array;

    public function validarFila(array $fila, int $numeroFila): array;

    public function clave(array $fila): string;

    public function buscarExistente(array $fila): mixed;

    public function determinarOperacion(array $fila, mixed $existente): string;

    /**
     * Compara los datos del Excel contra el registro existente.
     *
     * Devuelve únicamente los campos que realmente cambiarían.
     */
    public function compararCambios(array $fila, mixed $existente): array;

    public function crear(array $fila): mixed;

    public function actualizar(mixed $existente, array $fila): mixed;
}