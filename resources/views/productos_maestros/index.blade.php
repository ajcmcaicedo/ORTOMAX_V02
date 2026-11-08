@extends('layouts.app')

@section('title', 'Productos Maestros')

@section('content')

<div class="ortomax-page-header">
    <div>
        <h1 class="ortomax-page-title">Productos Maestros</h1>
        <p class="ortomax-page-subtitle">
            Homologación clínica y lógica de productos ORTOMAX
        </p>
    </div>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">

        @if(auth()->user()->tienePermiso('productos_maestros.importar'))
            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="abrirImportarProductosMaestros()"
            >
                📥 Importar Excel
            </button>
        @endif

        @if(auth()->user()->tienePermiso('productos_maestros.exportar'))
            <a
                href="{{ route('productos_maestros.exportar') }}"
                class="ortomax-btn ortomax-btn-secondary"
                style="text-decoration:none;"
            >
                📤 Exportar Excel
            </a>
        @endif

        @if(auth()->user()->tienePermiso('productos_maestros.crear'))
            <button
                type="button"
                class="ortomax-btn ortomax-btn-primary"
                onclick="abrirNuevoProductoMaestro()"
            >
                + Nuevo Producto Maestro
            </button>
        @endif

    </div>
</div>


<div class="ortomax-card">

    <form
        method="GET"
        action="{{ route('productos_maestros.index') }}"
        class="ortomax-search-form"
    >

        <div class="ortomax-search-group">

            <input
                type="text"
                name="q"
                value="{{ $busqueda }}"
                class="ortomax-input"
                placeholder="Buscar por código maestro o descripción..."
                style="width: 600px; min-width: 300px;"
            >

            <button
                type="submit"
                class="ortomax-btn ortomax-btn-secondary"
            >
                🔎 Buscar
            </button>

            @if($busqueda !== '')
                <a
                    href="{{ route('productos_maestros.index') }}"
                    class="ortomax-btn ortomax-btn-secondary"
                    style="text-decoration:none;"
                >
                    Limpiar
                </a>
            @endif

        </div>

    </form>


    <div class="ortomax-table-wrapper">

        <table class="ortomax-table">

            <thead>
                <tr>
                    <th>CODIGO MAESTRO</th>
                    <th>DESCRIPCIÓN</th>
                    <th>ESTADO</th>
                    <th class="ortomax-actions-header">ACCIONES</th>
                </tr>
            </thead>

            <tbody id="productosMaestrosTableBody">

                @forelse($productosMaestros as $productoMaestro)

                    <tr id="fila-producto-maestro-{{ $productoMaestro->id }}">

                        <td>
                            <strong>
                                {{ $productoMaestro->codigo_maestro }}
                            </strong>
                        </td>

                        <td>
                            {{ $productoMaestro->descripcion }}
                        </td>

                        <td>
                            @if($productoMaestro->estado)

                                <span class="ortomax-status ortomax-status-active">
                                    ACTIVO
                                </span>

                            @else

                                <span class="ortomax-status ortomax-status-inactive">
                                    INACTIVO
                                </span>

                            @endif
                        </td>

                        <td class="ortomax-actions-cell">

                            <div class="ortomax-actions">

                                @if(auth()->user()->tienePermiso('productos_maestros.ver'))

                                    <button
                                        type="button"
                                        class="ortomax-btn ortomax-btn-small ortomax-btn-info"
                                        onclick="verProductoMaestro({{ $productoMaestro->id }})"
                                        title="Ver"
                                    >
                                        👁️
                                    </button>

                                @endif


                                @if(auth()->user()->tienePermiso('productos_maestros.editar'))

                                    <button
                                        type="button"
                                        class="ortomax-btn ortomax-btn-small ortomax-btn-warning"
                                        onclick="editarProductoMaestro({{ $productoMaestro->id }})"
                                        title="Editar"
                                    >
                                        ✏️
                                    </button>

                                @endif


                                @if(auth()->user()->tienePermiso('productos_maestros.eliminar'))

                                    <button
                                        type="button"
                                        class="ortomax-btn ortomax-btn-small ortomax-btn-danger"
                                        onclick="eliminarProductoMaestro({{ $productoMaestro->id }})"
                                        title="Eliminar"
                                    >
                                        🗑️
                                    </button>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="4"
                            style="text-align:center; padding:30px;"
                        >
                            No existen productos maestros registrados.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    @if($productosMaestros->hasPages())

        <div style="margin-top:20px;">
            {{ $productosMaestros->links() }}
        </div>

    @endif

</div>


{{-- ========================================================= --}}
{{-- MODAL NUEVO / EDITAR --}}
{{-- ========================================================= --}}

