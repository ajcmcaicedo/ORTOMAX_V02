<?php

namespace App\Services\Importaciones\Perfiles;

use App\Models\Lote;
use App\Models\Producto;
use App\Services\Importaciones\PerfilImportacion;
use App\Services\Importaciones\ResultadoImportacion;
use Carbon\Carbon;
use DateTime;

class LotesImportacion implements PerfilImportacion
{
    protected ?array $productosCache = null;
    protected ?array $lotesCache = null;

    protected array $combinacionesVistas = [];

    /**
     * Fields used by the import process.
     */
    protected array $camposComparables = [
        'fecha_fabricacion',
        'fecha_vencimiento',
        'registro_sanitario',
        'estado',
        'observaciones',
    ];

    public function nombre(): string
    {
        return 'lotes';
    }

    /**
     * All seven headers are required.
     * An empty cell means the value must be cleared.
     */
    public function columnasRequeridas(): array
    {
        return [
            'codigo producto',
            'numero lote',
            'fecha fabricacion',
            'fecha vencimiento',
            'registro sanitario',
            'estado',
            'observaciones',
        ];
    }

    /**
     * Normalize Excel row values.
     */
    public function normalizarFila(array $fila): array
    {
        $equivalencias = [
            'codigo producto' => 'codigo_producto',
            'numero lote' => 'numero_lote',
            'fecha fabricacion' => 'fecha_fabricacion',
            'fecha vencimiento' => 'fecha_vencimiento',
            'registro sanitario' => 'registro_sanitario',
            'estado' => 'estado',
            'observaciones' => 'observaciones',
        ];

        $resultado = [];

        foreach ($fila as $campo => $valor) {
            $campo = strtolower(trim((string) $campo));
            $destino = $equivalencias[$campo] ?? $campo;

            if (is_string($valor)) {
                $valor = trim($valor);
            }

            if (in_array($destino, [
                'fecha_fabricacion',
                'fecha_vencimiento',
            ], true)) {
                $valor = $this->normalizarFecha($valor);
            }

            if ($destino === 'codigo_producto') {
                $valor = strtoupper(trim((string) $valor));
            }

            if ($destino === 'numero_lote') {
                $valor = trim((string) $valor);
            }

            if (in_array($destino, [
                'registro_sanitario',
                'observaciones',
            ], true)) {
                $valor = $this->textoONull($valor);
            }

            if ($destino === 'estado') {
                $valor = strtoupper(trim((string) $valor));

                // estado is NOT NULL in the database.
                if ($valor === '') {
                    $valor = 'ACTIVO';
                }
            }

            $resultado[$destino] = $valor;
        }

        // Explicitly include optional fields even when blank.
        foreach ($this->camposComparables as $campo) {
            if (!array_key_exists($campo, $resultado)) {
                $resultado[$campo] = null;
            }
        }

        if (empty($resultado['estado'])) {
            $resultado['estado'] = 'ACTIVO';
        }

        return $resultado;
    }

