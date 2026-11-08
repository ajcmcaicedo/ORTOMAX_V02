@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')

<div class="ortomax-card">

    {{-- Encabezado --}}
    <div class="ortomax-module-header">

        <div>
            <h1 class="ortomax-title">
                Proveedores
            </h1>

            <p class="ortomax-subtitle">
                Administración de proveedores de ORTOMAX
            </p>
        </div>

        {{-- Boton Nuevo proveedor  - import y Export --}}


        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">

            @if(auth()->user()->tienePermiso('proveedores.importar'))
        
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="abrirImportarProveedores()"
                >
                    📥 Importar Excel
                </button>
        
            @endif
        
        
            @if(auth()->user()->tienePermiso('proveedores.exportar'))
        
                <a
                    href="{{ route('proveedores.exportar') }}"
                    class="ortomax-btn ortomax-btn-secondary"
                    style="text-decoration:none;"
                >
                    📤 Exportar Excel
                </a>
        
            @endif
        
        
            @if(auth()->user()->tienePermiso('proveedores.crear'))
        
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-primary"
                    onclick="abrirNuevoProveedor()"
                >
                    + Nuevo Proveedor
                </button>
        
            @endif
        
        </div>




    </div>


    {{-- Búsqueda --}}
    <div style="margin-bottom: 20px;">

        <form
            method="GET"
            action="{{ route('proveedores.index') }}"
            style="display:flex; gap:10px; align-items:center;"
        >

            <input
                type="text"
                name="q"
                value="{{ $busqueda }}"
                class="ortomax-form-control"
                placeholder="Buscar por código, nombre, RUC o sigla..."
                style="max-width:500px;"
            >

            <button
                type="submit"
                class="ortomax-btn ortomax-btn-secondary"
            >
                Buscar
            </button>

            @if($busqueda !== '')
                <a
                    href="{{ route('proveedores.index') }}"
                    class="ortomax-btn ortomax-btn-secondary"
                    style="text-decoration:none;"
                >
                    Limpiar
                </a>
            @endif

        </form>

    </div>


    {{-- Tabla --}}
    <div class="ortomax-table-container">

        <table class="ortomax-table">

            <thead>
                <tr>

                    <th>Código</th>
                    <th>Nombre</th>
                    <th>RUC</th>
                    <th>Sigla</th>
                    <th>Estado</th>

                    <th style="width:180px;">
                        Acciones
                    </th>

                </tr>
            </thead>


            <tbody>

                @forelse($proveedores as $proveedor)

                    <tr data-proveedor-id="{{ $proveedor->id }}">

                        <td>
                            <strong>
                                {{ $proveedor->codigo }}
                            </strong>
                        </td>

                        <td>
                            {{ $proveedor->nombre }}
                        </td>

                        <td>
                            {{ $proveedor->ruc ?? '—' }}
                        </td>

                        <td>
                            {{ $proveedor->sigla }}
                        </td>

                        <td>

                            @if($proveedor->estado)

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

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-secondary"
                                    onclick="verProveedor({{ $proveedor->id }})"
                                >
                                    Ver
                                </button>

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-warning"
                                    onclick="editarProveedor({{ $proveedor->id }})"
                                >
                                    Editar
                                </button>

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-danger"
                                    onclick="eliminarProveedor({{ $proveedor->id }})"
                                >
                                    Eliminar
                                </button>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            style="text-align:center; padding:40px;"
                        >
                            No existen proveedores registrados.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- Paginación --}}
    @if($proveedores->hasPages())

        <div style="margin-top:20px;">
            {{ $proveedores->links() }}
        </div>

    @endif

</div>


{{-- ========================================================= --}}
{{-- Modal Nuevo Proveedor --}}
{{-- ========================================================= --}}

