@extends('layouts.app')

@section('title', 'Productos por Proveedor')

@section('content')

<div class="ortomax-card">

    {{-- ========================================================= --}}
    {{-- ENCABEZADO --}}
    {{-- ========================================================= --}}

    <div class="ortomax-module-header">

        <div>

            <h1 class="ortomax-title">
                Productos por Proveedor
            </h1>

            <p class="ortomax-subtitle">
                Homologación de productos ORTOMAX por proveedor y producto de Contífico
            </p>

        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">

            @if(auth()->user()->tienePermiso('productos_proveedores.importar'))
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="abrirImportarProductosProveedores()"
                >
                    📥 Importar Excel
                </button>
            @endif
        
            @if(auth()->user()->tienePermiso('productos_proveedores.exportar'))
                <a
                    href="{{ route('productos_proveedores.exportar') }}"
                    class="ortomax-btn ortomax-btn-secondary"
                    style="text-decoration:none;"
                >
                    📤 Exportar Excel
                </a>
            @endif
        
            @if(auth()->user()->tienePermiso('productos_proveedores.crear'))
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-primary"
                    onclick="abrirNuevoProductoProveedor()"
                >
                    + Nuevo Producto
                </button>
            @endif
        
        </div>

    {{-- ========================================================= --}}
    {{-- BUSCADOR PRINCIPAL --}}
    {{-- ========================================================= --}}

    <div style="margin-bottom:20px;">

        <form
            method="GET"
            action="{{ route('productos_proveedores.index') }}"
            style="
                display:flex;
                gap:10px;
                align-items:center;
                flex-wrap:wrap;
            "
        >

            <input
                type="text"
                name="q"
                value="{{ $busqueda }}"
                class="ortomax-form-control"
                placeholder="Buscar por producto maestro, proveedor, código o registro sanitario..."
                style="max-width:600px;"
            >


            <button
                type="submit"
                class="ortomax-btn ortomax-btn-secondary"
            >
                Buscar
            </button>


            @if($busqueda !== '')

                <a
                    href="{{ route('productos_proveedores.index') }}"
                    class="ortomax-btn ortomax-btn-secondary"
                    style="text-decoration:none;"
                >
                    Limpiar
                </a>

            @endif

        </form>

    </div>


    {{-- ========================================================= --}}
    {{-- TABLA --}}
    {{-- ========================================================= --}}

    <div class="ortomax-table-container">

        <table class="ortomax-table">

            <thead>

                <tr>

                    <th>
                        Producto Maestro
                    </th>

                    <th>
                        Proveedor
                    </th>

                    <th>
                        Producto Contífico
                    </th>

                    <th>
                        Código Proveedor
                    </th>

                    <th>
                        Registro Sanitario
                    </th>

                    <th>
                        Estado
                    </th>

                    <th style="width:180px;">
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody id="tablaProductosProveedores">

                @forelse($productosProveedores as $item)

                    <tr
                        id="fila-producto-proveedor-{{ $item->id }}"
                        data-id="{{ $item->id }}"
                    >

                        {{-- PRODUCTO MAESTRO --}}
                        <td>

                            <strong>
                                {{ $item->productoMaestro?->codigo_maestro ?? '—' }}
                            </strong>

                            @if($item->productoMaestro?->descripcion)

                                <br>

                                <small>
                                    {{ $item->productoMaestro->descripcion }}
                                </small>

                            @endif

                        </td>


                        {{-- PROVEEDOR --}}
                        <td>

                            @if($item->proveedor)

                                <strong>
                                    {{ $item->proveedor->sigla ?: $item->proveedor->codigo }}
                                </strong>

                                <br>

                                <small>
                                    {{ $item->proveedor->nombre }}
                                </small>

                            @else

                                —

                            @endif

                        </td>


                        {{-- PRODUCTO CONTÍFICO --}}
                        <td>

                            @if($item->producto)

                                <strong>
                                    {{ $item->producto->codigo }}
                                </strong>

                                <br>

                                <small>
                                    {{ $item->producto->nombre }}
                                </small>

                            @else

                                —

                            @endif

                        </td>


                        {{-- CÓDIGO PROVEEDOR --}}
                        <td>

                            {{ $item->codigo_producto ?: '—' }}

                        </td>


                        {{-- REGISTRO SANITARIO --}}
                        <td>

                            {{ $item->registro_sanitario ?: '—' }}

                        </td>


                        {{-- ESTADO --}}
                        <td>

                            @if($item->activo)

                                <span class="ortomax-status ortomax-status-active">
                                    ACTIVO
                                </span>

                            @else

                                <span class="ortomax-status ortomax-status-inactive">
                                    INACTIVO
                                </span>

                            @endif

                        </td>


                        {{-- ACCIONES --}}
                        <td class="ortomax-actions-cell">

                            <div class="ortomax-actions">

                                @if(auth()->user()->tienePermiso('productos_proveedores.ver'))

                                    <button
                                        type="button"
                                        class="ortomax-btn ortomax-btn-secondary"
                                        onclick="verProductoProveedor({{ $item->id }})"
                                    >
                                        Ver
                                    </button>

                                @endif


                                @if(auth()->user()->tienePermiso('productos_proveedores.editar'))

                                    <button
                                        type="button"
                                        class="ortomax-btn ortomax-btn-warning"
                                        onclick="editarProductoProveedor({{ $item->id }})"
                                    >
                                        Editar
                                    </button>

                                @endif


                                @if(auth()->user()->tienePermiso('productos_proveedores.eliminar'))

                                    <button
                                        type="button"
                                        class="ortomax-btn ortomax-btn-danger"
                                        onclick="eliminarProductoProveedor({{ $item->id }})"
                                    >
                                        Eliminar
                                    </button>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            style="
                                text-align:center;
                                padding:40px;
                            "
                        >
                            No existen productos por proveedor registrados.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- PAGINACIÓN --}}
    {{-- ========================================================= --}}

    @if($productosProveedores->hasPages())

        <div style="margin-top:20px;">

            {{ $productosProveedores->links() }}

        </div>

    @endif

</div>



{{-- ============================================================= --}}
{{-- MODAL NUEVO / EDITAR --}}
{{-- ============================================================= --}}