    /**
     * Validate row values.
     */
    public function validarFila(array $fila, int $numeroFila): array
    {
        $errores = [];

        $codigo = trim((string) ($fila['codigo_producto'] ?? ''));
        $numeroLote = trim((string) ($fila['numero_lote'] ?? ''));

        if ($codigo === '') {
            $errores[] = "Fila {$numeroFila}: falta el codigo del producto.";
        }

        if ($numeroLote === '') {
            $errores[] = "Fila {$numeroFila}: falta el numero de lote.";
        }

        if (mb_strlen($numeroLote) > 100) {
            $errores[] = "Fila {$numeroFila}: el numero de lote supera 100 caracteres.";
        }

        foreach ([
            'fecha_fabricacion' => 'fabricacion',
            'fecha_vencimiento' => 'vencimiento',
        ] as $campo => $nombre) {

            $fecha = $fila[$campo] ?? null;

            if ($fecha !== null && !$this->fechaValida($fecha)) {
                $errores[] = "Fila {$numeroFila}: fecha de {$nombre} invalida.";
            }
        }

        $registro = $fila['registro_sanitario'] ?? null;

        if ($registro !== null && mb_strlen($registro) > 100) {
            $errores[] = "Fila {$numeroFila}: el registro sanitario supera 100 caracteres.";
        }

        $estado = strtoupper(trim((string) ($fila['estado'] ?? 'ACTIVO')));

        if (mb_strlen($estado) > 30) {
            $errores[] = "Fila {$numeroFila}: el estado supera 30 caracteres.";
        }

        if ($codigo !== '' && !$this->productoPorCodigo($codigo)) {
            $errores[] = "Fila {$numeroFila}: el producto {$codigo} no existe en ORTOMAX.";
        }

        // Detect duplicate product + lot combinations in the same Excel.
        if ($codigo !== '' && $numeroLote !== '') {
            $clave = $this->normalizarCodigo($codigo)
                . '|'
                . strtoupper($numeroLote);

            if (isset($this->combinacionesVistas[$clave])) {
                $filaAnterior = $this->combinacionesVistas[$clave];

                $errores[] =
                    "Fila {$numeroFila}: lote duplicado en el Excel. "
                    . "Ya aparece en la fila {$filaAnterior}.";
            } else {
                $this->combinacionesVistas[$clave] = $numeroFila;
            }
        }

        return $errores;
    }

    public function clave(array $fila): string
    {
        return $this->normalizarCodigo(
            $fila['codigo_producto'] ?? ''
        ) . '|'
        . strtoupper(trim((string) ($fila['numero_lote'] ?? '')));
    }

    /**
     * Find existing lot by product + lot number.
     */
    public function buscarExistente(array $fila): mixed
    {
        $producto = $this->productoPorCodigo(
            $fila['codigo_producto'] ?? ''
        );

        if (!$producto) {
            return null;
        }

        $this->cargarLotes();

        $clave = $producto->id
            . '|'
            . strtoupper(trim((string) $fila['numero_lote']));

        return $this->lotesCache[$clave] ?? null;
    }

    /**
     * Determine whether the row is new, unchanged or updated.
     */
    public function determinarOperacion(
        array $fila,
        mixed $existente
    ): string {
        if (!$existente) {
            return ResultadoImportacion::NUEVO;
        }

        $cambios = $this->compararCambios($fila, $existente);

        return empty($cambios)
            ? ResultadoImportacion::SIN_CAMBIOS
            : ResultadoImportacion::ACTUALIZAR;
    }

    /**
     * Compare all editable fields.
     */
    public function compararCambios(
        array $fila,
        mixed $existente
    ): array {
        $cambios = [];

        foreach ($this->camposComparables as $campo) {
            $actual = $this->valorActual($existente, $campo);
            $nuevo = $this->valorNuevo($fila[$campo] ?? null, $campo);

            if ($actual !== $nuevo) {
                $cambios[$campo] = [
                    'actual' => $actual,
                    'nuevo' => $nuevo,
                ];
            }
        }

        return $cambios;
    }

    /**
     * Create a new lot.
     */
    public function crear(array $fila): mixed
    {
        $producto = $this->productoPorCodigo(
            $fila['codigo_producto'] ?? ''
        );

        if (!$producto) {
            throw new \RuntimeException(
                'No se encontro el producto: '
                . ($fila['codigo_producto'] ?? '')
            );
        }

        return Lote::create([
            'producto_id' => $producto->id,
            'numero_lote' => trim((string) $fila['numero_lote']),
            'fecha_fabricacion' => $fila['fecha_fabricacion'] ?? null,
            'fecha_vencimiento' => $fila['fecha_vencimiento'] ?? null,
            'registro_sanitario' => $fila['registro_sanitario'] ?? null,
            'estado' => $fila['estado'] ?: 'ACTIVO',
            'observaciones' => $fila['observaciones'] ?? null,
        ]);
    }