<div
    id="modalProductoMaestro"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2 id="modalProductoMaestroTitulo">
                Nuevo Producto Maestro
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModalProductoMaestro()"
            >
                &times;
            </button>

        </div>


        <div class="ortomax-modal-body">

            <form id="formProductoMaestro">

                @csrf

                <input
                    type="hidden"
                    id="productoMaestroId"
                >


                <div class="ortomax-form-group">

                    <label for="codigo_maestro">
                        Código Maestro *
                    </label>

                    <input
                        type="text"
                        id="codigo_maestro"
                        name="codigo_maestro"
                        class="ortomax-input"
                        maxlength="50"
                        required
                    >

                    <div
                        id="error_codigo_maestro"
                        class="ortomax-form-error"
                    ></div>

                </div>


                <div class="ortomax-form-group">

                    <label for="descripcion">
                        Descripción *
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        class="ortomax-input"
                        maxlength="255"
                        rows="3"
                        required
                    ></textarea>

                    <div
                        id="error_descripcion"
                        class="ortomax-form-error"
                    ></div>

                </div>


                <div class="ortomax-form-group">

                    <label for="estado">
                        Estado *
                    </label>

                    <select
                        id="estado"
                        name="estado"
                        class="ortomax-input"
                        required
                    >
                        <option value="1">ACTIVO</option>
                        <option value="0">INACTIVO</option>
                    </select>

                    <div
                        id="error_estado"
                        class="ortomax-form-error"
                    ></div>

                </div>

            </form>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModalProductoMaestro()"
            >
                Cancelar
            </button>

            <button
                type="button"
                id="btnGuardarProductoMaestro"
                class="ortomax-btn ortomax-btn-primary"
                onclick="guardarProductoMaestro()"
            >
                Guardar
            </button>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- MODAL VER --}}
{{-- ========================================================= --}}

<div
    id="modalVerProductoMaestro"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Producto Maestro
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModalVerProductoMaestro()"
            >
                &times;
            </button>

        </div>


        <div class="ortomax-modal-body">

            <div class="ortomax-detail-grid">

                <div>
                    <strong>Código Maestro</strong>
                    <div id="verCodigoMaestro"></div>
                </div>

                <div>
                    <strong>Estado</strong>
                    <div id="verEstado"></div>
                </div>

                <div style="grid-column:1 / -1;">
                    <strong>Descripción</strong>
                    <div id="verDescripcion"></div>
                </div>

            </div>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModalVerProductoMaestro()"
            >
                Cerrar
            </button>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- MODAL ELIMINAR --}}
{{-- ========================================================= --}}

<div
    id="modalEliminarProductoMaestro"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Eliminar Producto Maestro
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModalEliminarProductoMaestro()"
            >
                &times;
            </button>

        </div>


        <div class="ortomax-modal-body">

            <p>
                ¿Está seguro de eliminar este producto maestro?
            </p>

            <p>
                <strong id="eliminarDescripcionProductoMaestro"></strong>
            </p>

            <input
                type="hidden"
                id="eliminarProductoMaestroId"
            >

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModalEliminarProductoMaestro()"
            >
                Cancelar
            </button>

            <button
                type="button"
                class="ortomax-btn ortomax-btn-danger"
                onclick="confirmarEliminarProductoMaestro()"
            >
                Eliminar
            </button>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- JAVASCRIPT PRINCIPAL --}}
{{-- ========================================================= --}}

<script>

const csrfTokenProductoMaestro =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '{{ csrf_token() }}';


function limpiarErroresProductoMaestro()
{
    document.querySelectorAll(
        '#formProductoMaestro .ortomax-form-error'
    ).forEach(function(elemento) {

        elemento.textContent = '';

    });
}


function abrirNuevoProductoMaestro()
{
    document.getElementById(
        'modalProductoMaestroTitulo'
    ).textContent = 'Nuevo Producto Maestro';

    document.getElementById(
        'productoMaestroId'
    ).value = '';

    document.getElementById(
        'codigo_maestro'
    ).value = '';

    document.getElementById(
        'descripcion'
    ).value = '';

    document.getElementById(
        'estado'
    ).value = '1';

    limpiarErroresProductoMaestro();

    document.getElementById(
        'modalProductoMaestro'
    ).style.display = 'flex';
}


function cerrarModalProductoMaestro()
{
    document.getElementById(
        'modalProductoMaestro'
    ).style.display = 'none';
}


