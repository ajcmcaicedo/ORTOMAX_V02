<?php

namespace App\Http\Controllers;

use App\Models\Institucion;
use App\Models\Regional;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\Importaciones\ImportadorExcel;
use App\Services\Importaciones\Perfiles\InstitucionesImportacion;
use App\Services\Importaciones\ResultadoImportacion;

class InstitucionController extends Controller
{
    /**
     * Listado de instituciones.
     */
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
    
        $instituciones = Institucion::with('regional')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('codigo', 'like', '%' . $q . '%')
                        ->orWhere('ruc', 'like', '%' . $q . '%')
                        ->orWhere('razon_social', 'like', '%' . $q . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $q . '%')
                        ->orWhere('ciudad', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();
    
        $regionales = Regional::where('activo', true)
            ->orderBy('codigo')
            ->get();
    
        return view('instituciones.index', compact(
            'instituciones',
            'q',
            'regionales'
        ));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        $regionales = Regional::where('activo', true)
            ->orderBy('codigo')
            ->get();

        return view('instituciones.create', compact('regionales'));
    }

    /**
     * Guardar institución.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                'unique:instituciones,codigo',
            ],

            'tipo' => [
                'required',
                'string',
                'max:30',
            ],

            'ruc' => [
                'nullable',
                'string',
                'max:30',
            ],

            'razon_social' => [
                'required',
                'string',
                'max:200',
            ],

            'nombre_comercial' => [
                'nullable',
                'string',
                'max:200',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ciudad' => [
                'nullable',
                'string',
                'max:100',
            ],

            'regional_id' => [
                'nullable',
                'integer',
                'exists:regionales,id',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'campo1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'campo2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['activo'] = $request->boolean('activo');

        $institucion = Institucion::create($validated);

        return redirect()
            ->route('instituciones.index')
            ->with('success', 'Institución creada correctamente.');
    }

    /**
     * Mostrar institución.
     */
    public function show(Request $request, Institucion $institucion)
    {
        $institucion->load('regional');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'institucion' => $institucion,
            ]);
        }

        return view('instituciones.show', compact('institucion'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Request $request, Institucion $institucion)
    {
        $institucion->load('regional');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'institucion' => $institucion,
            ]);
        }

        $regionales = Regional::where('activo', true)
            ->orderBy('codigo')
            ->get();

        return view('instituciones.edit', compact(
            'institucion',
            'regionales'
        ));
    }

    /**
     * Actualizar institución.
     */
    public function update(Request $request, Institucion $institucion)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('instituciones', 'codigo')
                    ->ignore($institucion->id),
            ],

            'tipo' => [
                'required',
                'string',
                'max:30',
            ],

            'ruc' => [
                'nullable',
                'string',
                'max:30',
            ],

            'razon_social' => [
                'required',
                'string',
                'max:200',
            ],

            'nombre_comercial' => [
                'nullable',
                'string',
                'max:200',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ciudad' => [
                'nullable',
                'string',
                'max:100',
            ],

            'regional_id' => [
                'nullable',
                'integer',
                'exists:regionales,id',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'campo1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'campo2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['activo'] = $request->boolean('activo');

        $institucion->update($validated);
        $institucion->load('regional');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Institución actualizada correctamente.',
                'institucion' => $institucion,
            ]);
        }

        return redirect()
            ->route('instituciones.index')
            ->with('success', 'Institución actualizada correctamente.');
    }


    /**
     * Mostrar modal/página de importación de instituciones.
     */
    public function importForm()
    {
        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Analizar un Excel de instituciones sin modificar la base de datos.
     */
/**
 * Analizar archivo Excel antes de importar.
 */
public function importPreview(Request $request)
{
    $request->validate([
        'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
    ]);

    try {
        $rutaArchivo = $request->file('archivo')->getRealPath();

        $importador = new ImportadorExcel();
        $perfil = new InstitucionesImportacion();

        $resultado = $importador->analizar(
            $rutaArchivo,
            $perfil
        );

        if ($resultado->total() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron registros para importar.',
            ], 422);
        }

        /*
         * Si existen errores, la importación NO podrá confirmarse.
         */
        if ($resultado->tieneErrores()) {
            // El resultado igualmente se guarda para mostrarlo
            // en la pantalla de análisis.
        }

        /*
         * Convertimos el resultado a un array para almacenarlo
         * temporalmente en la sesión.
         */
        $datosResultado = $resultado->toArray();

        $token = bin2hex(random_bytes(16));

        session([
            'instituciones_importacion_' . $token => [
                'resultado' => $datosResultado,
                'creado_en' => now()->toDateTimeString(),
            ],
        ]);

        /*
         * Convertimos las filas del motor al formato que actualmente
         * utiliza el JavaScript de instituciones.
         */
        $preview = [];

        foreach ($resultado->filas() as $registro) {
            $fila = $registro['fila'] ?? [];

            $preview[] = [
                'fila' => $fila['_numero_fila'] ?? null,
                'codigo' => $fila['codigo'] ?? '',
                'tipo' => $fila['tipo'] ?? '',
                'ruc' => $fila['ruc'] ?? '',
                'razon_social' => $fila['razon social cliente'] ?? '',
                'nombre_comercial' => $fila['clinica hospital'] ?? '',
                'direccion' => $fila['direccion'] ?? '',
                'campo1' => $fila['campo1'] ?? '',
                'campo2' => $fila['campo2'] ?? '',

                /*
                 * Compatibilidad con el JavaScript actual.
                 */
                'existente' => in_array(
                    $registro['estado'] ?? '',
                    [
                        ResultadoImportacion::ACTUALIZAR,
                        ResultadoImportacion::SIN_CAMBIOS,
                    ],
                    true
                ),

                'estado' => $registro['estado'] ?? null,

                'cambios' => $registro['cambios'] ?? [],

                'errores' => $registro['errores'] ?? [],
            ];
        }

        return response()->json([
            'success' => true,

            'token' => $token,

            'total' => $resultado->total(),
            'nuevos' => $resultado->totalNuevos(),
            'actualizar' => $resultado->totalActualizar(),
            'sin_cambios' => $resultado->totalSinCambios(),
            'errores' => $resultado->totalErrores(),

            'preview' => $preview,
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'success' => false,
            'message' => 'No fue posible analizar el archivo Excel: ' . $e->getMessage(),
        ], 422);
    }
}