<div
    id="modalNuevoProveedor"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Nuevo Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarNuevoProveedor()"
            >
                ×
            </button>

        </div>


        <form id="formNuevoProveedor">

            <div class="ortomax-modal-body">

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Código *
                    </label>

                    <input
                        type="text"
                        id="nuevoProveedorCodigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="50"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        id="nuevoProveedorNombre"
                        name="nombre"
                        class="ortomax-form-control"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        RUC
                    </label>

                    <input
                        type="text"
                        id="nuevoProveedorRuc"
                        name="ruc"
                        class="ortomax-form-control"
                        maxlength="20"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Sigla *
                    </label>

                    <input
                        type="text"
                        id="nuevoProveedorSigla"
                        name="sigla"
                        class="ortomax-form-control"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">

                        <input
                            type="checkbox"
                            id="nuevoProveedorEstado"
                            name="estado"
                            value="1"
                            checked
                        >

                        Proveedor activo

                    </label>

                </div>

            </div>


            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarNuevoProveedor()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Guardar Proveedor
                </button>

            </div>

        </form>

    </div>

</div>


{{-- ========================================================= --}}
{{-- Modal Editar Proveedor --}}
{{-- ========================================================= --}}

<div
    id="modalEditarProveedor"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Editar Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarEditarProveedor()"
            >
                ×
            </button>

        </div>


        <form id="formEditarProveedor">

            <div class="ortomax-modal-body">

                <input
                    type="hidden"
                    id="editarProveedorId"
                >


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Código *
                    </label>

                    <input
                        type="text"
                        id="editarProveedorCodigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="50"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        id="editarProveedorNombre"
                        name="nombre"
                        class="ortomax-form-control"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        RUC
                    </label>

                    <input
                        type="text"
                        id="editarProveedorRuc"
                        name="ruc"
                        class="ortomax-form-control"
                        maxlength="20"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Sigla *
                    </label>

                    <input
                        type="text"
                        id="editarProveedorSigla"
                        name="sigla"
                        class="ortomax-form-control"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">

                        <input
                            type="checkbox"
                            id="editarProveedorEstado"
                            name="estado"
                            value="1"
                        >

                        Proveedor activo

                    </label>

                </div>

            </div>


            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarEditarProveedor()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Guardar Cambios
                </button>

            </div>

        </form>

    </div>

</div>


{{-- ========================================================= --}}
{{-- Modal Importar Proveedores --}}
{{-- ========================================================= --}}

@if(auth()->user()->tienePermiso('proveedores.importar'))

<div
    id="modalImportarProveedores"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Importar Proveedores desde Excel
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarImportarProveedores()"
            >
                ×
            </button>

        </div>


        <form
            id="formImportarProveedores"
            enctype="multipart/form-data"
        >

            <div class="ortomax-modal-body">

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Seleccione el archivo Excel
                    </label>

                    <input
                        type="file"
                        id="archivoProveedores"
                        name="archivo"
                        class="ortomax-form-control"
                        accept=".xlsx,.xls,.csv"
                        required
                    >

                </div>


                <div
                    id="errorImportarProveedores"
                    class="ortomax-alert ortomax-alert-danger"
                    style="display:none;"
                ></div>


                <p style="margin-top:15px; color:#666;">
                    El archivo debe contener las columnas:
                    <strong>
                        CODIGO, NOMBRE, RUC, SIGLA y ESTADO
                    </strong>.
                </p>

            </div>


            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarImportarProveedores()"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                    id="btnImportarProveedores"
                >
                    Importar
                </button>

            </div>

        </form>

    </div>

</div>

@endif





{{-- ========================================================= --}}
{{-- Modal Eliminar Proveedor --}}
{{-- ========================================================= --}}

<div
    id="modalEliminarProveedor"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Eliminar Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarEliminarProveedor()"
            >
                ×
            </button>

        </div>


        <div class="ortomax-modal-body">

            <p>
                ¿Está seguro de eliminar este proveedor?
            </p>

            <p>
                <strong id="eliminarProveedorNombre"></strong>
            </p>

            <p style="color:#DC3545;">
                Esta acción no se puede deshacer.
            </p>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarEliminarProveedor()"
            >
                Cancelar
            </button>

            <button
                type="button"
                class="ortomax-btn ortomax-btn-danger"
                id="btnConfirmarEliminarProveedor"
            >
                Eliminar
            </button>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- Modal Ver Proveedor --}}
{{-- ========================================================= --}}

