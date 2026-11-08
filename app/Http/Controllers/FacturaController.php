<?php

namespace App\Http\Controllers;

use App\Services\SiigoService;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function index(
        Request $request,
        SiigoService $siigo
    ) {
        $fechaInicial = $request->input(
            'fecha_inicial',
            now()->format('d/m/Y')
        );

        $fechaFinal = $request->input(
            'fecha_final',
            now()->format('d/m/Y')
        );

        $response = $siigo->obtenerFacturas([
            'fecha_inicial' => $fechaInicial,
            'fecha_final' => $fechaFinal,
        ]);

        if ($response->failed()) {
            abort(
                500,
                'No fue posible consultar las facturas de Contífico.'
            );
        }

        $data = $response->json();

        $facturas = $data['results'] ?? [];

        return view(
            'facturas.index',
            compact(
                'facturas',
                'fechaInicial',
                'fechaFinal'
            )
        );
    }


    public function detalle(
        string $id,
        SiigoService $siigo
    ) {
        $factura = $siigo->obtenerFactura($id);
    
        if (!$factura) {
            abort(
                404,
                'No se encontró la factura en Contífico.'
            );
        }
    
        $stockProductos = [];

        foreach ($factura['detalles'] ?? [] as $detalle) {
        
            $productoId = $detalle['producto_id'] ?? null;
        
            if (!$productoId) {
                continue;
            }
        
            $stock = $siigo->obtenerStockProducto($productoId);
        
            $stockTotal = 0;
        
            if (is_array($stock)) {
        
                foreach ($stock as $bodega) {
        
                    $stockTotal += (float) (
                        $bodega['cantidad'] ?? 0
                    );
        
                }
        
            }
        
            $stockProductos[$productoId] = [
                'total' => $stockTotal,
                'bodegas' => $stock ?? [],
            ];
        }
           
        return view(
            'facturas.detalle',
            compact(
                'factura',
                'stockProductos'
            )
        );
    }
}