/**
 * Confirmar la importación previamente analizada.
 */
public function importConfirm(Request $request)
{
    $validated = $request->validate([
        'token' => ['required', 'string', 'size:32'],
    ]);

    $key = 'instituciones_importacion_' . $validated['token'];

    $importacion = session($key);

    if (
        !$importacion ||
        empty($importacion['resultado'])
    ) {
        return response()->json([
            'success' => false,
            'message' => 'La vista previa de importación ya no está disponible. Vuelva a cargar el archivo.',
        ], 422);
    }

    $resultado = $importacion['resultado'];

    /*
     * Nunca permitimos confirmar una importación que tenga errores.
     */
    if (!empty($resultado['errores'])) {
        return response()->json([
            'success' => false,
            'message' => 'No se puede importar porque existen errores de validación.',
        ], 422);
    }

    $perfil = new InstitucionesImportacion();

    $nuevos = 0;
    $actualizados = 0;
    $sinCambios = 0;

    try {

        DB::transaction(function () use (
            $resultado,
            $perfil,
            &$nuevos,
            &$actualizados,
            &$sinCambios
        ) {

            foreach ($resultado['filas'] as $registro) {

                $estado = $registro['estado'] ?? null;
                $fila = $registro['fila'] ?? [];

                /*
                 * SIN CAMBIOS
                 *
                 * No modificamos absolutamente nada.
                 */
                if ($estado === ResultadoImportacion::SIN_CAMBIOS) {
                    $sinCambios++;
                    continue;
                }

                /*
                 * ERROR
                 *
                 * Nunca debería llegar aquí porque la confirmación
                 * ya fue bloqueada anteriormente.
                 */
                if ($estado === ResultadoImportacion::ERROR) {
                    throw new \RuntimeException(
                        'La importación contiene registros con errores de validación.'
                    );
                }

                /*
                 * NUEVO
                 */
                if ($estado === ResultadoImportacion::NUEVO) {

                    $existente = $perfil->buscarExistente($fila);

                    /*
                     * Protección contra cambios realizados entre
                     * el análisis y la confirmación.
                     */
                    if ($existente) {
                        throw new \RuntimeException(
                            'El registro con código "' .
                            ($fila['codigo'] ?? '') .
                            '" ya existe. La importación cambió después del análisis. Vuelva a analizar el archivo.'
                        );
                    }

                    $perfil->crear($fila);

                    $nuevos++;

                    continue;
                }

                /*
                 * ACTUALIZAR
                 */
                if ($estado === ResultadoImportacion::ACTUALIZAR) {

                    $existente = $perfil->buscarExistente($fila);

                    /*
                     * Si desapareció el registro entre el análisis
                     * y la confirmación, no hacemos una creación
                     * automática.
                     */
                    if (!$existente) {
                        throw new \RuntimeException(
                            'El registro con código "' .
                            ($fila['codigo'] ?? '') .
                            '" ya no existe. La importación cambió después del análisis. Vuelva a analizar el archivo.'
                        );
                    }

                    /*
                     * Volvemos a comparar contra la BD actual.
                     *
                     * Esto evita actualizar un registro si alguien
                     * modificó los datos después del análisis.
                     */
                    $cambiosActuales = $perfil->compararCambios(
                        $fila,
                        $existente
                    );

                    if (empty($cambiosActuales)) {
                        $sinCambios++;
                        continue;
                    }

                    $perfil->actualizar(
                        $existente,
                        $fila
                    );

                    $actualizados++;

                    continue;
                }

                /*
                 * Estado desconocido.
                 */
                throw new \RuntimeException(
                    'Se encontró un estado de importación no reconocido.'
                );
            }
        });

    } catch (\Throwable $e) {

        /*
         * La transacción ya hizo ROLLBACK automáticamente.
         *
         * NO eliminamos la sesión para permitir que el usuario
         * pueda volver a intentar la operación.
         */
        return response()->json([
            'success' => false,
            'message' => 'No fue posible completar la importación: ' . $e->getMessage(),
        ], 422);
    }

    /*
     * Solo eliminamos la vista previa de sesión después de
     * completar exitosamente toda la transacción.
     */
    session()->forget($key);

    return response()->json([
        'success' => true,

        'message' => 'Importación completada correctamente.',

        'nuevos' => $nuevos,
        'actualizados' => $actualizados,
        'sin_cambios' => $sinCambios,

        'total' => $nuevos + $actualizados,
    ]);
}

    /**
     * Exportar instituciones a Excel.
     */
    public function exportExcel(Request $request)
    {
        $instituciones = Institucion::query()
            ->orderBy('codigo')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Instituciones');

        // FORMATO MAESTRO: debe ser exactamente el mismo que acepta Importar Excel.
        $headers = [
            'CODIGO',
            'Tipo',
            'RUC',
            'Razón Social_Cliente',
            'Clínica_Hospital',
            'Dirección',
            'Campo1',
            'Campo2',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        $row = 2;
        foreach ($instituciones as $institucion) {
            $values = [
                (string) $institucion->codigo,
                $institucion->tipo,
                $institucion->ruc !== null ? (string) $institucion->ruc : '',
                $institucion->razon_social,
                $institucion->nombre_comercial,
                $institucion->direccion,
                $institucion->campo1,
                $institucion->campo2,
            ];

            foreach ($values as $col => $value) {
                $sheet->setCellValueExplicitByColumnAndRow(
                    $col + 1,
                    $row,
                    (string) ($value ?? ''),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
            $row++;
        }

        $lastRow = max(1, $row - 1);
        $sheet->freezePane('A2');
        $sheet->getAutoFilter()->setRange('A1:H' . $lastRow);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:H1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $widths = [
            'A' => 16,
            'B' => 18,
            'C' => 18,
            'D' => 38,
            'E' => 38,
            'F' => 48,
            'G' => 18,
            'H' => 18,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'instituciones_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function normalizarEncabezado($valor): string
    {
        // Normalización robusta para que el mismo Excel exportado por ORTOMAX
        // pueda volver a importarse aunque Excel conserve guiones bajos,
        // espacios especiales u otros separadores en los encabezados.
        $valor = trim((string) $valor);
        $valor = str_replace("\xC2\xA0", ' ', $valor); // espacio no separable UTF-8
        $valor = str_replace(
            ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ'],
            ['a','e','i','o','u','A','E','I','O','U','n','N'],
            $valor
        );

        // Separadores habituales de encabezados: _, -, . y espacios.
        $valor = preg_replace('/[_\-.]+/u', ' ', $valor);
        $valor = preg_replace('/\s+/u', ' ', $valor);

        return mb_strtolower(trim($valor));
    }

    private function textoExcel($valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_float($valor) && floor($valor) === $valor) {
            return (string) (int) $valor;
        }

        return trim((string) $valor);
    }

    /**
     * Eliminar institución.
     */
    public function destroy(Institucion $institucion)
    {
        /*
         * Por ahora eliminamos solamente si no existen
         * relaciones operativas asociadas.
         *
         * Cuando terminemos Notas de Entrega, precios,
         * cirugías, etc., agregaremos las validaciones
         * correspondientes antes de permitir eliminar.
         */

        $institucion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Institución eliminada correctamente.',
        ]);
    }
}