<div
    id="modalVerProveedor"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Información del Proveedor
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModalProveedor()"
            >
                ×
            </button>

        </div>


        <div class="ortomax-modal-body">

            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Código
                </label>

                <input
                    type="text"
                    id="verProveedorCodigo"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Nombre
                </label>

                <input
                    type="text"
                    id="verProveedorNombre"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    RUC
                </label>

                <input
                    type="text"
                    id="verProveedorRuc"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Sigla
                </label>

                <input
                    type="text"
                    id="verProveedorSigla"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Estado
                </label>

                <input
                    type="text"
                    id="verProveedorEstado"
                    class="ortomax-form-control"
                    readonly
                >

            </div>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModalProveedor()"
            >
                Cerrar
            </button>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- AJAX --}}
{{-- ========================================================= --}}

<script>

    /*
    |--------------------------------------------------------------------------
    | Datos de Proveedores
    |--------------------------------------------------------------------------
    */

    window.proveedoresData = @json($proveedores->values());


/*
|--------------------------------------------------------------------------
| Importar Proveedores
|--------------------------------------------------------------------------
*/

function abrirImportarProveedores()
{
    document.getElementById(
        'formImportarProveedores'
    ).reset();


    const error =
        document.getElementById(
            'errorImportarProveedores'
        );


    if (error) {

        error.style.display = 'none';

        error.textContent = '';

    }


    document.getElementById(
        'modalImportarProveedores'
    ).style.display = 'flex';
}


function cerrarImportarProveedores()
{
    document.getElementById(
        'modalImportarProveedores'
    ).style.display = 'none';


    document.getElementById(
        'formImportarProveedores'
    ).reset();


    const error =
        document.getElementById(
            'errorImportarProveedores'
        );


    if (error) {

        error.style.display = 'none';

        error.textContent = '';

    }
}