<div
    id="modalProductoProveedor"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2 id="modalProductoProveedorTitulo">
                Nuevo Producto por Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarProductoProveedor()"
            >
                ×
            </button>

        </div>


        <form id="formProductoProveedor">

            <div class="ortomax-modal-body">

                <input
                    type="hidden"
                    id="productoProveedorId"
                >


        {{-- ================================================= --}}
        {{-- PRODUCTO MAESTRO - BUSCADOR --}}
        {{-- ================================================= --}}

        <div class="ortomax-form-group">

            <label class="ortomax-form-label">
                Producto Maestro *
            </label>


            <input
                type="text"
                id="buscarProductoMaestro"
                class="ortomax-form-control"
                placeholder="Digite código o descripción del producto maestro..."
                autocomplete="off"
            >


            {{-- ID REAL DEL PRODUCTO MAESTRO --}}
            <input
                type="hidden"
                id="productoMaestroId"
                name="producto_maestro_id"
            >


            {{-- RESULTADOS --}}
            <div
                id="resultadosProductosMaestros"
                style="
                    display:none;
                    border:1px solid #d9dfe5;
                    background:#fff;
                    max-height:250px;
                    overflow-y:auto;
                    margin-top:5px;
                    position:relative;
                    z-index:2000;
                    border-radius:4px;
                "
            ></div>


            {{-- PRODUCTO SELECCIONADO --}}
            <div
                id="productoMaestroSeleccionado"
                style="
                    margin-top:8px;
                    color:#666;
                "
            >
                Ningún producto maestro seleccionado.
            </div>


            <div
                id="errorProductoMaestro"
                class="ortomax-form-error"
            ></div>

        </div>

                {{-- ================================================= --}}
                {{-- PROVEEDOR - BUSCADOR --}}
                {{-- ================================================= --}}

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Proveedor *
                    </label>


                    <input
                        type="text"
                        id="buscarProveedor"
                        class="ortomax-form-control"
                        placeholder="Digite código, sigla o nombre del proveedor..."
                        autocomplete="off"
                    >


                    {{-- ID REAL QUE SE ENVÍA AL SERVIDOR --}}
                    <input
                        type="hidden"
                        id="proveedorId"
                        name="proveedor_id"
                    >


                    {{-- RESULTADOS --}}
                    <div
                        id="resultadosProveedores"
                        style="
                            display:none;
                            border:1px solid #ddd;
                            background:#fff;
                            max-height:220px;
                            overflow-y:auto;
                            margin-top:5px;
                            position:relative;
                            z-index:1000;
                        "
                    ></div>


                    {{-- PROVEEDOR SELECCIONADO --}}
                    <div
                        id="proveedorSeleccionado"
                        style="
                            margin-top:8px;
                            color:#666;
                        "
                    >
                        Ningún proveedor seleccionado.
                    </div>


                    <div
                        id="errorProveedor"
                        class="ortomax-form-error"
                    ></div>

                </div>


                {{-- ================================================= --}}
                {{-- PRODUCTO CONTÍFICO --}}
                {{-- ================================================= --}}

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Producto Contífico *
                    </label>


                    <input
                        type="text"
                        id="buscarProductoContifico"
                        class="ortomax-form-control"
                        placeholder="Buscar por código, código auxiliar, nombre o código de barras..."
                        autocomplete="off"
                    >


                    {{-- ID REAL DEL PRODUCTO --}}
                    <input
                        type="hidden"
                        id="productoId"
                        name="producto_id"
                    >


                    {{-- RESULTADOS AJAX --}}
                    <div
                        id="resultadosProductosContifico"
                        style="
                            display:none;
                            border:1px solid #ddd;
                            background:#fff;
                            max-height:250px;
                            overflow-y:auto;
                            margin-top:5px;
                            position:relative;
                            z-index:999;
                        "
                    ></div>


                    {{-- PRODUCTO SELECCIONADO --}}
                    <div
                        id="productoSeleccionado"
                        style="
                            margin-top:8px;
                            color:#666;
                        "
                    >
                        Ningún producto seleccionado.
                    </div>


                    <div
                        id="errorProducto"
                        class="ortomax-form-error"
                    ></div>

                </div>


                {{-- ================================================= --}}
                {{-- CÓDIGO / REGISTRO SANITARIO --}}
                {{-- ================================================= --}}

                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:20px;
                    "
                >

                    {{-- CÓDIGO --}}
                    <div class="ortomax-form-group">

                        <label class="ortomax-form-label">
                            Código del producto del proveedor
                        </label>

                        <input
                            type="text"
                            id="codigoProducto"
                            name="codigo_producto"
                            class="ortomax-form-control"
                            maxlength="100"
                        >

                    </div>


                    {{-- REGISTRO --}}
                    <div class="ortomax-form-group">

                        <label class="ortomax-form-label">
                            Registro Sanitario
                        </label>

                        <input
                            type="text"
                            id="registroSanitario"
                            name="registro_sanitario"
                            class="ortomax-form-control"
                            maxlength="150"
                        >

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- ESTADO --}}
                {{-- ================================================= --}}

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Estado
                    </label>

                    <select
                        id="activo"
                        name="activo"
                        class="ortomax-form-control"
                    >

                        <option value="1">
                            ACTIVO
                        </option>

                        <option value="0">
                            INACTIVO
                        </option>

                    </select>

                </div>


                <div
                    id="errorGeneralProductoProveedor"
                    class="ortomax-form-error"
                ></div>

            </div>


            {{-- FOOTER --}}
            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarProductoProveedor()"
                >
                    Cancelar
                </button>


                <button
                type="submit"
                class="ortomax-btn ortomax-btn-primary"
                >
                      Guardar
                </button>

            </div>

        </form>

    </div>

</div>


{{-- ============================================================= --}}
{{-- MODAL IMPORTAR EXCEL --}}
{{-- ============================================================= --}}

