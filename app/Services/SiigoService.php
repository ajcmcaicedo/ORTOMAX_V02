<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SiigoService
{
    /**
     * Realiza una petición GET a la API de Contífico.
     *
     * Incluye reintentos para errores de conexión.
     */
    private function get(string $endpoint, array $query = []): Response
    {
        $url = config('services.contifico.url');

        $headers = [
            'key' => config('services.contifico.key'),
            'Authorization' => config('services.contifico.authorization'),
            'Accept' => 'application/json',
        ];

        $intentosMaximos = 3;
        $esperas = [2, 5, 10];

        for ($intento = 1; $intento <= $intentosMaximos; $intento++) {
            try {
                return Http::withHeaders($headers)
                    ->timeout(60)
                    ->get($url . $endpoint, $query);

            } catch (ConnectionException $e) {

                if ($intento >= $intentosMaximos) {
                    throw $e;
                }

                sleep($esperas[$intento - 1]);
            }
        }

        throw new \RuntimeException(
            'No fue posible obtener respuesta de Contífico.'
        );
    }

    /**
     * ==========================================================
     * BODEGAS
     * ==========================================================
     */
    public function obtenerBodegas()
    {
        return $this->get('/sistema/api/v2/bodega/');
    }

    /**
     * ==========================================================
     * STOCK DE PRODUCTO
     * ==========================================================
     */
    public function obtenerStockProducto(string $productoId)
    {
        $response = $this->get(
            '/sistema/api/v2/producto/' . $productoId . '/stock/'
        );

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * ==========================================================
     * PRODUCTOS - V2 PAGINADO
     * ==========================================================
     */
    public function obtenerProductosPagina(int $pagina = 1)
    {
        return $this->get(
            '/sistema/api/v2/producto/',
            ['page' => $pagina]
        );
    }

    /**
     * ==========================================================
     * PRODUCTO - V2 POR ID
     *
     * Permite obtener el detalle completo de un producto
     * cuando conocemos su contifico_id.
     * ==========================================================
     */
    public function obtenerProducto(string $productoId): ?array
    {
        $response = $this->get(
            '/sistema/api/v2/producto/' . $productoId . '/'
        );

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * ==========================================================
     * PRODUCTO - V1 POR CÓDIGO
     *
     * Esta es la recuperación especial que descubrimos:
     *
     * V2 listado:
     *   No encuentra algunos productos.
     *
     * V1:
     *   /sistema/api/v1/producto/?codigo=INST6512
     *
     * devuelve el producto y su ID.
     *
     * Después utilizamos ese ID para consultar V2.
     * ==========================================================
     */
    public function obtenerProductoPorCodigoV1(string $codigo): ?array
    {
        $codigo = trim($codigo);
    
        if ($codigo === '') {
            return null;
        }
    
        $response = Http::withHeaders([
            'key' => config('services.contifico.key'),
            'Authorization' => config('services.contifico.authorization'),
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->get(
                config('services.contifico.url') . '/sistema/api/v1/producto/',
                [
                    'codigo' => $codigo,
                ]
            );
    
        if ($response->failed()) {
            return null;
        }
    
        $data = $response->json();
    
        /*
         * Contífico V1 devuelve un ARRAY de productos:
         *
         * [
         *     [
         *         'id' => '...',
         *         'codigo' => 'INST6512',
         *         ...
         *     ]
         * ]
         */
    
        if (!is_array($data)) {
            return null;
        }
    
        foreach ($data as $producto) {
    
            if (!is_array($producto)) {
                continue;
            }
    
            $codigoEncontrado = trim(
                (string) ($producto['codigo'] ?? '')
            );
    
            if (
                strtoupper($codigoEncontrado) ===
                strtoupper($codigo)
            ) {
                return $producto;
            }
        }
    
        return null;
    }

    /**
     * ==========================================================
     * PRODUCTO - RECUPERACIÓN V1 → V2
     *
     * Busca por código en V1 y, si encuentra el ID,
     * obtiene el producto completo mediante V2.
     *
     * Devuelve:
     *
     * [
     *     'v1' => [...],
     *     'v2' => [...],
     * ]
     *
     * o null si no fue posible recuperarlo.
     * ==========================================================
     */
    public function recuperarProductoPorCodigo(string $codigo): ?array
    {
        $productoV1 = $this->obtenerProductoPorCodigoV1($codigo);

        if (!$productoV1 || empty($productoV1['id'])) {
            return null;
        }

        $productoV2 = $this->obtenerProducto(
            $productoV1['id']
        );

        if (!$productoV2) {
            return null;
        }

        /*
         * Seguridad:
         * comprobamos que V2 realmente corresponda
         * al código solicitado.
         */
        $codigoV2 = trim(
            (string) ($productoV2['codigo'] ?? '')
        );

        if (
            strtoupper($codigoV2) !==
            strtoupper(trim($codigo))
        ) {
            return null;
        }

        return [
            'v1' => $productoV1,
            'v2' => $productoV2,
        ];
    }

    /**
     * ==========================================================
     * CATEGORÍAS - V2 PAGINADO
     * ==========================================================
     */
    public function obtenerCategoriasPagina(int $pagina = 1)
    {
        return $this->get(
            '/sistema/api/v2/categoria/',
            ['page' => $pagina]
        );
    }

    /**
     * ==========================================================
     * FACTURAS
     * ==========================================================
     */
    public function obtenerFacturas(array $query = [])
    {
        return $this->get(
            '/sistema/api/v2/factura/',
            $query
        );
    }

    /**
     * ==========================================================
     * FACTURA POR ID
     * ==========================================================
     */
    public function obtenerFactura(string $facturaId)
    {
        return $this->get(
            '/sistema/api/v2/factura/' . $facturaId . '/'
        );
    }
}