document.getElementById(
    'formImportarProveedores'
).addEventListener(
    'submit',
    async function(event) {

        event.preventDefault();


        const form = event.target;

        const archivo =
            document.getElementById(
                'archivoProveedores'
            );


        const error =
            document.getElementById(
                'errorImportarProveedores'
            );


        const boton =
            document.getElementById(
                'btnImportarProveedores'
            );


        if (!archivo.files.length) {

            error.textContent =
                'Seleccione un archivo Excel.';

            error.style.display = 'block';

            return;
        }


        const datos =
            new FormData(form);


        boton.disabled = true;

        boton.textContent = 'Importando...';


        error.style.display = 'none';


        try {

            const response =
                await fetch(
                    '/proveedores/importar',
                    {
                        method: 'POST',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .getAttribute(
                                        'content'
                                    )
                        },

                        body: datos
                    }
                );


            const data =
                await response.json();


            if (!response.ok || !data.ok) {

                if (data.errors) {

                    const mensajes =
                        Object.values(
                            data.errors
                        )
                        .flat()
                        .join('\n');


                    error.textContent =
                        mensajes;

                } else {

                    error.textContent =
                        data.message ??
                        'No fue posible importar los proveedores.';

                }


                error.style.display =
                    'block';

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Importación correcta
            |--------------------------------------------------------------------------
            |
            | Recargamos la página para que la tabla
            | muestre inmediatamente los datos importados.
            |
            */

            cerrarImportarProveedores();

            window.location.reload();


        } catch (exception) {

            console.error(exception);


            error.textContent =
                'Ocurrió un error al importar el archivo.';


            error.style.display =
                'block';

        } finally {

            boton.disabled = false;

            boton.textContent =
                'Importar';

        }

    }
);




    /*
    |--------------------------------------------------------------------------
    | Ver Proveedor
    |--------------------------------------------------------------------------
    */

    function verProveedor(id)
    {
        const proveedor = window.proveedoresData.find(
            item => Number(item.id) === Number(id)
        );

        if (!proveedor) {
            return;
        }

        document.getElementById('verProveedorCodigo').value =
            proveedor.codigo ?? '';

        document.getElementById('verProveedorNombre').value =
            proveedor.nombre ?? '';

        document.getElementById('verProveedorRuc').value =
            proveedor.ruc ?? '';

        document.getElementById('verProveedorSigla').value =
            proveedor.sigla ?? '';

        document.getElementById('verProveedorEstado').value =
            proveedor.estado
                ? 'ACTIVO'
                : 'INACTIVO';

        document.getElementById('modalVerProveedor').style.display =
            'flex';
    }


    /*
    |--------------------------------------------------------------------------
    | Cerrar modal Ver
    |--------------------------------------------------------------------------
    */

    function cerrarModalProveedor()
    {
        document.getElementById(
            'modalVerProveedor'
        ).style.display = 'none';
    }


    /*
    |--------------------------------------------------------------------------
    | Clic fuera del modal Ver
    |--------------------------------------------------------------------------
    */

    document.getElementById('modalVerProveedor')
        .addEventListener('click', function(event) {

            if (event.target === this) {
                cerrarModalProveedor();
            }

        });


    /*
    |--------------------------------------------------------------------------
    | Nuevo Proveedor
    |--------------------------------------------------------------------------
    */

    function abrirNuevoProveedor()
    {
        document.getElementById(
            'formNuevoProveedor'
        ).reset();

        document.getElementById(
            'nuevoProveedorEstado'
        ).checked = true;

        document.getElementById(
            'modalNuevoProveedor'
        ).style.display = 'flex';
    }


    function cerrarNuevoProveedor()
    {
        document.getElementById(
            'modalNuevoProveedor'
        ).style.display = 'none';

        document.getElementById(
            'formNuevoProveedor'
        ).reset();

        document.getElementById(
            'nuevoProveedorEstado'
        ).checked = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Guardar Nuevo Proveedor
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'formNuevoProveedor'
    ).addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();

            const form = event.target;

            const datos = new FormData(form);

            /*
            |--------------------------------------------------------------
            | Checkbox
            |--------------------------------------------------------------
            */

            if (!document.getElementById(
                'nuevoProveedorEstado'
            ).checked) {

                datos.set('estado', '0');

            }


            try {

                const response = await fetch(
                    '/proveedores',
                    {
                        method: 'POST',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .getAttribute(
                                        'content'
                                    )
                        },

                        body: datos
                    }
                );


                const data =
                    await response.json();


                if (!response.ok) {

                    if (data.errors) {

                        const mensajes =
                            Object.values(
                                data.errors
                            )
                            .flat()
                            .join('\n');

                        alert(mensajes);

                    } else {

                        alert(
                            data.message ??
                            'No fue posible crear el proveedor.'
                        );

                    }

                    return;
                }


                /*
                |----------------------------------------------------------
                | Agregar a los datos locales
                |----------------------------------------------------------
                */

                window.proveedoresData.push(
                    data.proveedor
                );


                /*
                |----------------------------------------------------------
                | Agregar fila a la tabla
                |----------------------------------------------------------
                */

                const tbody =
                    document.querySelector(
                        '.ortomax-table tbody'
                    );


                const filaVacia =
                    tbody.querySelector(
                        'td[colspan="6"]'
                    );


                if (filaVacia) {
                    tbody.innerHTML = '';
                }


                const tr =
                    document.createElement('tr');


                tr.setAttribute(
                    'data-proveedor-id',
                    data.proveedor.id
                );


                tr.innerHTML = `

                    <td>
                        <strong>
                            ${data.proveedor.codigo}
                        </strong>
                    </td>

                    <td>
                        ${data.proveedor.nombre}
                    </td>

                    <td>
                        ${data.proveedor.ruc ?? '—'}
                    </td>

                    <td>
                        ${data.proveedor.sigla}
                    </td>

                    <td>
                        ${
                            data.proveedor.estado
                                ? '<span class="ortomax-status ortomax-status-active">ACTIVO</span>'
                                : '<span class="ortomax-status ortomax-status-inactive">INACTIVO</span>'
                        }
                    </td>

                    <td class="ortomax-actions-cell">

                        <div class="ortomax-actions">

                            <button
                                type="button"
                                class="ortomax-btn ortomax-btn-secondary"
                                onclick="verProveedor(${data.proveedor.id})"
                            >
                                Ver
                            </button>

                            <button
                                type="button"
                                class="ortomax-btn ortomax-btn-warning"
                                onclick="editarProveedor(${data.proveedor.id})"
                            >
                                Editar
                            </button>

                            <button
                                type="button"
                                class="ortomax-btn ortomax-btn-danger"
                                onclick="eliminarProveedor(${data.proveedor.id})"
                            >
                                Eliminar
                            </button>

                        </div>

                    </td>
                `;


                tbody.appendChild(tr);


                cerrarNuevoProveedor();


            } catch (error) {

                console.error(error);

                alert(
                    'Ocurrió un error al guardar el proveedor.'
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Editar Proveedor
    |--------------------------------------------------------------------------
    */

    function editarProveedor(id)
    {
        const proveedor =
            window.proveedoresData.find(
                item =>
                    Number(item.id) ===
                    Number(id)
            );


        if (!proveedor) {
            return;
        }


        document.getElementById(
            'editarProveedorId'
        ).value = proveedor.id;


        document.getElementById(
            'editarProveedorCodigo'
        ).value =
            proveedor.codigo ?? '';


        document.getElementById(
            'editarProveedorNombre'
        ).value =
            proveedor.nombre ?? '';


        document.getElementById(
            'editarProveedorRuc'
        ).value =
            proveedor.ruc ?? '';


        document.getElementById(
            'editarProveedorSigla'
        ).value =
            proveedor.sigla ?? '';


        document.getElementById(
            'editarProveedorEstado'
        ).checked =
            Boolean(proveedor.estado);


        document.getElementById(
            'modalEditarProveedor'
        ).style.display = 'flex';
    }


    function cerrarEditarProveedor()
    {
        document.getElementById(
            'modalEditarProveedor'
        ).style.display = 'none';
    }


    /*
    |--------------------------------------------------------------------------
    | Guardar cambios
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'formEditarProveedor'
    ).addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();


            const id =
                document.getElementById(
                    'editarProveedorId'
                ).value;


            const form =
                event.target;


            const datos =
                new FormData(form);


            datos.append(
                '_method',
                'PUT'
            );


            if (!document.getElementById(
                'editarProveedorEstado'
            ).checked) {

                datos.set('estado', '0');

            }


            try {

                const response =
                    await fetch(
                        `/proveedores/${id}`,
                        {
                            method: 'POST',

                            headers: {

                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute(
                                            'content'
                                        )
                            },

                            body: datos
                        }
                    );


                const data =
                    await response.json();


                if (!response.ok) {

                    if (data.errors) {

                        const mensajes =
                            Object.values(
                                data.errors
                            )
                            .flat()
                            .join('\n');

                        alert(mensajes);

                    } else {

                        alert(
                            data.message ??
                            'No fue posible actualizar el proveedor.'
                        );

                    }

                    return;
                }


                /*
                |----------------------------------------------------------
                | Actualizar datos locales
                |----------------------------------------------------------
                */

                const indice =
                    window.proveedoresData.findIndex(
                        item =>
                            Number(item.id) ===
                            Number(id)
                    );


                if (indice !== -1) {

                    window.proveedoresData[indice] =
                        data.proveedor;

                }


                /*
                |----------------------------------------------------------
                | Actualizar fila
                |----------------------------------------------------------
                */

                const fila =
                    document.querySelector(
                        `tr[data-proveedor-id="${id}"]`
                    );


                if (fila) {

                    fila.innerHTML = `

                        <td>
                            <strong>
                                ${data.proveedor.codigo}
                            </strong>
                        </td>

                        <td>
                            ${data.proveedor.nombre}
                        </td>

                        <td>
                            ${data.proveedor.ruc ?? '—'}
                        </td>

                        <td>
                            ${data.proveedor.sigla}
                        </td>

                        <td>
                            ${
                                data.proveedor.estado
                                    ? '<span class="ortomax-status ortomax-status-active">ACTIVO</span>'
                                    : '<span class="ortomax-status ortomax-status-inactive">INACTIVO</span>'
                            }
                        </td>

                        <td class="ortomax-actions-cell">

                            <div class="ortomax-actions">

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-secondary"
                                    onclick="verProveedor(${data.proveedor.id})"
                                >
                                    Ver
                                </button>

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-warning"
                                    onclick="editarProveedor(${data.proveedor.id})"
                                >
                                    Editar
                                </button>

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-danger"
                                    onclick="eliminarProveedor(${data.proveedor.id})"
                                >
                                    Eliminar
                                </button>

                            </div>

                        </td>

                    `;

                }


                cerrarEditarProveedor();


            } catch (error) {

                console.error(error);

                alert(
                    'Ocurrió un error al actualizar el proveedor.'
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Eliminar Proveedor
    |--------------------------------------------------------------------------
    */

    let proveedorEliminarId = null;


    function eliminarProveedor(id)
    {
        const proveedor =
            window.proveedoresData.find(
                item =>
                    Number(item.id) ===
                    Number(id)
            );


        if (!proveedor) {
            return;
        }


        proveedorEliminarId = id;


        document.getElementById(
            'eliminarProveedorNombre'
        ).textContent =
            `${proveedor.codigo} - ${proveedor.nombre}`;


        document.getElementById(
            'modalEliminarProveedor'
        ).style.display = 'flex';
    }


    function cerrarEliminarProveedor()
    {
        proveedorEliminarId = null;


        document.getElementById(
            'modalEliminarProveedor'
        ).style.display = 'none';
    }


    /*
    |--------------------------------------------------------------------------
    | Confirmar eliminación
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'btnConfirmarEliminarProveedor'
    ).addEventListener(
        'click',
        async function() {

            if (!proveedorEliminarId) {
                return;
            }


            const id =
                proveedorEliminarId;


            try {

                const response =
                    await fetch(
                        `/proveedores/${id}`,
                        {
                            method: 'DELETE',

                            headers: {

                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute(
                                            'content'
                                        )
                            }
                        }
                    );


                const data =
                    await response.json();


                if (!response.ok || !data.ok) {

                    alert(
                        data.message ??
                        'No fue posible eliminar el proveedor.'
                    );

                    return;
                }


                /*
                |----------------------------------------------------------
                | Eliminar del arreglo local
                |----------------------------------------------------------
                */

                window.proveedoresData =
                    window.proveedoresData.filter(
                        item =>
                            Number(item.id) !==
                            Number(id)
                    );


                /*
                |----------------------------------------------------------
                | Eliminar fila
                |----------------------------------------------------------
                */

                const fila =
                    document.querySelector(
                        `tr[data-proveedor-id="${id}"]`
                    );


                if (fila) {
                    fila.remove();
                }


                /*
                |----------------------------------------------------------
                | Si no quedan proveedores
                |----------------------------------------------------------
                */

                const tbody =
                    document.querySelector(
                        '.ortomax-table tbody'
                    );


                if (
                    tbody &&
                    tbody.children.length === 0
                ) {

                    tbody.innerHTML = `

                        <tr>

                            <td
                                colspan="6"
                                style="text-align:center; padding:40px;"
                            >
                                No existen proveedores registrados.
                            </td>

                        </tr>

                    `;

                }


                cerrarEliminarProveedor();


            } catch (error) {

                console.error(error);

                alert(
                    'Ocurrió un error al eliminar el proveedor.'
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Cerrar modales al hacer clic fuera
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'modalNuevoProveedor'
    ).addEventListener(
        'click',
        function(event) {

            if (event.target === this) {
                cerrarNuevoProveedor();
            }

        }
    );


    document.getElementById(
        'modalEditarProveedor'
    ).addEventListener(
        'click',
        function(event) {

            if (event.target === this) {
                cerrarEditarProveedor();
            }

        }
    );


    document.getElementById(
        'modalEliminarProveedor'
    ).addEventListener(
        'click',
        function(event) {

            if (event.target === this) {
                cerrarEliminarProveedor();
            }

        }
    );

   /*
    |--------------------------------------------------------------------------
    | Cerrar modales - modalImportarProveedores al hacer clic fuera
    |--------------------------------------------------------------------------
    */

    document.getElementById(
    'modalImportarProveedores'
).addEventListener(
    'click',
    function(event) {

        if (event.target === this) {

            cerrarImportarProveedores();

        }

    }
);




</script>

@endsection