function guardarProductoMaestro()
{
    limpiarErroresProductoMaestro();

    const id =
        document.getElementById(
            'productoMaestroId'
        ).value;

    const datos = {

        codigo_maestro:
            document.getElementById(
                'codigo_maestro'
            ).value.trim(),

        descripcion:
            document.getElementById(
                'descripcion'
            ).value.trim(),

        estado:
            document.getElementById(
                'estado'
            ).value

    };


    let url =
        '{{ route("productos_maestros.store") }}';

    let metodo = 'POST';


    if (id) {

        url =
            '/productos-maestros/' + id;

        metodo = 'PUT';

    }


    fetch(url, {

        method: metodo,

        headers: {

            'Content-Type': 'application/json',

            'Accept': 'application/json',

            'X-CSRF-TOKEN':
                csrfTokenProductoMaestro

        },

        body: JSON.stringify(datos)

    })

    .then(async response => {

        const data =
            await response.json();

        if (!response.ok) {

            throw {

                status: response.status,

                data: data

            };

        }

        return data;

    })

    .then(data => {

        if (data.ok) {

            cerrarModalProductoMaestro();

            window.location.reload();

        }

    })

    .catch(error => {

        if (

            error.status === 422 &&

            error.data &&

            error.data.errors

        ) {

            Object.keys(
                error.data.errors
            ).forEach(function(campo) {

                const elemento =
                    document.getElementById(
                        'error_' + campo
                    );

                if (elemento) {

                    elemento.textContent =
                        error.data.errors[campo][0];

                }

            });

            return;

        }


        console.error(error);


        alert(

            error.data?.message ||

            'Ocurrió un error al guardar el producto maestro.'

        );

    });
}


function verProductoMaestro(id)
{
    fetch(
        '/productos-maestros/' + id,
        {
            headers: {
                'Accept': 'application/json'
            }
        }
    )

    .then(response => response.json())

    .then(data => {

        if (!data.ok) {
            return;
        }

        const producto =
            data.producto_maestro;


        document.getElementById(
            'verCodigoMaestro'
        ).textContent =
            producto.codigo_maestro;


        document.getElementById(
            'verDescripcion'
        ).textContent =
            producto.descripcion;


        document.getElementById(
            'verEstado'
        ).innerHTML =

            producto.estado

                ? '<span class="ortomax-status ortomax-status-active">ACTIVO</span>'

                : '<span class="ortomax-status ortomax-status-inactive">INACTIVO</span>';


        document.getElementById(
            'modalVerProductoMaestro'
        ).style.display = 'flex';

    })

    .catch(error => {

        console.error(error);

        alert(
            'No fue posible consultar el producto maestro.'
        );

    });
}


function cerrarModalVerProductoMaestro()
{
    document.getElementById(
        'modalVerProductoMaestro'
    ).style.display = 'none';
}


function editarProductoMaestro(id)
{
    fetch(
        '/productos-maestros/' + id,
        {
            headers: {
                'Accept': 'application/json'
            }
        }
    )

    .then(response => response.json())

    .then(data => {

        if (!data.ok) {
            return;
        }

        const producto =
            data.producto_maestro;


        document.getElementById(
            'modalProductoMaestroTitulo'
        ).textContent =
            'Editar Producto Maestro';


        document.getElementById(
            'productoMaestroId'
        ).value =
            producto.id;


        document.getElementById(
            'codigo_maestro'
        ).value =
            producto.codigo_maestro;


        document.getElementById(
            'descripcion'
        ).value =
            producto.descripcion;


        document.getElementById(
            'estado'
        ).value =
            producto.estado ? '1' : '0';


        limpiarErroresProductoMaestro();


        document.getElementById(
            'modalProductoMaestro'
        ).style.display = 'flex';

    })

    .catch(error => {

        console.error(error);

        alert(
            'No fue posible cargar el producto maestro.'
        );

    });
}


function eliminarProductoMaestro(id)
{
    fetch(
        '/productos-maestros/' + id,
        {
            headers: {
                'Accept': 'application/json'
            }
        }
    )

    .then(response => response.json())

    .then(data => {

        if (!data.ok) {
            return;
        }

        const producto =
            data.producto_maestro;


        document.getElementById(
            'eliminarProductoMaestroId'
        ).value =
            producto.id;


        document.getElementById(
            'eliminarDescripcionProductoMaestro'
        ).textContent =

            producto.codigo_maestro +
            ' — ' +
            producto.descripcion;


        document.getElementById(
            'modalEliminarProductoMaestro'
        ).style.display = 'flex';

    })

    .catch(error => {

        console.error(error);

        alert(
            'No fue posible cargar el producto maestro.'
        );

    });
}


function cerrarModalEliminarProductoMaestro()
{
    document.getElementById(
        'modalEliminarProductoMaestro'
    ).style.display = 'none';
}


function confirmarEliminarProductoMaestro()
{
    const id =
        document.getElementById(
            'eliminarProductoMaestroId'
        ).value;


    fetch(
        '/productos-maestros/' + id,
        {
            method: 'DELETE',

            headers: {

                'Accept': 'application/json',

                'X-CSRF-TOKEN':
                    csrfTokenProductoMaestro

            }
        }
    )

    .then(async response => {

        const data =
            await response.json();

        if (!response.ok) {

            throw {

                status: response.status,

                data: data

            };

        }

        return data;

    })

    .then(data => {

        if (data.ok) {

            cerrarModalEliminarProductoMaestro();

            window.location.reload();

        }

    })

    .catch(error => {

        console.error(error);

        alert(

            error.data?.message ||

            'No fue posible eliminar el producto maestro.'

        );

    });
}


