@extends('layouts.app')

@section('title', 'Regionales')

@section('content')


<div class="ortomax-card">


    <div class="ortomax-module-header">

        <div>

            <h1 class="ortomax-title">
                Regionales
            </h1>

            <p class="ortomax-subtitle">
                Gestión de regionales de ORTOMAX
            </p>

        </div>


        @if(auth()->user()->tienePermiso('regionales.crear'))

            <button
                type="button"
                id="btnNuevaRegional"
                class="ortomax-btn ortomax-btn-primary"
            >
                + Nueva Regional
            </button>

        @endif

    </div>


    <div class="ortomax-table-container">

        <table class="ortomax-table">

            <thead>

                <tr>

                    <th>Código</th>

                    <th>Nombre</th>

                    <th>Descripción</th>

                    <th>Estado</th>

                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody id="tablaRegionalesBody">


                @forelse($regionales as $regional)


                    <tr
                        id="regional-row-{{ $regional->id }}"
                        data-id="{{ $regional->id }}"
                    >

                        <td>
                            {{ $regional->codigo }}
                        </td>


                        <td>
                            {{ $regional->nombre }}
                        </td>


                        <td>
                            {{ $regional->descripcion ?? '-' }}
                        </td>


                        <td>

                            @if($regional->activo)

                                <span class="ortomax-status ortomax-status-success">
                                    ACTIVO
                                </span>

                            @else

                                <span class="ortomax-status ortomax-status-danger">
                                    INACTIVO
                                </span>

                            @endif

                        </td>


                        <td>


                            @if(auth()->user()->tienePermiso('regionales.ver'))

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-secondary btn-ver-regional"
                                    data-id="{{ $regional->id }}"
                                >
                                    Ver
                                </button>

                            @endif


                            @if(auth()->user()->tienePermiso('regionales.editar'))

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-warning btn-editar-regional"
                                    data-id="{{ $regional->id }}"
                                >
                                    Editar
                                </button>

                            @endif


                            @if(auth()->user()->tienePermiso('regionales.eliminar'))

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-danger btn-eliminar-regional"
                                    data-id="{{ $regional->id }}"
                                >
                                    Eliminar
                                </button>

                            @endif


                        </td>

                    </tr>


                @empty


                    <tr id="filaSinRegionales">

                        <td
                            colspan="5"
                            style="text-align:center;"
                        >
                            No existen regionales registradas.
                        </td>

                    </tr>


                @endforelse


            </tbody>

        </table>

    </div>

</div>



{{-- ==========================================================
     MODAL NUEVA REGIONAL
     ========================================================== --}}

@if(auth()->user()->tienePermiso('regionales.crear'))


<div
    id="modalNuevaRegional"
    class="ortomax-modal"
>


    <div class="ortomax-modal-content">


        <div class="ortomax-modal-header">

            <h2 class="ortomax-modal-title">
                Nueva Regional
            </h2>


            <button
                type="button"
                class="ortomax-modal-close"
                data-cerrar-modal="modalNuevaRegional"
                aria-label="Cerrar"
            >
                &times;
            </button>

        </div>


        <form
            id="formNuevaRegional"
            action="{{ route('regionales.store') }}"
            method="POST"
        >

            @csrf


            <div class="ortomax-modal-body">


                <div
                    id="errorNuevaRegional"
                    class="ortomax-alert ortomax-alert-danger"
                    style="display:none;"
                ></div>


                <div class="ortomax-form-group">

                    <label
                        for="codigo"
                        class="ortomax-form-label"
                    >
                        Código *
                    </label>


                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label
                        for="nombre"
                        class="ortomax-form-label"
                    >
                        Nombre *
                    </label>


                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="ortomax-form-control"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label
                        for="descripcion"
                        class="ortomax-form-label"
                    >
                        Descripción
                    </label>


                    <textarea
                        id="descripcion"
                        name="descripcion"
                        class="ortomax-form-control"
                        maxlength="500"
                        rows="3"
                    ></textarea>

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">

                        <input
                            type="checkbox"
                            name="activo"
                            value="1"
                            checked
                        >

                        Regional activa

                    </label>

                </div>


            </div>


            <div class="ortomax-modal-footer">


                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    data-cerrar-modal="modalNuevaRegional"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Guardar Regional
                </button>


            </div>


        </form>


    </div>

</div>


@endif



{{-- ==========================================================
     MODAL VER REGIONAL
     ========================================================== --}}

@if(auth()->user()->tienePermiso('regionales.ver'))


