<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Throwable;

class ExportarLotesExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        public string $token
    ) {}

    public function handle(): void
    {
        $exportacion = DB::table('exportaciones_lotes')
            ->where('token', $this->token)
            ->first();

        if (!$exportacion) {
            return;
        }

        $rutaPlantilla = storage_path(
            'app/importaciones/Plantilla_Lotes.xlsx'
        );

        $directorio = storage_path('app/exportaciones/lotes');

        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $rutaArchivo = $directorio . '/LOTES_' . $this->token . '.xlsx';

        try {

            DB::table('exportaciones_lotes')
                ->where('token', $this->token)
                ->update([
                    'estado' => 'procesando',
                    'updated_at' => now(),
                ]);

            if (!file_exists($rutaPlantilla)) {
                throw new \RuntimeException(
                    'No se encontro la plantilla de lotes.'
                );
            }

            $libro = IOFactory::load($rutaPlantilla);
            $hoja = $libro->getActiveSheet();

            $ultimaFila = $hoja->getHighestRow();

            if ($ultimaFila > 1) {
                $hoja->removeRow(2, $ultimaFila - 1);
            }

            $total = (int) DB::table('lotes')->count();

            DB::table('exportaciones_lotes')
                ->where('token', $this->token)
                ->update([
                    'total_registros' => $total,
                    'procesados' => 0,
                    'porcentaje' => $total === 0 ? 100 : 0,
                    'updated_at' => now(),
                ]);

            $fila = 2;
            $procesados = 0;

            $lotes = DB::table('lotes')
                ->join('productos', 'productos.id', '=', 'lotes.producto_id')
                ->select([
                    'productos.codigo as codigo_producto',
                    'lotes.numero_lote',
                    'lotes.fecha_fabricacion',
                    'lotes.fecha_vencimiento',
                    'lotes.registro_sanitario',
                    'lotes.estado',
                    'lotes.observaciones',
                ])
                ->orderBy('lotes.id')
                ->cursor();

            foreach ($lotes as $lote) {

                $hoja->setCellValueExplicit(
                    'A' . $fila,
                    (string) $lote->codigo_producto,
                    DataType::TYPE_STRING
                );

                $hoja->setCellValueExplicit(
                    'B' . $fila,
                    (string) $lote->numero_lote,
                    DataType::TYPE_STRING
                );

                $fabricacion = $lote->fecha_fabricacion
                    ? date('d/m/y', strtotime($lote->fecha_fabricacion))
                    : null;

                $vencimiento = $lote->fecha_vencimiento
                    ? date('d/m/y', strtotime($lote->fecha_vencimiento))
                    : null;

                $hoja->setCellValue('C' . $fila, $fabricacion);
                $hoja->setCellValue('D' . $fila, $vencimiento);

                $hoja->setCellValueExplicit(
                    'E' . $fila,
                    (string) ($lote->registro_sanitario ?? ''),
                    DataType::TYPE_STRING
                );

                $estado = $lote->estado
                    ? ucfirst(mb_strtolower($lote->estado))
                    : 'Activo';

                $hoja->setCellValueExplicit(
                    'F' . $fila,
                    $estado,
                    DataType::TYPE_STRING
                );

                $hoja->setCellValue(
                    'G' . $fila,
                    $lote->observaciones
                );

                $fila++;
                $procesados++;

                // Actualizar el progreso cada 100 registros.
                if ($procesados % 100 === 0 || $procesados === $total) {

                    $porcentaje = $total > 0
                        ? (int) floor(($procesados / $total) * 100)
                        : 100;

                    DB::table('exportaciones_lotes')
                        ->where('token', $this->token)
                        ->update([
                            'procesados' => $procesados,
                            'porcentaje' => $porcentaje,
                            'updated_at' => now(),
                        ]);
                }
            }

            $escritor = new Xlsx($libro);
            $escritor->save($rutaArchivo);

            $libro->disconnectWorksheets();
            unset($libro);

            DB::table('exportaciones_lotes')
                ->where('token', $this->token)
                ->update([
                    'estado' => 'completado',
                    'procesados' => $procesados,
                    'porcentaje' => 100,
                    'archivo' => 'exportaciones/lotes/LOTES_' . $this->token . '.xlsx',
                    'updated_at' => now(),
                ]);

        } catch (Throwable $e) {

            if (file_exists($rutaArchivo)) {
                @unlink($rutaArchivo);
            }

            DB::table('exportaciones_lotes')
                ->where('token', $this->token)
                ->update([
                    'estado' => 'error',
                    'error' => mb_substr($e->getMessage(), 0, 5000),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('exportaciones_lotes')
            ->where('token', $this->token)
            ->update([
                'estado' => 'error',
                'error' => mb_substr(
                    $exception?->getMessage() ?? 'Error desconocido',
                    0,
                    5000
                ),
                'updated_at' => now(),
            ]);
    }
}
