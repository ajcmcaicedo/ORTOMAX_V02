<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>ORTOMAX AI - Stock</title>

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
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .producto-id {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #eee;
            text-align: left;
        }

        .cantidad {
            font-size: 18px;
            font-weight: bold;
        }

        .boton {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

    </style>

</head>


<body>


<div class="header">

    <h1>ORTOMAX AI</h1>

    <div>Stock por bodega</div>

</div>


<div class="container">


    <div class="card">


        <h2>Stock del producto</h2>


        <div class="producto-id">

            @if ($producto)
        
                <div style="font-size: 24px; font-weight: bold;">
        
                    {{ $producto->nombre }}
        
                </div>
        
                <br>
        
                <strong>Código:</strong>
        
                {{ $producto->codigo }}
        
                <br>
        
                @if ($producto->descripcion)
        
                    <strong>Descripción:</strong>
        
                    {{ $producto->descripcion }}
        
                @endif
        
            @else
        
                <strong>ID Contífico:</strong>
        
                {{ $productoId }}
        
            @endif
        
        </div>


        <table>


            <thead>

                <tr>

                    <th>Bodega</th>

                    <th>Cantidad disponible</th>

                </tr>

            </thead>


            <tbody>

                @php
                    $stockDisponible = collect($stock)->filter(
                        fn ($bodega) => (float)($bodega['cantidad'] ?? 0) > 0
                    );
            
                    $stockTotal = collect($stock)->sum(
                        fn ($bodega) => (float)($bodega['cantidad'] ?? 0)
                    );
                @endphp
            
                @forelse ($stockDisponible as $bodega)
            
                    <tr>
            
                        <td>
                            {{ $bodega['bodega_nombre'] ?? 'Sin nombre' }}
                        </td>
            
                        <td class="cantidad">
                            {{ number_format(
                                (float)($bodega['cantidad'] ?? 0),
                                2
                            ) }}
                        </td>
            
                    </tr>
            
                @empty
            
                    <tr>
            
                        <td colspan="2">
                            <strong>Sin stock disponible</strong>
                        </td>
            
                    </tr>
            
                @endforelse
            
            </tbody>

        </table>



        <div class="total">

            STOCK TOTAL:
        
            {{ number_format($stockTotal, 2) }}
        
            unidades
        
        </div>


        <a
            class="boton"
            href="{{ url('/facturas') }}"
        >

            ← Volver a facturas

        </a>


    </div>


</div>


</body>

</html>