/*
|--------------------------------------------------------------------------
| Cerrar modales al hacer clic sobre el fondo
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'click',
    function(event)
    {

        const modalNuevo =
            document.getElementById(
                'modalProductoMaestro'
            );

        const modalVer =
            document.getElementById(
                'modalVerProductoMaestro'
            );

        const modalEliminar =
            document.getElementById(
                'modalEliminarProductoMaestro'
            );

        const modalImportar =
            document.getElementById(
                'modalImportarProductosMaestros'
            );


        if (event.target === modalNuevo) {

            cerrarModalProductoMaestro();

        }


        if (event.target === modalVer) {

            cerrarModalVerProductoMaestro();

        }


        if (event.target === modalEliminar) {

            cerrarModalEliminarProductoMaestro();

        }


        if (event.target === modalImportar) {

            cerrarImportarProductosMaestros();

        }

    }
);

</script>


{{-- ========================================================= --}}
{{-- MODAL IMPORTAR EXCEL --}}
{{-- ========================================================= --}}

<div
    id="modalImportarProductosMaestros"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Importar Productos Maestros
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarImportarProductosMaestros()"
            >
                &times;
            </button>

        </div>


        <div class="ortomax-modal-body">

            <p>
                Seleccione el archivo Excel que contiene los productos maestros.
            </p>

            <p style="font-size:13px; color:#666;">

                El archivo debe contener las columnas:

                <strong>
                    CODIGO_MAESTRO, DESCRIPCION, ESTADO
                </strong>

            </p>


            <div class="ortomax-form-group">

                <label for="archivoProductosMaestros">
                    Archivo Excel *
                </label>

                <input
                    type="file"
                    id="archivoProductosMaestros"
                    accept=".xlsx,.xls,.csv"
                    class="ortomax-input"
                >

                <div
                    id="error_archivo_productos_maestros"
                    class="ortomax-form-error"
                ></div>

            </div>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarImportarProductosMaestros()"
            >
                Cancelar
            </button>


            <button
                type="button"
                id="btnImportarProductosMaestros"
                class="ortomax-btn ortomax-btn-primary"
                onclick="importarProductosMaestros()"
            >
                📥 Importar
            </button>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- JAVASCRIPT IMPORTAR EXCEL --}}
{{-- ========================================================= --}}

<script>

function abrirImportarProductosMaestros()
{
    document.getElementById(
        'archivoProductosMaestros'
    ).value = '';


    document.getElementById(
        'error_archivo_productos_maestros'
    ).textContent = '';


    document.getElementById(
        'modalImportarProductosMaestros'
    ).style.display = 'flex';
}


function cerrarImportarProductosMaestros()
{
    document.getElementById(
        'modalImportarProductosMaestros'
    ).style.display = 'none';
}


function importarProductosMaestros()
{
    const archivo =
        document.getElementById(
            'archivoProductosMaestros'
        ).files[0];


    const error =
        document.getElementById(
            'error_archivo_productos_maestros'
        );


    error.textContent = '';


    if (!archivo) {

        error.textContent =
            'Debe seleccionar un archivo Excel.';

        return;

    }


    const formData =
        new FormData();

    formData.append(
        'archivo',
        archivo
    );


    const boton =
        document.getElementById(
            'btnImportarProductosMaestros'
        );


    boton.disabled = true;

    boton.textContent =
        'Importando...';


    fetch(
        '{{ route("productos_maestros.importar") }}',
        {
            method: 'POST',

            headers: {

                'Accept': 'application/json',

                'X-CSRF-TOKEN':
                    csrfTokenProductoMaestro

            },

            body: formData
        }
    )

    .then(async response => {

        const data =
            await response.json();


        if (!response.ok) {

            throw {

                status: response.status,

                data: data

            };

        }


        return data;

    })


    .then(data => {

        if (data.ok) {

            cerrarImportarProductosMaestros();

            window.location.reload();

        }

    })


    .catch(error => {

        console.error(error);


        if (

            error.status === 422 &&

            error.data &&

            error.data.errors

        ) {

            const errores =
                error.data.errors;


            if (errores.archivo) {

                document.getElementById(
                    'error_archivo_productos_maestros'
                ).textContent =
                    errores.archivo[0];

            }


            return;

        }


        alert(

            error.data?.message ||

            'Ocurrió un error al importar los productos maestros.'

        );

    })


    .finally(() => {

        boton.disabled = false;

        boton.textContent =
            '📥 Importar';

    });
}

</script>



@endsection