<div
    id="modalVerRegional"
    class="ortomax-modal"
>


    <div class="ortomax-modal-content">


        <div class="ortomax-modal-header">

            <h2 class="ortomax-modal-title">
                Información de Regional
            </h2>


            <button
                type="button"
                class="ortomax-modal-close"
                data-cerrar-modal="modalVerRegional"
                aria-label="Cerrar"
            >
                &times;
            </button>

        </div>


        <div class="ortomax-modal-body">


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Código
                </label>


                <input
                    type="text"
                    id="verRegionalCodigo"
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
                    id="verRegionalNombre"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Descripción
                </label>


                <textarea
                    id="verRegionalDescripcion"
                    class="ortomax-form-control"
                    rows="3"
                    readonly
                ></textarea>

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Estado
                </label>


                <div id="verRegionalEstado"></div>

            </div>


        </div>


        <div class="ortomax-modal-footer">


            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                data-cerrar-modal="modalVerRegional"
            >
                Cerrar
            </button>


        </div>


    </div>

</div>


@endif



{{-- ==========================================================
     MODAL EDITAR REGIONAL
     ========================================================== --}}

@if(auth()->user()->tienePermiso('regionales.editar'))


<div
    id="modalEditarRegional"
    class="ortomax-modal"
>


    <div class="ortomax-modal-content">


        <div class="ortomax-modal-header">

            <h2 class="ortomax-modal-title">
                Editar Regional
            </h2>


            <button
                type="button"
                class="ortomax-modal-close"
                data-cerrar-modal="modalEditarRegional"
                aria-label="Cerrar"
            >
                &times;
            </button>

        </div>


        <form
            id="formEditarRegional"
            method="POST"
        >

            @csrf

            @method('PUT')


            <div class="ortomax-modal-body">


                <div
                    id="errorEditarRegional"
                    class="ortomax-alert ortomax-alert-danger"
                    style="display:none;"
                ></div>


                <input
                    type="hidden"
                    id="editarRegionalId"
                >


                <div class="ortomax-form-group">

                    <label
                        for="editarRegionalCodigo"
                        class="ortomax-form-label"
                    >
                        Código *
                    </label>


                    <input
                        type="text"
                        id="editarRegionalCodigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label
                        for="editarRegionalNombre"
                        class="ortomax-form-label"
                    >
                        Nombre *
                    </label>


                    <input
                        type="text"
                        id="editarRegionalNombre"
                        name="nombre"
                        class="ortomax-form-control"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label
                        for="editarRegionalDescripcion"
                        class="ortomax-form-label"
                    >
                        Descripción
                    </label>


                    <textarea
                        id="editarRegionalDescripcion"
                        name="descripcion"
                        class="ortomax-form-control"
                        maxlength="500"
                        rows="3"
                    ></textarea>

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">

                        <input
                            type="checkbox"
                            id="editarRegionalActivo"
                            name="activo"
                            value="1"
                        >

                        Regional activa

                    </label>

                </div>


            </div>


            <div class="ortomax-modal-footer">


                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    data-cerrar-modal="modalEditarRegional"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-warning"
                >
                    Guardar cambios
                </button>


            </div>


        </form>


    </div>

</div>


@endif



{{-- ==========================================================
     MODAL ELIMINAR REGIONAL
     ========================================================== --}}

@if(auth()->user()->tienePermiso('regionales.eliminar'))


<div
    id="modalEliminarRegional"
    class="ortomax-modal"
>


    <div class="ortomax-modal-content">


        <div class="ortomax-modal-header">

            <h2 class="ortomax-modal-title">
                Eliminar Regional
            </h2>


            <button
                type="button"
                class="ortomax-modal-close"
                data-cerrar-modal="modalEliminarRegional"
                aria-label="Cerrar"
            >
                &times;
            </button>

        </div>


        <div class="ortomax-modal-body">


            <p>

                ¿Está seguro de eliminar la regional

                <strong id="nombreRegionalEliminar"></strong>?

            </p>


            <div
                id="errorEliminarRegional"
                class="ortomax-alert ortomax-alert-danger"
                style="display:none;"
            ></div>


        </div>


        <div class="ortomax-modal-footer">


            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                data-cerrar-modal="modalEliminarRegional"
            >
                Cancelar
            </button>


            <button
                type="button"
                id="btnConfirmarEliminar"
                class="ortomax-btn ortomax-btn-danger"
            >
                Eliminar
            </button>


        </div>


    </div>

</div>