<div
    id="modalImportarProductosProveedores"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Importar Productos por Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarImportarProductosProveedores()"
            >
                ×
            </button>

        </div>


        <form
            id="formImportarProductosProveedores"
            enctype="multipart/form-data"
        >

            <div class="ortomax-modal-body">

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Archivo Excel
                    </label>

                    <input
                        type="file"
                        id="archivoProductosProveedores"
                        name="archivo"
                        class="ortomax-form-control"
                        accept=".xlsx,.xls,.csv"
                    >

                </div>


                <div
                    style="
                        margin-top:15px;
                        padding:15px;
                        background:#f5f7fa;
                        border-radius:6px;
                        font-size:14px;
                    "
                >

                    <strong>Formato esperado:</strong>

                    <br><br>

                    <code>
                        CODIGO_MAESTRO |
                        PROVEEDOR_CODIGO |
                        PRODUCTO_CONTIFICO |
                        CODIGO_PRODUCTO |
                        REGISTRO_SANITARIO |
                        ACTIVO
                    </code>

                    <br><br>

                    Ejemplo:

                    <br>

                    <code>
                        0000396852 |
                        PROV001 |
                        P01C10 |
                        ABC123 |
                        RS-001 |
                        ACTIVO
                    </code>

                </div>


                <div
                    id="resultadoImportacionProductosProveedores"
                    class="ortomax-form-error"
                    style="margin-top:15px;"
                ></div>

            </div>


            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarImportarProductosProveedores()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Importar
                </button>

            </div>

        </form>

    </div>

</div>




{{-- ============================================================= --}}
{{-- MODAL VER --}}
{{-- ============================================================= --}}

<div
    id="modalVerProductoProveedor"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Producto por Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModalVerProductoProveedor()"
            >
                ×
            </button>

        </div>


        <div class="ortomax-modal-body">

            <div id="detalleProductoProveedor"></div>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModalVerProductoProveedor()"
            >
                Cerrar
            </button>

        </div>

    </div>

</div>



{{-- ============================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ============================================================= --}}

@php

    $proveedoresDisponibles =
        \App\Models\Proveedor::where('estado', true)
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'sigla'
            ]);


    $productosMaestrosDisponibles =
        \App\Models\ProductoMaestro::where('estado', true)
            ->orderBy('codigo_maestro')
            ->get([
                'id',
                'codigo_maestro',
                'descripcion'
            ]);

@endphp

<script>


/*
|--------------------------------------------------------------------------
| BUSCADOR PRINCIPAL - PRODUCTOS POR PROVEEDOR
|--------------------------------------------------------------------------
|
| La búsqueda principal NO se ejecuta al escribir.
| Solo se ejecuta mediante:
|   - botón "Buscar"
|   - tecla ENTER
|
| Los buscadores internos de los modales permanecen intactos.
|--------------------------------------------------------------------------
*/

const buscadorPrincipal =
    document.querySelector('input[name="q"]');

const formularioBusqueda =
    buscadorPrincipal?.closest('form');

/*
 * IMPORTANTE:
 * No se registra ningún listener sobre:
 * input
 * keyup
 * keydown
 * keypress
 *
 * El formulario GET normal se encarga de realizar
 * la búsqueda cuando el usuario presiona ENTER
 * o hace clic en "Buscar".
 */
const csrfTokenProductoProveedor =
    document.querySelector(
        'meta[name="csrf-token"]'
    )?.getAttribute('content')
    || '{{ csrf_token() }}';


    const proveedoresDisponibles =
    @json($proveedoresDisponibles);


    const productosMaestrosDisponibles =
        @json($productosMaestrosDisponibles);


    let temporizadorBusquedaProducto = null;

    let temporizadorBusquedaProveedor = null;

    let temporizadorBusquedaProductoMaestro = null;

/*
|--------------------------------------------------------------------------
| BUSCADOR PRODUCTO MAESTRO
|--------------------------------------------------------------------------
*/

document
    .getElementById('buscarProductoMaestro')
    .addEventListener(
        'input',
        function()
        {

            clearTimeout(
                temporizadorBusquedaProductoMaestro
            );


            const texto =
                this.value.trim().toLowerCase();


            /*
            |----------------------------------------------------------
            | Si el usuario modifica el texto, eliminamos la
            | selección anterior.
            |----------------------------------------------------------
            */

            document.getElementById(
                'productoMaestroId'
            ).value = '';


            document.getElementById(
                'productoMaestroSeleccionado'
            ).innerHTML =
                'Ningún producto maestro seleccionado.';


            /*
            |----------------------------------------------------------
            | Campo vacío
            |----------------------------------------------------------
            */

            if (texto.length < 1) {

                document.getElementById(
                    'resultadosProductosMaestros'
                ).style.display = 'none';

                return;
            }


            temporizadorBusquedaProductoMaestro =
                setTimeout(
                    function()
                    {

                        buscarProductosMaestros(
                            texto
                        );

                    },
                    50
                );

        }
    );



/*
|--------------------------------------------------------------------------
| BUSCAR PRODUCTOS MAESTROS
|--------------------------------------------------------------------------
*/

function buscarProductosMaestros(texto)
{

    const contenedor =
        document.getElementById(
            'resultadosProductosMaestros'
        );


    const resultados =
        productosMaestrosDisponibles.filter(
            function(producto)
            {

                const codigo =
                    (
                        producto.codigo_maestro ||
                        ''
                    ).toLowerCase();


                const descripcion =
                    (
                        producto.descripcion ||
                        ''
                    ).toLowerCase();


                return (
                    codigo.includes(texto) ||
                    descripcion.includes(texto)
                );

            }
        );


    contenedor.innerHTML = '';


    /*
    |--------------------------------------------------------------
    | Sin resultados
    |--------------------------------------------------------------
    */

    if (resultados.length === 0) {

        contenedor.innerHTML =

            '<div style="' +
                'padding:12px;' +
                'color:#777;' +
            '">' +

                'No se encontraron productos maestros.' +

            '</div>';


        contenedor.style.display =
            'block';

        return;
    }


    /*
    |--------------------------------------------------------------
    | Mostrar resultados
    |--------------------------------------------------------------
    */

    resultados.forEach(
        function(producto)
        {

            const elemento =
                document.createElement('div');


            elemento.style.padding =
                '10px 12px';

            elemento.style.cursor =
                'pointer';

            elemento.style.borderBottom =
                '1px solid #eee';

            elemento.style.background =
                '#fff';


            elemento.innerHTML =

                '<strong>' +

                (
                    producto.codigo_maestro ||
                    ''
                ) +

                '</strong>' +

                '<br>' +

                '<span>' +

                (
                    producto.descripcion ||
                    ''
                ) +

                '</span>';


            /*
            |------------------------------------------------------
            | Hover
            |------------------------------------------------------
            */

            elemento.addEventListener(
                'mouseenter',
                function()
                {

                    this.style.background =
                        '#f5f7fa';

                }
            );


            elemento.addEventListener(
                'mouseleave',
                function()
                {

                    this.style.background =
                        '#fff';

                }
            );


            /*
            |------------------------------------------------------
            | Seleccionar
            |------------------------------------------------------
            */

            elemento.addEventListener(
                'click',
                function()
                {

                    seleccionarProductoMaestro(
                        producto
                    );

                }
            );


            contenedor.appendChild(
                elemento
            );

        }
    );


    contenedor.style.display =
        'block';

}