    /**
     * Update an existing lot.
     * Empty optional Excel cells are saved as NULL.
     */
    public function actualizar(
        mixed $existente,
        array $fila
    ): mixed {
        $existente->fecha_fabricacion =
            $fila['fecha_fabricacion'] ?? null;

        $existente->fecha_vencimiento =
            $fila['fecha_vencimiento'] ?? null;

        $existente->registro_sanitario =
            $fila['registro_sanitario'] ?? null;

        $existente->estado =
            !empty($fila['estado']) ? $fila['estado'] : 'ACTIVO';

        $existente->observaciones =
            $fila['observaciones'] ?? null;

        $existente->save();

        return $existente;
    }

    protected function cargarProductos(): void
    {
        if ($this->productosCache !== null) {
            return;
        }

        $this->productosCache = [];

        Producto::query()
            ->select(['id', 'codigo'])
            ->orderBy('id')
            ->chunk(1000, function ($productos) {
                foreach ($productos as $producto) {
                    $clave = $this->normalizarCodigo($producto->codigo);
                    $this->productosCache[$clave] = $producto;
                }
            });
    }

    protected function productoPorCodigo(string $codigo): ?Producto
    {
        $this->cargarProductos();

        $clave = $this->normalizarCodigo($codigo);

        return $this->productosCache[$clave] ?? null;
    }

    protected function cargarLotes(): void
    {
        if ($this->lotesCache !== null) {
            return;
        }

        $this->lotesCache = [];

        Lote::query()
            ->select([
                'id',
                'producto_id',
                'numero_lote',
                'fecha_fabricacion',
                'fecha_vencimiento',
                'registro_sanitario',
                'estado',
                'observaciones',
            ])
            ->chunk(1000, function ($lotes) {
                foreach ($lotes as $lote) {
                    $clave = $lote->producto_id
                        . '|'
                        . strtoupper(trim($lote->numero_lote));

                    $this->lotesCache[$clave] = $lote;
                }
            });
    }

    protected function normalizarCodigo(string $codigo): string
    {
        return strtoupper(trim($codigo));
    }

    protected function textoONull(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    protected function normalizarFecha(mixed $valor): mixed
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $valor = trim((string) $valor);

        $formatos = [
            '!d/m/y',
            '!d/m/Y',
            '!Y-m-d',
            '!d-m-y',
            '!d-m-Y',
        ];

        foreach ($formatos as $formato) {
            $fecha = DateTime::createFromFormat($formato, $valor);
            $errores = DateTime::getLastErrors();

            if (
                $fecha !== false &&
                ($errores === false ||
                    ($errores['warning_count'] === 0 &&
                     $errores['error_count'] === 0))
            ) {
                return $fecha->format('Y-m-d');
            }
        }

        return $valor;
    }

    protected function fechaValida(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }

        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
        $errores = DateTime::getLastErrors();

        return $fecha !== false
            && $fecha->format('Y-m-d') === $valor
            && ($errores === false ||
                ($errores['warning_count'] === 0 &&
                 $errores['error_count'] === 0));
    }

    protected function valorActual(Lote $lote, string $campo): ?string
    {
        $valor = $lote->{$campo};

        if ($valor === null || $valor === '') {
            return null;
        }

        if (in_array($campo, [
            'fecha_fabricacion',
            'fecha_vencimiento',
        ], true)) {
            return Carbon::parse($valor)->format('Y-m-d');
        }

        if ($campo === 'estado') {
            return strtoupper(trim((string) $valor));
        }

        return trim((string) $valor);
    }

    protected function valorNuevo(mixed $valor, string $campo): ?string
    {
        if ($valor === null || trim((string) $valor) === '') {
            return $campo === 'estado' ? 'ACTIVO' : null;
        }

        if (in_array($campo, [
            'fecha_fabricacion',
            'fecha_vencimiento',
        ], true)) {
            return (string) $valor;
        }

        if ($campo === 'estado') {
            return strtoupper(trim((string) $valor));
        }

        return trim((string) $valor);
    }
}
