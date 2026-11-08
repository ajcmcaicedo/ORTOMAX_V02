<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ORTOMAX AI - Productos</title>

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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .search-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
        }
        .btn-stock {
            background: #198754;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .search-form {
            display: flex;
            gap: 10px;
        }

        input {
            flex: 1;
            padding: 12px;
            font-size: 16px;
        }

        button {
            padding: 12px 25px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
        }

        table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
            background: white;
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

                    /* ==============================
            MODAL STOCK
            ============================== */

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

            right: 15px;

            top: 10px;

            border: none;

            background: transparent;

            color: #333;

            font-size: 32px;

            cursor: pointer;

            padding: 0;
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


            .cargando {

            padding: 20px;

            text-align: center;

            font-size: 16px;
            }


            .error-stock {

            padding: 15px;

            margin-top: 15px;

            background: #f8d7da;

            color: #842029;

            border-radius: 6px;
            }


            .btn-cerrar {

            margin-top: 20px;

            background: #6c757d;
            }

    </style>
</head>

<body>

<div class="header">
    <h1>ORTOMAX AI</h1>
    <div>Consulta de productos</div>
</div>

<div class="container">

    <div class="search-box">

        <h2>Buscar producto</h2>

        <form
            method="GET"
            action="{{ url('/productos') }}"
            class="search-form"
        >

            <input
                type="text"
                name="q"
                value="{{ $busqueda }}"
                placeholder="Código, nombre o código de barras..."
            >

            <button type="submit">
                Buscar
            </button>

        </form>

    </div>


    @if ($busqueda !== '')

        <p>
            <strong>Resultados para:</strong>
            {{ $busqueda }}
        </p>

        <p>
            Encontrados:
            <strong>{{ $productos->count() }}</strong>
        </p>


        @if ($productos->count() > 0)

            <table>

                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Código de barras</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($productos as $producto)

                        <tr>

                            <td>
                                {{ $producto->codigo }}
                            </td>

                            <td>
                                {{ $producto->nombre }}
                            </td>

                            <td>
                                {{ $producto->codigo_barra ?? '' }}
                            </td>

                            <td>
                                {{ $producto->estado }}
                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="btn-stock"
                                    onclick="abrirStock('{{ $producto->contifico_id }}')"
                                >
                                    Ver stock
                                </button>
                            
                            </td>


                        </tr>

                    @endforeach

                </tbody>

            </table>

        @else

            <p>
                No encontramos productos.
            </p>

        @endif

    @endif

</div>

<!-- ==============================
     MODAL DE STOCK
================================ -->

<div
    id="modal-stock"
    class="modal-stock"
>

    <div
        class="modal-contenido"
    >

        <!-- BOTÓN CERRAR -->

        <button
            type="button"
            class="modal-cerrar"
            onclick="cerrarStock()"
        >
            ×
        </button>


        <h2>
            Stock por bodega
        </h2>


        <!-- PRODUCTO -->

        <h3 id="modal-producto">
            Consultando...
        </h3>


        <p id="modal-codigo"></p>


        <!-- CARGANDO -->

        <div
            id="modal-cargando"
            class="cargando"
        >
            Consultando stock en Contífico...
        </div>


        <!-- ERROR -->

        <div
            id="modal-error"
            class="error-stock"
            style="display:none;"
        >
        </div>


        <!-- TABLA -->

        <table
            id="tabla-stock"
            class="tabla-stock"
            style="display:none;"
        >

            <thead>

                <tr>

                    <th>
                        Bodega
                    </th>

                    <th>
                        Cantidad
                    </th>

                </tr>

            </thead>


            <tbody id="lista-bodegas">

            </tbody>

        </table>


        <!-- TOTAL -->

        <div
            id="modal-total"
            class="stock-total-modal"
            style="display:none;"
        >
        </div>


        <button
            type="button"
            class="btn-cerrar"
            onclick="cerrarStock()"
        >
            Cerrar
        </button>

    </div>

</div>

<script>

    async function abrirStock(productoId)
    {
        const modal =
            document.getElementById('modal-stock');
    
        const producto =
            document.getElementById('modal-producto');
    
        const codigo =
            document.getElementById('modal-codigo');
    
        const cargando =
            document.getElementById('modal-cargando');
    
        const error =
            document.getElementById('modal-error');
    
        const tabla =
            document.getElementById('tabla-stock');
    
        const lista =
            document.getElementById('lista-bodegas');
    
        const total =
            document.getElementById('modal-total');
    
    
        /* Mostrar modal */
    
        modal.style.display = 'flex';
    
    
        /* Estado inicial */
    
        producto.textContent =
            'Consultando...';
    
        codigo.textContent = '';
    
        cargando.style.display =
            'block';
    
        error.style.display =
            'none';
    
        tabla.style.display =
            'none';
    
        total.style.display =
            'none';
    
        lista.innerHTML = '';
    
    
        try {
    
            /* =========================
               CONSULTA ÚNICA A CONTÍFICO
            ========================== */
    
            const respuesta = await fetch(
                `/stock/${productoId}/consultar`
            );
    
    
            const datos =
                await respuesta.json();
    
    
            /* =========================
               VALIDAR RESPUESTA
            ========================== */
    
            if (
                !respuesta.ok ||
                datos.error
            ) {
    
                throw new Error(
                    datos.mensaje ||
                    'No fue posible consultar el stock.'
                );
    
            }
    
    
            /* =========================
               PRODUCTO
            ========================== */
    
            producto.textContent =
                datos.producto?.nombre ||
                'Producto';
    
    
            codigo.textContent =
                'Código: ' +
                (
                    datos.producto?.codigo ||
                    ''
                );
    
    
            /* =========================
               BODEGAS
            ========================== */
    
            const bodegas =
                datos.bodegas || [];
    
    
            if (bodegas.length === 0) {
    
                lista.innerHTML = `
                    <tr>
                        <td colspan="2">
                            No hay stock disponible.
                        </td>
                    </tr>
                `;
    
            } else {
    
                bodegas.forEach(
                    function(bodega)
                    {
    
                        const fila =
                            document.createElement('tr');
    
    
                        const nombre =
                            bodega.bodega_nombre ||
                            'Sin nombre';
    
    
                        const cantidad =
                            parseFloat(
                                bodega.cantidad || 0
                            );
    
    
                        fila.innerHTML = `
    
                            <td>
                                ${nombre}
                            </td>
    
                            <td class="cantidad-stock">
                                ${cantidad.toFixed(2)}
                            </td>
    
                        `;
    
    
                        lista.appendChild(fila);
    
                    }
                );
    
            }
    
    
            /* =========================
               MOSTRAR RESULTADO
            ========================== */
    
            cargando.style.display =
                'none';
    
            tabla.style.display =
                'table';
    
            total.style.display =
                'block';
    
    
            total.textContent =
                'STOCK TOTAL: ' +
    
                parseFloat(
                    datos.stock_total || 0
                ).toFixed(2) +
    
                ' unidades';
    
    
        }
    
        catch (errorConsulta)
        {
    
            cargando.style.display =
                'none';
    
            error.style.display =
                'block';
    
            error.textContent =
                'Error: ' +
                errorConsulta.message;
    
        }
    
    }
    
    
    function cerrarStock()
    {
    
        const modal =
            document.getElementById(
                'modal-stock'
            );
    
        modal.style.display =
            'none';
    
    }
    
    
    document.addEventListener(
        'keydown',
        function(event)
        {
    
            if (event.key === 'Escape') {
    
                cerrarStock();
    
            }
    
        }
    );
    
    </script>

</body>

</html>