/*
|--------------------------------------------------------------------------
| SELECCIONAR PRODUCTO MAESTRO
|--------------------------------------------------------------------------
*/

function seleccionarProductoMaestro(producto)
{

    /*
    |--------------------------------------------------------------
    | Guardar ID real
    |--------------------------------------------------------------
    */

    document.getElementById(
        'productoMaestroId'
    ).value =
        producto.id;


    /*
    |--------------------------------------------------------------
    | Mostrar código en el buscador
    |--------------------------------------------------------------
    */

    document.getElementById(
        'buscarProductoMaestro'
    ).value =
        producto.codigo_maestro;


    /*
    |--------------------------------------------------------------
    | Mostrar selección
    |--------------------------------------------------------------
    */

    document.getElementById(
        'productoMaestroSeleccionado'
    ).innerHTML =

        '<strong>' +
        producto.codigo_maestro +
        '</strong>' +

        ' — ' +

        producto.descripcion;


    /*
    |--------------------------------------------------------------
    | Ocultar resultados
    |--------------------------------------------------------------
    */

    document.getElementById(
        'resultadosProductosMaestros'
    ).style.display =
        'none';

}






/*
|--------------------------------------------------------------------------
| PROVEEDORES DISPONIBLES
|--------------------------------------------------------------------------
|




/*
|--------------------------------------------------------------------------
| TEMPORIZADORES
|--------------------------------------------------------------------------
*/



/*
|--------------------------------------------------------------------------
| ABRIR NUEVO
|--------------------------------------------------------------------------
*/

function abrirNuevoProductoProveedor()
{

    document.getElementById(
    'buscarProductoMaestro'
    ).value = '';


    document.getElementById(
        'productoMaestroId'
    ).value = '';


    document.getElementById(
        'productoMaestroSeleccionado'
    ).innerHTML =
        'Ningún producto maestro seleccionado.';


    document.getElementById(
        'resultadosProductosMaestros'
    ).style.display =
        'none';


    /*
    |--------------------------------------------------------------
    | Limpiar proveedor
    |--------------------------------------------------------------
    */

    document.getElementById(
        'buscarProveedor'
    ).value = '';


    document.getElementById(
        'proveedorId'
    ).value = '';


    document.getElementById(
        'proveedorSeleccionado'
    ).innerHTML =
        'Ningún proveedor seleccionado.';


    document.getElementById(
        'resultadosProveedores'
    ).style.display = 'none';


    /*
    |--------------------------------------------------------------
    | Limpiar producto Contífico
    |--------------------------------------------------------------
    */

    document.getElementById(
        'productoId'
    ).value = '';


    document.getElementById(
        'buscarProductoContifico'
    ).value = '';


    document.getElementById(
        'productoSeleccionado'
    ).innerHTML =
        'Ningún producto seleccionado.';


    document.getElementById(
        'resultadosProductosContifico'
    ).style.display = 'none';


    /*
    |--------------------------------------------------------------
    | Título
    |--------------------------------------------------------------
    */

    document.getElementById(
        'modalProductoProveedorTitulo'
    ).textContent =
        'Nuevo Producto por Proveedor';


    /*
    |--------------------------------------------------------------
    | Errores
    |--------------------------------------------------------------
    */

    limpiarErroresProductoProveedor();


    /*
    |--------------------------------------------------------------
    | Abrir modal
    |--------------------------------------------------------------
    */

    document.getElementById(
        'modalProductoProveedor'
    ).style.display = 'flex';

}



/*
|--------------------------------------------------------------------------
| CERRAR MODAL NUEVO / EDITAR
|--------------------------------------------------------------------------
*/

function cerrarProductoProveedor()
{

    document.getElementById(
        'modalProductoProveedor'
    ).style.display = 'none';

}



/*
|--------------------------------------------------------------------------
| LIMPIAR ERRORES
|--------------------------------------------------------------------------
*/

function limpiarErroresProductoProveedor()
{

    document.querySelectorAll(
        '#modalProductoProveedor .ortomax-form-error'
    )
    .forEach(function(elemento)
    {

        elemento.textContent = '';

    });

}



/*
|--------------------------------------------------------------------------
| ================================================================
| BUSCADOR DE PROVEEDORES
| ================================================================
|--------------------------------------------------------------------------
*/


document
    .getElementById('buscarProveedor')
    .addEventListener(
        'input',
        function()
        {

            clearTimeout(
                temporizadorBusquedaProveedor
            );


            const texto =
                this.value.trim().toLowerCase();


            /*
            |------------------------------------------------------
            | Si cambia el texto, quitamos la selección anterior.
            |------------------------------------------------------
            */

            document.getElementById(
                'proveedorId'
            ).value = '';


            document.getElementById(
                'proveedorSeleccionado'
            ).innerHTML =
                'Ningún proveedor seleccionado.';


            /*
            |------------------------------------------------------
            | No buscar con campo vacío.
            |------------------------------------------------------
            */

            if (texto.length < 1) {

                document.getElementById(
                    'resultadosProveedores'
                ).style.display = 'none';

                return;
            }


            temporizadorBusquedaProveedor =
                setTimeout(
                    function()
                    {

                        buscarProveedores(texto);

                    },
                    150
                );

        }
    );



/*
|--------------------------------------------------------------------------
| BUSCAR PROVEEDORES
|--------------------------------------------------------------------------
*/

