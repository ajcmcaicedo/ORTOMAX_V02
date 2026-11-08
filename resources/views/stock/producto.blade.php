<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            padding: 20px;
        }

        .container {
            padding: 30px;
            max-width: 1000px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #eee;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

    </style>

</head>

<body>

    <div class="header">

        <h1>ORTOMAX AI</h1>

        <div>
            Stock por bodega
        </div>

    </div>


    <div class="container">

        <div class="card">

            <h2>Stock del producto</h2>

            <p>
                <strong>ID del producto:</strong>
                {{ $productoId }}
            </p>


            <table>

                <thead>

                    <tr>

                        <th>Bodega</th>

                        <th>Cantidad</th>

                    </tr>

                </thead>


                <tbody>

                    @php
                        $total = 0;
                    @endphp


                    @foreach ($stock as $item)

                        @php
                            $cantidad = (float) ($item['cantidad'] ?? 0);
                        @endphp

                        @if ($cantidad > 0)

                            @php
                                $total += $cantidad;
                            @endphp

                            <tr>
                                <td>
                                    {{ $item['bodega_nombre'] ?? 'Sin nombre' }}
                                </td>

                                <td>
                                    {{ $cantidad }}
                                </td>
                            </tr>

                        @endif

                    @endforeach

                </tbody>

            </table>


            <div class="total">

                Total disponible:

                {{ $total }}

            </div>

        </div>

    </div>

</body>

</html>