@endif



<script>

    /*
    |--------------------------------------------------------------------------
    | DATOS INICIALES DE LAS REGIONALES
    |--------------------------------------------------------------------------
    */

    window.regionalesData = @json($regionales->values());


    /*
    |--------------------------------------------------------------------------
    | OBTENER REGIONAL
    |--------------------------------------------------------------------------
    */

    function obtenerRegional(id)
    {

        return window.regionalesData.find(
            function(regional)
            {
                return Number(regional.id) === Number(id);
            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | NUEVA REGIONAL
    |--------------------------------------------------------------------------
    */

    const btnNuevaRegional =
        document.getElementById(
            'btnNuevaRegional'
        );


    if (btnNuevaRegional) {

        btnNuevaRegional.addEventListener(
            'click',
            function()
            {

                abrirModal(
                    'modalNuevaRegional'
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | BOTONES PARA CERRAR MODALES
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function(event)
        {

            const boton =
                event.target.closest(
                    '[data-cerrar-modal]'
                );


            if (!boton) {
                return;
            }


            cerrarModal(
                boton.dataset.cerrarModal
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | VER REGIONAL
    |--------------------------------------------------------------------------
    */

    function mostrarRegional(id)
    {

        const regional =
            obtenerRegional(id);


        if (!regional) {

            console.error(
                'Regional no encontrada:',
                id
            );

            return;

        }


        document.getElementById(
            'verRegionalCodigo'
        ).value =
            regional.codigo ?? '';


        document.getElementById(
            'verRegionalNombre'
        ).value =
            regional.nombre ?? '';


        document.getElementById(
            'verRegionalDescripcion'
        ).value =
            regional.descripcion ?? '';


        const estado =
            document.getElementById(
                'verRegionalEstado'
            );


        if (regional.activo) {

            estado.innerHTML =
                '<span class="ortomax-status ortomax-status-success">ACTIVO</span>';

        }
        else {

            estado.innerHTML =
                '<span class="ortomax-status ortomax-status-danger">INACTIVO</span>';

        }


        abrirModal(
            'modalVerRegional'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR REGIONAL
    |--------------------------------------------------------------------------
    */

    function prepararEdicionRegional(id)
    {

        const regional =
            obtenerRegional(id);


        if (!regional) {

            console.error(
                'Regional no encontrada:',
                id
            );

            return;

        }


        document.getElementById(
            'editarRegionalId'
        ).value =
            regional.id;


        document.getElementById(
            'editarRegionalCodigo'
        ).value =
            regional.codigo ?? '';


        document.getElementById(
            'editarRegionalNombre'
        ).value =
            regional.nombre ?? '';


        document.getElementById(
            'editarRegionalDescripcion'
        ).value =
            regional.descripcion ?? '';


        document.getElementById(
            'editarRegionalActivo'
        ).checked =
            !!regional.activo;


        document.getElementById(
            'formEditarRegional'
        ).action =
            `/regionales/${regional.id}`;


        const error =
            document.getElementById(
                'errorEditarRegional'
            );


        if (error) {

            error.style.display =
                'none';

            error.textContent =
                '';

        }


        abrirModal(
            'modalEditarRegional'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR REGIONAL
    |--------------------------------------------------------------------------
    */

    let regionalEliminarId =
        null;


    function prepararEliminacionRegional(id)
    {

        const regional =
            obtenerRegional(id);


        if (!regional) {

            console.error(
                'Regional no encontrada:',
                id
            );

            return;

        }


        regionalEliminarId =
            regional.id;


        document.getElementById(
            'nombreRegionalEliminar'
        ).textContent =
            regional.nombre;


        const error =
            document.getElementById(
                'errorEliminarRegional'
            );


        if (error) {

            error.style.display =
                'none';

            error.textContent =
                '';

        }


        const boton =
            document.getElementById(
                'btnConfirmarEliminar'
            );


        if (boton) {

            boton.disabled =
                false;

            boton.textContent =
                'Eliminar';

        }


        abrirModal(
            'modalEliminarRegional'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | EVENTOS DE LOS BOTONES DE LA TABLA
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function(event)
        {

            const botonVer =
                event.target.closest(
                    '.btn-ver-regional'
                );


            if (botonVer) {

                mostrarRegional(
                    botonVer.dataset.id
                );

                return;

            }


            const botonEditar =
                event.target.closest(
                    '.btn-editar-regional'
                );


            if (botonEditar) {

                prepararEdicionRegional(
                    botonEditar.dataset.id
                );

                return;

            }


            const botonEliminar =
                event.target.closest(
                    '.btn-eliminar-regional'
                );


            if (botonEliminar) {

                prepararEliminacionRegional(
                    botonEliminar.dataset.id
                );

                return;

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CREAR REGIONAL - AJAX
    |--------------------------------------------------------------------------
    */

    const formNuevaRegional =
        document.getElementById(
            'formNuevaRegional'
        );


    if (formNuevaRegional) {

        formNuevaRegional.addEventListener(
            'submit',
            async function(event)
            {

                event.preventDefault();


                const formulario =
                    this;


                const boton =
                    formulario.querySelector(
                        'button[type="submit"]'
                    );


                const error =
                    document.getElementById(
                        'errorNuevaRegional'
                    );


                if (error) {

                    error.style.display =
                        'none';

                    error.textContent =
                        '';

                }


                boton.disabled =
                    true;


                boton.textContent =
                    'Guardando...';


                try {

                    const csrfToken =
                        document
                            .querySelector(
                                'meta[name="csrf-token"]'
                            )
                            .getAttribute(
                                'content'
                            );


                    const response =
                        await fetch(
                            formulario.action,
                            {
                                method: 'POST',

                                body:
                                    new FormData(
                                        formulario
                                    ),

                                headers: {

                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',

                                    'X-CSRF-TOKEN':
                                        csrfToken

                                }

                            }
                        );


                    const data =
                        await response.json();


                    if (
                        response.status === 422 &&
                        data.errors
                    ) {

                        throw new Error(
                            Object.values(
                                data.errors
                            )
                            .flat()
                            .join('\n')
                        );

                    }


                    if (!response.ok) {

                        throw new Error(
                            data.message ||
                            data.mensaje ||
                            'No fue posible crear la regional.'
                        );

                    }


                    if (
                        !data.ok ||
                        !data.regional
                    ) {

                        throw new Error(
                            data.mensaje ||
                            'La regional no pudo ser creada.'
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Guardar en memoria JavaScript
                    |--------------------------------------------------------------------------
                    */

                    window.regionalesData.push(
                        data.regional
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Agregar fila
                    |--------------------------------------------------------------------------
                    */

                    agregarFilaRegional(
                        data.regional
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Cerrar y limpiar
                    |--------------------------------------------------------------------------
                    */

                    cerrarModal(
                        'modalNuevaRegional'
                    );


                    formulario.reset();


                    mostrarMensajeExito(
                        data.mensaje ||
                        'Regional creada correctamente.'
                    );

                }
                catch(error) {

                    if (error) {

                        const mensaje =
                            error instanceof Error
                                ? error.message
                                : 'Error inesperado.';


                        if (error) {

                            const mensajeElemento =
                                document.getElementById(
                                    'errorNuevaRegional'
                                );


                            if (mensajeElemento) {

                                mensajeElemento.textContent =
                                    mensaje;

                                mensajeElemento.style.display =
                                    'block';

                            }

                        }

                    }

                }
                finally {

                    boton.disabled =
                        false;

                    boton.textContent =
                        'Guardar Regional';

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | AGREGAR FILA
    |--------------------------------------------------------------------------
    */

    function agregarFilaRegional(regional)
    {

        const tabla =
            document.getElementById(
                'tablaRegionalesBody'
            );


        if (!tabla) {
            return;
        }


        const filaVacia =
            document.getElementById(
                'filaSinRegionales'
            );


        if (filaVacia) {
            filaVacia.remove();
        }


        const filaExistente =
            document.getElementById(
                `regional-row-${regional.id}`
            );


        if (filaExistente) {
            return;
        }


        const fila =
            document.createElement('tr');


        fila.id =
            `regional-row-${regional.id}`;


        fila.dataset.id =
            regional.id;


        fila.innerHTML = `

            <td>
                ${escapeHtml(regional.codigo)}
            </td>

            <td>
                ${escapeHtml(regional.nombre)}
            </td>

            <td>
                ${escapeHtml(
                    regional.descripcion ?? '-'
                )}
            </td>

            <td>

                ${
                    regional.activo

                        ? '<span class="ortomax-status ortomax-status-success">ACTIVO</span>'

                        : '<span class="ortomax-status ortomax-status-danger">INACTIVO</span>'
                }

            </td>

            <td>

                @if(auth()->user()->tienePermiso('regionales.ver'))

                    <button
                        type="button"
                        class="ortomax-btn ortomax-btn-secondary btn-ver-regional"
                        data-id="${regional.id}"
                    >
                        Ver
                    </button>

                @endif


                @if(auth()->user()->tienePermiso('regionales.editar'))

                    <button
                        type="button"
                        class="ortomax-btn ortomax-btn-warning btn-editar-regional"
                        data-id="${regional.id}"
                    >
                        Editar
                    </button>

                @endif


                @if(auth()->user()->tienePermiso('regionales.eliminar'))

                    <button
                        type="button"
                        class="ortomax-btn ortomax-btn-danger btn-eliminar-regional"
                        data-id="${regional.id}"
                    >
                        Eliminar
                    </button>

                @endif

            </td>

        `;


        tabla.appendChild(
            fila
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(valor)
    {

        const div =
            document.createElement('div');


        div.textContent =
            valor ?? '';


        return div.innerHTML;

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR ELIMINACIÓN - AJAX
    |--------------------------------------------------------------------------
    */

    const btnConfirmarEliminar =
        document.getElementById(
            'btnConfirmarEliminar'
        );


    if (btnConfirmarEliminar) {

        btnConfirmarEliminar.addEventListener(
            'click',
            async function()
            {

                if (!regionalEliminarId) {
                    return;
                }


                const id =
                    regionalEliminarId;


                const boton =
                    this;


                const error =
                    document.getElementById(
                        'errorEliminarRegional'
                    );


                boton.disabled =
                    true;


                boton.textContent =
                    'Eliminando...';


                if (error) {

                    error.style.display =
                        'none';

                    error.textContent =
                        '';

                }


                try {

                    const csrfToken =
                        document
                            .querySelector(
                                'meta[name="csrf-token"]'
                            )
                            .getAttribute(
                                'content'
                            );


                    const response =
                        await fetch(
                            `/regionales/${id}`,
                            {
                                method: 'DELETE',

                                headers: {

                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',

                                    'X-CSRF-TOKEN':
                                        csrfToken

                                }

                            }
                        );


                    const data =
                        await response.json();


                    if (
                        !response.ok ||
                        !data.ok
                    ) {

                        throw new Error(
                            data.mensaje ||
                            data.message ||
                            'No fue posible eliminar la regional.'
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Quitar de memoria
                    |--------------------------------------------------------------------------
                    */

                    window.regionalesData =
                        window.regionalesData.filter(
                            function(regional)
                            {
                                return Number(regional.id)
                                    !== Number(id);
                            }
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Quitar fila
                    |--------------------------------------------------------------------------
                    */

                    const fila =
                        document.getElementById(
                            `regional-row-${id}`
                        );


                    if (fila) {
                        fila.remove();
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Tabla vacía
                    |--------------------------------------------------------------------------
                    */

                    const tabla =
                        document.getElementById(
                            'tablaRegionalesBody'
                        );


                    if (
                        tabla &&
                        tabla.children.length === 0
                    ) {

                        tabla.innerHTML = `

                            <tr id="filaSinRegionales">

                                <td
                                    colspan="5"
                                    style="text-align:center;"
                                >
                                    No existen regionales registradas.
                                </td>

                            </tr>

                        `;

                    }


                    cerrarModal(
                        'modalEliminarRegional'
                    );


                    mostrarMensajeExito(
                        data.mensaje ||
                        'Regional eliminada correctamente.'
                    );


                    regionalEliminarId =
                        null;

                }
                catch(error) {

                    const mensaje =
                        error instanceof Error
                            ? error.message
                            : 'Error inesperado.';


                    if (error) {

                        if (document.getElementById(
                            'errorEliminarRegional'
                        )) {

                            const elemento =
                                document.getElementById(
                                    'errorEliminarRegional'
                                );


                            elemento.textContent =
                                mensaje;


                            elemento.style.display =
                                'block';

                        }

                    }


                    boton.disabled =
                        false;


                    boton.textContent =
                        'Eliminar';

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | MENSAJE DE ÉXITO
    |--------------------------------------------------------------------------
    */

    function mostrarMensajeExito(mensaje)
    {

        const contenedor =
            document.querySelector(
                '.ortomax-content'
            );


        if (!contenedor) {
            return;
        }


        const alerta =
            document.createElement('div');


        alerta.className =
            'ortomax-card';


        alerta.textContent =
            mensaje;


        contenedor.insertBefore(
            alerta,
            contenedor.firstElementChild
        );


        setTimeout(
            function()
            {
                alerta.remove();
            },
            4000
        );

    }

</script>


@endsection