function buscarProveedores(texto)
{

    const contenedor =
        document.getElementById(
            'resultadosProveedores'
        );


    const resultados =
        proveedoresDisponibles.filter(
            function(proveedor)
            {

                const codigo =
                    (
                        proveedor.codigo || ''
                    ).toLowerCase();


                const sigla =
                    (
                        proveedor.sigla || ''
                    ).toLowerCase();


                const nombre =
                    (
                        proveedor.nombre || ''
                    ).toLowerCase();


                return (

                    codigo.includes(texto) ||

                    sigla.includes(texto) ||

                    nombre.includes(texto)

                );

            }
        );


    contenedor.innerHTML = '';


    /*
    |--------------------------------------------------------------
    | Sin resultados
    |--------------------------------------------------------------
    */

    if (resultados.length === 0) {

        contenedor.innerHTML =

            '<div style="' +
                'padding:12px;' +
                'color:#777;' +
            '">' +

                'No se encontraron proveedores.' +

            '</div>';


        contenedor.style.display =
            'block';

        return;
    }


    /*
    |--------------------------------------------------------------
    | Mostrar resultados
    |--------------------------------------------------------------
    */

    resultados.forEach(
        function(proveedor)
        {

            const elemento =
                document.createElement('div');


            elemento.style.padding =
                '10px 12px';

            elemento.style.cursor =
                'pointer';

            elemento.style.borderBottom =
                '1px solid #eee';

            elemento.style.background =
                '#fff';


            elemento.innerHTML =

                '<strong>' +

                (
                    proveedor.sigla ||
                    proveedor.codigo ||
                    ''
                ) +

                '</strong>' +

                ' - ' +

                (
                    proveedor.nombre ||
                    ''
                );


            /*
            |------------------------------------------------------
            | Hover
            |------------------------------------------------------
            */

            elemento.addEventListener(
                'mouseenter',
                function()
                {

                    this.style.background =
                        '#f5f7fa';

                }
            );


            elemento.addEventListener(
                'mouseleave',
                function()
                {

                    this.style.background =
                        '#fff';

                }
            );


            /*
            |------------------------------------------------------
            | Seleccionar
            |------------------------------------------------------
            */

            elemento.addEventListener(
                'click',
                function()
                {

                    seleccionarProveedor(
                        proveedor
                    );

                }
            );


            contenedor.appendChild(
                elemento
            );

        }
    );


    contenedor.style.display =
        'block';

}



/*
|--------------------------------------------------------------------------
| SELECCIONAR PROVEEDOR
|--------------------------------------------------------------------------
*/

function seleccionarProveedor(proveedor)
{

    /*
    |--------------------------------------------------------------
    | Guardamos el ID real
    |--------------------------------------------------------------
    */

    document.getElementById(
        'proveedorId'
    ).value =
        proveedor.id;


    /*
    |--------------------------------------------------------------
    | Mostramos sigla/código
    |--------------------------------------------------------------
    */

    document.getElementById(
        'buscarProveedor'
    ).value =

        proveedor.sigla ||

        proveedor.codigo ||

        '';


    /*
    |--------------------------------------------------------------
    | Mostramos confirmación
    |--------------------------------------------------------------
    */

    document.getElementById(
        'proveedorSeleccionado'
    ).innerHTML =

        '<strong>' +

        (
            proveedor.sigla ||
            proveedor.codigo ||
            ''
        ) +

        '</strong>' +

        ' — ' +

        (
            proveedor.nombre ||
            ''
        );


    /*
    |--------------------------------------------------------------
    | Ocultar resultados
    |--------------------------------------------------------------
    */

    document.getElementById(
        'resultadosProveedores'
    ).style.display =
        'none';

}



/*
|--------------------------------------------------------------------------
| ================================================================
| BUSCADOR PRODUCTOS CONTÍFICO
| ================================================================
|--------------------------------------------------------------------------
*/


document
    .getElementById('buscarProductoContifico')
    .addEventListener(
        'input',
        function()
        {

            clearTimeout(
                temporizadorBusquedaProducto
            );


            const texto =
                this.value.trim();


            if (texto.length < 2) {

                document.getElementById(
                    'resultadosProductosContifico'
                ).style.display = 'none';

                return;
            }


            temporizadorBusquedaProducto =
                setTimeout(
                    function()
                    {

                        buscarProductosContifico(
                            texto
                        );

                    },
                    300
                );

        }
    );



/*
|--------------------------------------------------------------------------
| BUSCAR PRODUCTOS CONTÍFICO
|--------------------------------------------------------------------------
*/

function buscarProductosContifico(texto)
{

    fetch(
        "{{ route('productos_proveedores.buscar_productos') }}"
        + '?q='
        + encodeURIComponent(texto),
        {
            headers:
            {

                'Accept':
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest'

            }

        }
    )
    .then(
        function(response)
        {

            return response.json();

        }
    )
    .then(
        function(data)
        {

            const contenedor =
                document.getElementById(
                    'resultadosProductosContifico'
                );


            contenedor.innerHTML = '';


            /*
            |------------------------------------------------------
            | Sin resultados
            |------------------------------------------------------
            */

            if (
                !data.ok ||
                !data.productos ||
                data.productos.length === 0
            ) {

                contenedor.innerHTML =

                    '<div style="' +
                        'padding:12px;' +
                        'color:#777;' +
                    '">' +

                        'No se encontraron productos.' +

                    '</div>';


                contenedor.style.display =
                    'block';

                return;
            }


            /*
            |------------------------------------------------------
            | Resultados
            |------------------------------------------------------
            */

            data.productos.forEach(
                function(producto)
                {

                    const elemento =
                        document.createElement('div');


                    elemento.style.padding =
                        '10px 12px';

                    elemento.style.cursor =
                        'pointer';

                    elemento.style.borderBottom =
                        '1px solid #eee';

                    elemento.style.background =
                        '#fff';


                        elemento.innerHTML =

                        '<div style="font-weight:700;font-size:14px;color:#1f2937;">' +

                            (producto.codigo || 'SIN CÓDIGO') +

                        '</div>' +

                        '<div style="font-size:13px;font-weight:600;margin-top:3px;">' +

                            (producto.nombre || 'SIN NOMBRE') +

                        '</div>' +

                        '<div style="font-size:12px;color:#666;margin-top:4px;">' +

                            'Código JBG: ' +

                            (producto.codigojbg || '—') +

                            ' &nbsp; | &nbsp; ' +

                            'Código barras: ' +

                            (producto.codigo_barra || '—') +

                        '</div>' +

                        '<div style="font-size:12px;color:#666;margin-top:3px;">' +

                            'Marca: ' +

                            (producto.marca || '—') +

                            ' &nbsp; | &nbsp; ' +

                            'Categoría: ' +

                            (producto.categoria || '—') +

                        '</div>' +

                        '<div style="font-size:12px;color:#666;margin-top:3px;">' +

                            'Familia: ' +

                            (producto.familia || '—') +

                            ' &nbsp; | &nbsp; ' +

                            'Subfamilia: ' +

                            (producto.subfamilia || '—') +

                        '</div>' +

                        '<div style="font-size:12px;color:#666;margin-top:3px;">' +

                            'Tipo: ' +

                            (producto.tipo || '—') +

                            ' &nbsp; | &nbsp; ' +

                            'Tipo producto: ' +

                            (producto.tipo_producto || '—') +

                        '</div>' +

                        '<div style="font-size:12px;color:#555;margin-top:5px;">' +

                            (producto.descripcion || 'Sin descripción') +

                        '</div>';


                    /*
                    |--------------------------------------------------
                    | Hover
                    |--------------------------------------------------
                    */

                    elemento.addEventListener(
                        'mouseenter',
                        function()
                        {

                            this.style.background =
                                '#f5f7fa';

                        }
                    );


                    elemento.addEventListener(
                        'mouseleave',
                        function()
                        {

                            this.style.background =
                                '#fff';

                        }
                    );


                    /*
                    |--------------------------------------------------
                    | Seleccionar
                    |--------------------------------------------------
                    */

                    elemento.addEventListener(
                        'click',
                        function()
                        {

                            seleccionarProducto(
                                producto
                            );

                        }
                    );


                    contenedor.appendChild(
                        elemento
                    );

                }
            );


            contenedor.style.display =
                'block';

        }
    )
    .catch(
        function(error)
        {

            console.error(error);

        }
    );

}



