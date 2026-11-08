<?php

namespace App\Services\Importaciones\Perfiles;

use App\Models\Producto;
use App\Services\Importaciones\PerfilImportacion;
use App\Services\Importaciones\ResultadoImportacion;

class ProductosImportacion implements PerfilImportacion
{
    /**
     * Nombre del perfil.
     */
    public function nombre(): string
    {
        return 'productos';
    }

    /**
     * Columnas m�nimas requeridas en Excel.
     */
    public function columnasRequeridas(): array
    {
        return [
            'codigo',
        ];
    }

    /**
     * Normaliza una fila del Excel.
     */
    public function normalizarFila(array $fila): array
    {
        $resultado = [];

        foreach ($fila as $campo => $valor) {
            $campo = strtolower(trim((string) $campo));

            if (is_string($valor)) {
                $valor = trim($valor);
            }

            $resultado[$campo] = $valor;
        }

        return $resultado;
    }

    /**
     * Valida una fila.
     */
    public function validarFila(array $fila, int $numeroFila): array
    {
        $errores = [];

        $codigo = trim((string) ($fila['codigo'] ?? ''));

        if ($codigo === '') {
            $errores[] = "Fila {$numeroFila}: el c�digo del producto es obligatorio.";
        }

        return $errores;
    }

    /**
     * Clave �nica del producto.
     */
    public function clave(array $fila): string
    {
        return strtoupper(
            trim((string) ($fila['codigo'] ?? ''))
        );
    }

    /**
     * Busca el producto existente por c�digo.
     */
    public function buscarExistente(array $fila): mixed
    {
        $codigo = trim((string) ($fila['codigo'] ?? ''));

        if ($codigo === '') {
            return null;
        }

        return Producto::where('codigo', $codigo)->first();
    }

    /**
     * Determina la operaci�n que debe realizarse.
     */
    public function determinarOperacion(array $fila, mixed $existente): string
    {
        if (!$existente) {
            return ResultadoImportacion::NUEVO;
        }
    
        if (
            $existente->origen_producto === 'CONTIFICO' ||
            $existente->estado_contifico === 'SINCRONIZADO'
        ) {
            return ResultadoImportacion::SIN_CAMBIOS;
        }
    
        if (
            $existente->origen_producto === 'EXCEL' &&
            $existente->estado_contifico === 'PENDIENTE'
        ) {
            return ResultadoImportacion::ACTUALIZAR;
        }
    
        return ResultadoImportacion::SIN_CAMBIOS;
    }

    /**
     * Determina qu� datos cambiar�an.
     *
     * Los campos maestros de Cont�fico y los campos
     * propios de ORTOMAX quedan protegidos.
     */
    public function compararCambios(
        array $fila,
        mixed $existente
    ): array {
        if (!$existente) {
            return [];
        }
    
        /*
         * Si el producto ya pertenece a Contífico,
         * Excel no modifica nada.
         */
        if (
            strtoupper((string) $existente->origen_producto) === 'CONTIFICO'
            ||
            strtoupper((string) $existente->estado_contifico) === 'SINCRONIZADO'
        ) {
            return [];
        }
    
        /*
         * Campos que permitimos mantener temporalmente
         * desde Excel.
         *
         * IMPORTANTE:
         * No incluimos codigojbg ni los campos ORTOMAX.
         */
        $camposPermitidos = [
            'nombre',
            'descripcion',
            'codigo_barra',
            'marca',
            'categoria',
            'tipo',
            'tipo_producto',
            'porcentaje_iva',
            'costo_maximo',
        ];
    
        $cambios = [];
    
        foreach ($camposPermitidos as $campo) {
            if (!array_key_exists($campo, $fila)) {
                continue;
            }
    
            $nuevo = $fila[$campo];
    
            /*
             * Una celda vacía del Excel NO borra información
             * existente.
             */
            if (
                $nuevo === null ||
                (is_string($nuevo) && trim($nuevo) === '')
            ) {
                continue;
            }
    
            $actual = $existente->{$campo};
    
            /*
             * Los campos numéricos deben compararse
             * numéricamente y no como texto.
             *
             * Ejemplo:
             * 15.00 y 15 representan el mismo valor.
             */
            if (in_array($campo, ['porcentaje_iva', 'costo_maximo'], true)) {
                if ((float) $actual === (float) $nuevo) {
                    continue;
                }
            } else {
                if ((string) $actual === (string) $nuevo) {
                    continue;
                }
            }
    
            $cambios[$campo] = [
                'anterior' => $actual,
                'nuevo' => $nuevo,
            ];
        }
    
        return $cambios;
    }

    /**
     * Crea un producto temporal desde Excel.
     */
    public function crear(array $fila): mixed
    {
        $datos = [
            'codigo' => trim((string) ($fila['codigo'] ?? '')),

            /*
             * Producto temporal.
             */
            'contifico_id' => null,
            'origen_producto' => 'EXCEL',
            'estado_contifico' => 'PENDIENTE',
        ];

        /*
         * Campos permitidos desde Excel.
         */
        $camposPermitidos = [
            'nombre',
            'descripcion',
            'codigo_barra',
            'marca',
            'categoria',
            'tipo',
            'tipo_producto',
            'porcentaje_iva',
            'costo_maximo',
        ];

        foreach ($camposPermitidos as $campo) {
            if (!array_key_exists($campo, $fila)) {
                continue;
            }

            $valor = $fila[$campo];

            /*
             * No guardamos campos vac�os innecesariamente.
             */
            if (
                $valor === null ||
                (is_string($valor) && trim($valor) === '')
            ) {
                continue;
            }

            $datos[$campo] = $valor;
        }

        return Producto::create($datos);
    }

    /**
     * Actualiza un producto temporal.
     */
    public function actualizar(
        mixed $existente,
        array $fila
    ): mixed {
        /*
         * Seguridad adicional:
         * jam�s modificar un producto ya sincronizado
         * con Cont�fico.
         */
        if (
            strtoupper((string) $existente->origen_producto) === 'CONTIFICO'
            ||
            strtoupper((string) $existente->estado_contifico) === 'SINCRONIZADO'
        ) {
            return $existente;
        }

        $camposPermitidos = [
            'nombre',
            'descripcion',
            'codigo_barra',
            'marca',
            'categoria',
            'tipo',
            'tipo_producto',
            'porcentaje_iva',
            'costo_maximo',
        ];

        $datos = [];

        foreach ($camposPermitidos as $campo) {
            if (!array_key_exists($campo, $fila)) {
                continue;
            }

            $valor = $fila[$campo];

            /*
             * Una celda vac�a NO borra informaci�n existente.
             */
            if (
                $valor === null ||
                (is_string($valor) && trim($valor) === '')
            ) {
                continue;
            }

            $datos[$campo] = $valor;
        }

        if (!empty($datos)) {
            $existente->fill($datos);
            $existente->save();
        }

        return $existente;
    }
}