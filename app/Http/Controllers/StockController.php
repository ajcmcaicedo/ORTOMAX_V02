<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\SiigoService;

class StockController extends Controller
{
    public function mostrar(
        string $productoId,
        SiigoService $siigo
    ) {
        $stock = $siigo->obtenerStockProducto($productoId);

        if ($stock === null) {
            abort(
                500,
                'No fue posible consultar el stock en Contífico.'
            );
        }

        $producto = Producto::where(
            'contifico_id',
            $productoId
        )->first();

        return view(
            'stock.detalle',
            compact(
                'stock',
                'producto',
                'productoId'
            )
        );
    }


    public function consultar(
        string $productoId,
        SiigoService $siigo
    ) {
        $stock = $siigo->obtenerStockProducto($productoId);

        if ($stock === null) {
            return response()->json([
                'error' => true,
                'mensaje' => 'No fue posible consultar el stock.'
            ], 500);
        }

        $producto = Producto::where(
            'contifico_id',
            $productoId
        )->first();

        $stockTotal = collect($stock)->sum(
            fn ($bodega) => (float)($bodega['cantidad'] ?? 0)
        );

        $bodegas = collect($stock)
            ->filter(
                fn ($bodega) =>
                    (float)($bodega['cantidad'] ?? 0) > 0
            )
            ->values();

        return response()->json([
            'producto' => [
                'id' => $productoId,
                'codigo' => $producto?->codigo,
                'nombre' => $producto?->nombre,
                'descripcion' => $producto?->descripcion,
            ],
            'stock_total' => $stockTotal,
            'bodegas' => $bodegas,
        ]);
    }
}