/*
|--------------------------------------------------------------------------
| SELECCIONAR PRODUCTO CONTÍFICO
|--------------------------------------------------------------------------
*/

function seleccionarProducto(producto)
{

    document.getElementById(
        'productoId'
    ).value =
        producto.id;


    document.getElementById(
        'buscarProductoContifico'
    ).value =
        producto.codigo || '';


    document.getElementById(
        'productoSeleccionado'
    ).innerHTML =

        '<strong>' +

        (
            producto.codigo ||
            ''
        ) +

        '</strong>' +

        ' — ' +

        (
            producto.nombre ||
            ''
        );


    document.getElementById(
        'resultadosProductosContifico'
    ).style.display =
        'none';

}



/*
|--------------------------------------------------------------------------
| ================================================================
| GUARDAR
| ================================================================
|--------------------------------------------------------------------------
*/


document
    .getElementById('formProductoProveedor')
    .addEventListener(
        'submit',
        async function(event)
        {

            event.preventDefault();


            limpiarErroresProductoProveedor();


            const id =
                document.getElementById(
                    'productoProveedorId'
                ).value;


            const datos =
            {

                producto_maestro_id:
                    document.getElementById(
                        'productoMaestroId'
                    ).value,


                proveedor_id:
                    document.getElementById(
                        'proveedorId'
                    ).value,


                producto_id:
                    document.getElementById(
                        'productoId'
                    ).value,


                codigo_producto:
                    document.getElementById(
                        'codigoProducto'
                    ).value.trim(),


                registro_sanitario:
                    document.getElementById(
                        'registroSanitario'
                    ).value.trim(),


                activo:
                    document.getElementById(
                        'activo'
                    ).value

            };


            /*
            |----------------------------------------------------------
            | Validación visual del proveedor
            |----------------------------------------------------------
            */

            if (!datos.proveedor_id) {

                document.getElementById(
                    'errorProveedor'
                ).textContent =
                    'Debe seleccionar un proveedor.';

                return;
            }


            /*
            |----------------------------------------------------------
            | URL
            |----------------------------------------------------------
            */

            let url =
                "{{ route('productos_proveedores.store') }}";


            let method =
                'POST';


            if (id) {

                url =
                    "{{ url('/productos-proveedores') }}"
                    + '/'
                    + id;


                method =
                    'PUT';

            }


            try {

                const response =
                    await fetch(
                        url,
                        {

                            method:
                                method,

                            headers:
                            {

                                'Accept':
                                    'application/json',

                                'Content-Type':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'X-CSRF-TOKEN':
                                    csrfTokenProductoProveedor

                            },

                            body:
                                JSON.stringify(
                                    datos
                                )

                        }
                    );


                const data =
                    await response.json();

                    console.log('DATOS ENVIADOS:', datos);
                    console.log('RESPUESTA DEL SERVIDOR:', data);


                /*
                |------------------------------------------------------
                | Errores de validación
                |------------------------------------------------------
                */

                if (
                    response.status === 422 &&
                    data.errors
                ) {

                    const errores =
                        data.errors;


                    if (
                        errores.producto_maestro_id
                    ) {

                        document.getElementById(
                            'errorProductoMaestro'
                        ).textContent =
                            errores.producto_maestro_id[0];

                    }


                    if (
                        errores.proveedor_id
                    ) {

                        document.getElementById(
                            'errorProveedor'
                        ).textContent =
                            errores.proveedor_id[0];

                    }


                    if (
                        errores.producto_id
                    ) {

                        document.getElementById(
                            'errorProducto'
                        ).textContent =
                            errores.producto_id[0];

                    }


                    return;
                }


                /*
                |------------------------------------------------------
                | Error general
                |------------------------------------------------------
                */

                if (!response.ok) {

                    document.getElementById(
                        'errorGeneralProductoProveedor'
                    ).textContent =

                        data.message ||

                        'No fue posible guardar el registro.';

                    return;
                }


                /*
                |------------------------------------------------------
                | Guardado correcto
                |------------------------------------------------------
                */

                if (data.ok) {

                    cerrarProductoProveedor();

                    window.location.reload();

                }

            }
            catch(error)
            {

                console.error(error);


                document.getElementById(
                    'errorGeneralProductoProveedor'
                ).textContent =

                    'Ocurrió un error al guardar el registro.';

            }

        }
    );



