<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        ORTOMAX AI - {{ $factura['documento'] ?? 'Factura' }}
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
        }

        .header {
            background: #0d6efd;
            color: white;
            padding: 20px 30px;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .titulo {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .datos {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .dato {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
        }

        .dato strong {
            display: block;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            background: #eee;
            text-align: left;
        }

        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 10px 18px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }

        .producto {
            font-weight: bold;
        }

        .codigo {
            color: #555;
            font-size: 13px;
        }

        .modal-stock {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    align-items: center;
    justify-content: center;
}

        .modal-contenido {
            position: relative;
            background: white;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,.3);
        }

        .modal-cerrar {
            position: absolute;
            right: 18px;
            top: 12px;
            border: none;
            background: transparent;
            font-size: 32px;
            cursor: pointer;
        }

        .tabla-stock {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tabla-stock th,
        .tabla-stock td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        .tabla-stock th {
            text-align: left;
            background: #f1f3f5;
        }

        .cantidad-stock {
            font-weight: bold;
            text-align: right;
        }

        .stock-total-modal {
            margin-top: 20px;
            padding: 15px;
            background: #f1f3f5;
            border-radius: 8px;
            font-size: 20px;
            font-weight: bold;
        }

        .boton.cerrar {
            border: none;
            cursor: pointer;
        }



    </style>

</head>

<body>

<div class="header">

    <h1>ORTOMAX AI</h1>

    <div>Detalle de factura</div>

</div>


<div class="container">


    {{-- DATOS DE LA FACTURA --}}

    <div class="card">

        <div class="titulo">

            Factura
            {{ $factura['documento'] ?? '' }}

        </div>


        <div class="datos">


            <div class="dato">

                <strong>Fecha de emisión</strong>

                {{ $factura['fecha_emision'] ?? '' }}

            </div>


            <div class="dato">

                <strong>Tipo de documento</strong>

                {{ $factura['tipo_documento'] ?? '' }}

            </div>


            <div class="dato">

                <strong>Estado</strong>

                {{ $factura['estado'] ?? '' }}

            </div>


            <div class="dato">

                <strong>Electrónica</strong>

                {{ !empty($factura['electronico'])
                    ? 'Sí'
                    : 'No' }}

            </div>


            <div class="dato">

                <strong>Subtotal</strong>

                ${{ number_format(
                    (float)($factura['subtotal_12'] ?? 0)
                    +
                    (float)($factura['subtotal_0'] ?? 0),
                    2
                ) }}

            </div>


            <div class="dato">

                <strong>IVA</strong>

                ${{ number_format(
                    (float)($factura['iva'] ?? 0),
                    2
                ) }}

            </div>


        </div>

    </div>


    {{-- CLIENTE / PROVEEDOR --}}

    @if (!empty($factura['persona']))

        @php
            $persona = $factura['persona'];
        @endphp

        <div class="card">

            <h2>

                {{ ($factura['tipo_registro'] ?? '') === 'PRO'
                    ? 'Proveedor'
                    : 'Cliente' }}

            </h2>


            <div class="datos">


                @if (!empty($persona['razon_social']))

                    <div class="dato">

                        <strong>Razón social</strong>

                        {{ $persona['razon_social'] }}

                    </div>

                @endif


                @if (!empty($persona['ruc']))

                    <div class="dato">

                        <strong>RUC</strong>

                        {{ $persona['ruc'] }}

                    </div>

                @endif


                @if (!empty($persona['cedula']))

                    <div class="dato">

                        <strong>Cédula</strong>

                        {{ $persona['cedula'] }}

                    </div>

                @endif


                @if (!empty($persona['telefonos']))

                    <div class="dato">

                        <strong>Teléfono</strong>

                        {{ $persona['telefonos'] }}

                    </div>

                @endif


                @if (!empty($persona['email']))

                    <div class="dato">

                        <strong>Email</strong>

                        {{ $persona['email'] }}

                    </div>

                @endif


                @if (!empty($persona['direccion']))

                    <div class="dato">

                        <strong>Dirección</strong>

                        {{ $persona['direccion'] }}

                    </div>

                @endif


            </div>

        </div>

    @endif


    {{-- PRODUCTOS --}}

    <div class="card">

        <h2>Productos facturados</h2>


        <table>

            <thead>

                <tr>

                    <th>Código</th>

                    <th>Producto</th>

                    <th>Descripción</th>

                    <th>Cantidad</th>

                    <th>Precio</th>

                    <th>IVA</th>

                    <th>Total</th>

                    <th>Stock actual</th>
                </tr>

            </thead>


            <tbody>


                @forelse (
                    $factura['detalles'] ?? []
                    as $detalle
                )


                    @php

                        $producto = \App\Models\Producto::where(
                            'contifico_id',
                            $detalle['producto_id'] ?? ''
                        )->first();

                        $cantidad =
                            (float)($detalle['cantidad'] ?? 0);

                        $precio =
                            (float)($detalle['precio'] ?? 0);

                    @endphp


                    <tr>


                        <td>

                            @if ($producto)

                                {{ $producto->codigo }}

                            @else

                                {{ $detalle['producto_id'] ?? '' }}

                            @endif

                        </td>


                        <td>

                            <div class="producto">

                                {{ $producto->nombre
                                    ?? $detalle['producto_nombre']
                                    ?? '' }}

                            </div>

                        </td>


                        <td>

                            {{ $producto->descripcion
                                ?? $detalle['producto_descipcion']
                                ?? '' }}

                        </td>


                        <td>

                            {{ $cantidad }}

                        </td>


                        <td>

                            ${{ number_format(
                                $precio,
                                2
                            ) }}

                        </td>


                        <td>

                            {{ $detalle['porcentaje_iva'] ?? 0 }}%

                        </td>


                        <td>

                            ${{ number_format(
                                $cantidad * $precio,
                                2
                            ) }}

                        </td>

                        <td>

                            @php
                                $productoId = $detalle['producto_id'] ?? null;
                                $stockActual = $stockProductos[$productoId]['total'] ?? 0;
                            @endphp
                        
                            @if ($stockActual > 0)
                        
                                <strong style="color: green;">
                                    🟢 {{ number_format($stockActual, 2) }}
                                </strong>
                        
                            @else
                        
                                <strong style="color: red;">
                                    🔴 SIN STOCK
                                </strong>
                        
                            @endif
                        
                            <br>
                        
                            @if ($productoId)
                        
                            <button
                            type="button"
                            class="boton"
                            onclick="abrirStock('{{ $productoId }}')"
                        >
                                      Ver bodegas
                            </button>
                        
                            @endif
                        
                        </td>

                      </tr>                     

                @empty


                    <tr>

                        <td colspan="8">

                            No hay productos registrados.

                        </td>

                    </tr>


                @endforelse


            </tbody>

        </table>


        <div class="total">

            Total factura:

            ${{ number_format(
                (float)($factura['total'] ?? 0),
                2
            ) }}

        </div>




    </div>


    {{-- ACCIONES --}}

    <div class="card">


        @if (!empty($factura['url_ride']))

            <a
                class="boton"
                href="{{ $factura['url_ride'] }}"
                target="_blank"
            >

                Ver RIDE

            </a>

        @endif


        @if (!empty($factura['url_xml']))

            <a
                class="boton"
                href="{{ $factura['url_xml'] }}"
                target="_blank"
            >

                Ver XML

            </a>

        @endif


        <a
            class="boton"
            href="{{ url('/facturas') }}"
        >

            Volver a facturas

        </a>


    </div>


</div>

@foreach ($factura['detalles'] ?? [] as $detalle)

    @php
        $productoId = $detalle['producto_id'] ?? null;

        $stockInfo = $stockProductos[$productoId] ?? [
            'total' => 0,
            'bodegas' => [],
        ];

        $bodegasConStock = collect(
            $stockInfo['bodegas']
        )->filter(
            fn ($bodega) =>
                (float)($bodega['cantidad'] ?? 0) > 0
        );
    @endphp

    <div
        id="modal-stock-{{ $productoId }}"
        class="modal-stock"
        onclick="cerrarStock(event, '{{ $productoId }}')"
    >

        <div
            class="modal-contenido"
            onclick="event.stopPropagation()"
        >

            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarStock(null, '{{ $productoId }}')"
            >
                ×
            </button>

            <h2>Stock por bodega</h2>

            <h3>
                {{ $detalle['producto_nombre'] ?? 'Producto' }}
            </h3>

            <p>
                <strong>Código:</strong>
                {{ $detalle['producto_id'] ?? '' }}
            </p>

            <table class="tabla-stock">

                <thead>

                    <tr>
                        <th>Bodega</th>
                        <th>Cantidad</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse ($bodegasConStock as $bodega)

                        <tr>

                            <td>
                                {{ $bodega['bodega_nombre'] ?? 'Sin nombre' }}
                            </td>

                            <td class="cantidad-stock">
                                {{ number_format(
                                    (float)($bodega['cantidad'] ?? 0),
                                    2
                                ) }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="2">
                                Sin stock disponible
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

            <div class="stock-total-modal">

                STOCK TOTAL:

                {{ number_format(
                    (float)$stockInfo['total'],
                    2
                ) }}

                unidades

            </div>

            <button
                type="button"
                class="boton cerrar"
                onclick="cerrarStock(null, '{{ $productoId }}')"
            >
                Cerrar
            </button>

        </div>

    </div>

@endforeach

<script>

    function abrirStock(productoId) {
    
        const modal = document.getElementById(
            'modal-stock-' + productoId
        );
    
        if (modal) {
    
            modal.style.display = 'flex';
    
        }
    }
    
    
    function cerrarStock(event, productoId) {
    
        const modal = document.getElementById(
            'modal-stock-' + productoId
        );
    
        if (!modal) {
            return;
        }
    
        if (
            event === null ||
            event.target === modal
        ) {
    
            modal.style.display = 'none';
    
        }
    }
    
    
    document.addEventListener(
        'keydown',
        function(event) {
    
            if (event.key === 'Escape') {
    
                document
                    .querySelectorAll('.modal-stock')
                    .forEach(function(modal) {
    
                        modal.style.display = 'none';
    
                    });
    
            }
    
        }
    );
    
    </script>


</body>

</html>