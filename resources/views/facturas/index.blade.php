<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>ORTOMAX AI - Facturas</title>

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
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .filtros {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 20px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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

        .boton {
            display: inline-block;
            padding: 7px 12px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 5px;
        }

        .boton-verde {
            background: #198754;
        }

        .total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 20px;
        }

    </style>

</head>


<body>


<div class="header">

    <h1>ORTOMAX AI</h1>

    <div>Facturas de Contífico</div>

</div>


<div class="container">


    <div class="card">

        <h2>Consulta de facturas</h2>


        <form
            method="GET"
            action="{{ url('/facturas') }}"
        >

            <div class="filtros">


                <div class="campo">

                    <label>
                        Fecha inicial
                    </label>

                    <input
                        type="text"
                        name="fecha_inicial"
                        value="{{ $fechaInicial }}"
                        placeholder="dd/mm/aaaa"
                    >

                </div>


                <div class="campo">

                    <label>
                        Fecha final
                    </label>

                    <input
                        type="text"
                        name="fecha_final"
                        value="{{ $fechaFinal }}"
                        placeholder="dd/mm/aaaa"
                    >

                </div>


                <button type="submit">
                    Buscar
                </button>


            </div>

        </form>


        <table>


            <thead>

                <tr>

                    <th>Fecha</th>

                    <th>Documento</th>

                    <th>Tipo</th>

                    <th>Cliente / Proveedor</th>

                    <th>Estado</th>

                    <th>Electrónica</th>

                    <th>Total</th>

                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody>


                @forelse ($facturas as $factura)


                    <tr>


                        <td>

                            {{ $factura['fecha_emision'] ?? '' }}

                        </td>


                        <td>

                            <strong>

                                {{ $factura['documento'] ?? '' }}

                            </strong>

                        </td>


                        <td>

                            {{ $factura['tipo_registro'] ?? '' }}

                        </td>


                        <td>

                            {{ $factura['persona_id'] ?? '' }}

                        </td>


                        <td>

                            {{ $factura['estado'] ?? '' }}

                        </td>


                        <td>

                            {{ !empty($factura['electronico'])
                                ? 'Sí'
                                : 'No' }}

                        </td>


                        <td>

                            ${{ number_format(
                                (float)($factura['total'] ?? 0),
                                2
                            ) }}

                        </td>


                        <td>


                            <a
                                class="boton"
                                href="{{ url('/facturas/' . $factura['id']) }}"
                            >

                                Ver detalle

                            </a>


                            @if (!empty($factura['url_ride']))

                                <a
                                    class="boton boton-verde"
                                    href="{{ $factura['url_ride'] }}"
                                    target="_blank"
                                >

                                    RIDE

                                </a>

                            @endif


                        </td>


                    </tr>


                @empty


                    <tr>

                        <td colspan="8">

                            No se encontraron facturas.

                        </td>

                    </tr>


                @endforelse


            </tbody>


        </table>


        <div class="total">

            Facturas encontradas:
            {{ count($facturas) }}

        </div>


    </div>


</div>


</body>

</html>