/*
|--------------------------------------------------------------------------
| ================================================================
| VER
| ================================================================
|--------------------------------------------------------------------------
*/

function verProductoProveedor(id)
{

    fetch(
        "{{ url('/productos-proveedores') }}"
        + '/'
        + id,
        {

            headers:
            {

                'Accept':
                    'application/json'

            }

        }
    )
    .then(
        function(response)
        {

            return response.json();

        }
    )
    .then(
        function(data)
        {

            if (!data.ok) {

                alert(
                    data.message ||
                    'No fue posible consultar el registro.'
                );

                return;
            }


            const item =
                data.producto_proveedor;


            const maestro =
                item.producto_maestro;


            const proveedor =
                item.proveedor;


            const producto =
                item.producto;


            let html = '';


            /*
            |----------------------------------------------------------
            | Producto Maestro
            |----------------------------------------------------------
            */

            html +=

                '<div class="ortomax-form-group">' +

                    '<label class="ortomax-form-label">' +
                        'Producto Maestro' +
                    '</label>' +

                    '<input ' +
                        'type="text" ' +
                        'class="ortomax-form-control" ' +
                        'readonly ' +
                        'value="' +

                        (
                            maestro
                                ? maestro.codigo_maestro +
                                  ' - ' +
                                  maestro.descripcion
                                : '—'
                        ) +

                    '">' +

                '</div>';


            /*
            |----------------------------------------------------------
            | Proveedor
            |----------------------------------------------------------
            */

            html +=

                '<div class="ortomax-form-group">' +

                    '<label class="ortomax-form-label">' +
                        'Proveedor' +
                    '</label>' +

                    '<input ' +
                        'type="text" ' +
                        'class="ortomax-form-control" ' +
                        'readonly ' +
                        'value="' +

                        (
                            proveedor
                                ? (
                                    proveedor.sigla ||
                                    proveedor.codigo ||
                                    ''
                                  )
                                  +
                                  ' - ' +
                                  proveedor.nombre
                                : '—'
                        ) +

                    '">' +

                '</div>';


            /*
            |----------------------------------------------------------
            | Producto Contífico
            |----------------------------------------------------------
            */

            html +=

                '<div class="ortomax-form-group">' +

                    '<label class="ortomax-form-label">' +
                        'Producto Contífico' +
                    '</label>' +

                    '<input ' +
                        'type="text" ' +
                        'class="ortomax-form-control" ' +
                        'readonly ' +
                        'value="' +

                        (
                            producto
                                ? producto.codigo +
                                  ' - ' +
                                  producto.nombre
                                : '—'
                        ) +

                    '">' +

                '</div>';


            /*
            |----------------------------------------------------------
            | Código proveedor
            |----------------------------------------------------------
            */

            html +=

                '<div class="ortomax-form-group">' +

                    '<label class="ortomax-form-label">' +
                        'Código del producto del proveedor' +
                    '</label>' +

                    '<input ' +
                        'type="text" ' +
                        'class="ortomax-form-control" ' +
                        'readonly ' +
                        'value="' +

                        (
                            item.codigo_producto ||
                            '—'
                        ) +

                    '">' +

                '</div>';


            /*
            |----------------------------------------------------------
            | Registro sanitario
            |----------------------------------------------------------
            */

            html +=

                '<div class="ortomax-form-group">' +

                    '<label class="ortomax-form-label">' +
                        'Registro Sanitario' +
                    '</label>' +

                    '<input ' +
                        'type="text" ' +
                        'class="ortomax-form-control" ' +
                        'readonly ' +
                        'value="' +

                        (
                            item.registro_sanitario ||
                            '—'
                        ) +

                    '">' +

                '</div>';


            /*
            |----------------------------------------------------------
            | Estado
            |----------------------------------------------------------
            */

            html +=

                '<div class="ortomax-form-group">' +

                    '<label class="ortomax-form-label">' +
                        'Estado' +
                    '</label>' +

                    '<input ' +
                        'type="text" ' +
                        'class="ortomax-form-control" ' +
                        'readonly ' +
                        'value="' +

                        (
                            item.activo
                                ? 'ACTIVO'
                                : 'INACTIVO'
                        ) +

                    '">' +

                '</div>';


            document.getElementById(
                'detalleProductoProveedor'
            ).innerHTML =
                html;


            document.getElementById(
                'modalVerProductoProveedor'
            ).style.display =
                'flex';

        }
    )
    .catch(
        function(error)
        {

            console.error(error);

            alert(
                'No fue posible consultar el registro.'
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| ================================================================
| EDITAR
| ================================================================
|--------------------------------------------------------------------------
*/

function editarProductoProveedor(id)
{

    fetch(
        "{{ url('/productos-proveedores') }}"
        + '/'
        + id,
        {

            headers:
            {

                'Accept':
                    'application/json'

            }

        }
    )
    .then(
        function(response)
        {

            return response.json();

        }
    )
    .then(
        function(data)
        {

            if (!data.ok) {

                alert(
                    data.message ||
                    'No fue posible consultar el registro.'
                );

                return;
            }


            const item =
                data.producto_proveedor;


            /*
            |----------------------------------------------------------
            | ID
            |----------------------------------------------------------
            */

            document.getElementById(
                'productoProveedorId'
            ).value =
                item.id;


            /*
            |----------------------------------------------------------
            | Producto Maestro
            |----------------------------------------------------------
            */

            document.getElementById(
                'productoMaestroId'
            ).value =
                item.producto_maestro_id;

                document.getElementById(
    'buscarProductoMaestro'
).value =

    item.producto_maestro

        ? item.producto_maestro.codigo_maestro
        : '';


        document.getElementById(
            'productoMaestroSeleccionado'
        ).innerHTML =

            item.producto_maestro

                ? '<strong>' +
                item.producto_maestro.codigo_maestro +
                '</strong> — ' +
                item.producto_maestro.descripcion

                : 'Ningún producto maestro seleccionado.';


        document.getElementById(
            'resultadosProductosMaestros'
        ).style.display =
            'none';


            /*
            |----------------------------------------------------------
            | PROVEEDOR
            |----------------------------------------------------------
            |
            | Guardamos el ID real y mostramos el código/sigla.
            |
            */

            document.getElementById(
                'proveedorId'
            ).value =
                item.proveedor_id;


            document.getElementById(
                'buscarProveedor'
            ).value =

                item.proveedor

                    ? (
                        item.proveedor.sigla ||
                        item.proveedor.codigo ||
                        ''
                      )

                    : '';


            document.getElementById(
                'proveedorSeleccionado'
            ).innerHTML =

                item.proveedor

                    ? '<strong>' +
                      (
                          item.proveedor.sigla ||
                          item.proveedor.codigo ||
                          ''
                      ) +
                      '</strong> — ' +
                      item.proveedor.nombre

                    : 'Ningún proveedor seleccionado.';


            document.getElementById(
                'resultadosProveedores'
            ).style.display =
                'none';


            /*
            |----------------------------------------------------------
            | Producto Contífico
            |----------------------------------------------------------
            */

            document.getElementById(
                'productoId'
            ).value =
                item.producto_id;


            document.getElementById(
                'buscarProductoContifico'
            ).value =

                item.producto
                    ? item.producto.codigo
                    : '';


            document.getElementById(
                'productoSeleccionado'
            ).innerHTML =

                item.producto

                    ? '<strong>' +
                      item.producto.codigo +
                      '</strong> — ' +
                      item.producto.nombre

                    : 'Ningún producto seleccionado.';


            /*
            |----------------------------------------------------------
            | Código proveedor
            |----------------------------------------------------------
            */

            document.getElementById(
                'codigoProducto'
            ).value =
                item.codigo_producto || '';


            /*
            |----------------------------------------------------------
            | Registro sanitario
            |----------------------------------------------------------
            */

            document.getElementById(
                'registroSanitario'
            ).value =
                item.registro_sanitario || '';


            /*
            |----------------------------------------------------------
            | Estado
            |----------------------------------------------------------
            */

            document.getElementById(
                'activo'
            ).value =
                item.activo
                    ? '1'
                    : '0';


            /*
            |----------------------------------------------------------
            | Título
            |----------------------------------------------------------
            */

            document.getElementById(
                'modalProductoProveedorTitulo'
            ).textContent =
                'Editar Producto por Proveedor';


            /*
            |----------------------------------------------------------
            | Errores
            |----------------------------------------------------------
            */

            limpiarErroresProductoProveedor();


            /*
            |----------------------------------------------------------
            | Abrir modal
            |----------------------------------------------------------
            */

            document.getElementById(
                'modalProductoProveedor'
            ).style.display =
                'flex';

        }
    )
    .catch(
        function(error)
        {

            console.error(error);

            alert(
                'No fue posible consultar el registro.'
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| ================================================================
| ELIMINAR
| ================================================================
|--------------------------------------------------------------------------
*/

function eliminarProductoProveedor(id)
{

    if (
        !confirm(
            '¿Está seguro de eliminar este producto por proveedor?'
        )
    ) {

        return;

    }


    fetch(
        "{{ url('/productos-proveedores') }}"
        + '/'
        + id,
        {

            method:
                'DELETE',

            headers:
            {

                'Accept':
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest',

                'X-CSRF-TOKEN':
                    csrfTokenProductoProveedor

            }

        }
    )
    .then(
        function(response)
        {

            return response.json();

        }
    )
    .then(
        function(data)
        {

            if (!data.ok) {

                alert(
                    data.message ||
                    'No fue posible eliminar el registro.'
                );

                return;
            }


            const fila =
                document.getElementById(
                    'fila-producto-proveedor-' + id
                );


            if (fila) {

                fila.remove();

            }

        }
    )
    .catch(
        function(error)
        {

            console.error(error);

            alert(
                'No fue posible eliminar el registro.'
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| CERRAR MODAL VER
|--------------------------------------------------------------------------
*/

function cerrarModalVerProductoProveedor()
{

    document.getElementById(
        'modalVerProductoProveedor'
    ).style.display =
        'none';

}



/*
|--------------------------------------------------------------------------
| CERRAR MODAL AL HACER CLIC FUERA
|--------------------------------------------------------------------------


document
    .getElementById('modalProductoProveedor')
    .addEventListener(
        'click',
        function(event)
        {

            if (event.target === this) {

                cerrarProductoProveedor();

            }

        }
    );


document
    .getElementById('modalVerProductoProveedor')
    .addEventListener(
        'click',
        function(event)
        {

            if (event.target === this) {

                cerrarModalVerProductoProveedor();

            }

        }
    );
    */

/*
|--------------------------------------------------------------------------
| IMPORTAR PRODUCTOS POR PROVEEDOR
|--------------------------------------------------------------------------
*/

function abrirImportarProductosProveedores()
{
    document.getElementById(
        'archivoProductosProveedores'
    ).value = '';

    document.getElementById(
        'resultadoImportacionProductosProveedores'
    ).textContent = '';

    document.getElementById(
        'modalImportarProductosProveedores'
    ).style.display = 'flex';
}


function cerrarImportarProductosProveedores()
{
    document.getElementById(
        'modalImportarProductosProveedores'
    ).style.display = 'none';
}


document
    .getElementById('formImportarProductosProveedores')
    .addEventListener(
        'submit',
        async function(event)
        {
            event.preventDefault();

            const archivo =
                document.getElementById(
                    'archivoProductosProveedores'
                ).files[0];

            const resultado =
                document.getElementById(
                    'resultadoImportacionProductosProveedores'
                );

            resultado.textContent = '';

            if (!archivo) {

                resultado.textContent =
                    'Debe seleccionar un archivo Excel.';

                return;
            }


            const formulario =
                new FormData();

            formulario.append(
                'archivo',
                archivo
            );


            try {

                const response =
                    await fetch(
                        "{{ route('productos_proveedores.importar') }}",
                        {
                            method: 'POST',

                            headers: {
                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'X-CSRF-TOKEN':
                                    csrfTokenProductoProveedor
                            },

                            body:
                                formulario
                        }
                    );


                const data =
                    await response.json();


                if (!response.ok || !data.ok) {

                    resultado.textContent =
                        data.message ||
                        'No fue posible importar el archivo.';

                    return;
                }


                cerrarImportarProductosProveedores();

                window.location.reload();

            }
            catch(error) {

                console.error(error);

                resultado.textContent =
                    'Ocurrió un error al importar el archivo.';
            }

        }
    );


</script>

@endsection

