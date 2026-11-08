@extends('layouts.app')

@section('title', 'Bodegas')

@section('content')

<div class="ortomax-card">

    {{-- Encabezado --}}
    <div class="ortomax-module-header">

        <div>
            <h1 class="ortomax-title">
                Bodegas
            </h1>

            <p class="ortomax-subtitle">
                Administración de bodegas de ORTOMAX
            </p>
        </div>

        <div>
        <button
            type="button"
            class="ortomax-btn ortomax-btn-primary"
            onclick="abrirNuevaBodega()"
        >
            + Nueva Bodega
        </button>
        </div>

    </div>


    {{-- Tabla --}}
    <div class="ortomax-table-container">

        <table class="ortomax-table">

            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Regional</th>
                    <th>Tipo de bodega</th>
                    <th>Estado</th>
                    <th style="width: 180px;">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody>

                @forelse($bodegas as $bodega)

                <tr data-bodega-id="{{ $bodega->id }}">

                        <td>
                            <strong>
                                {{ $bodega->codigo }}
                            </strong>
                        </td>

                        <td>
                            {{ $bodega->nombre }}
                        </td>

                        <td>
                            {{ $bodega->regional?->nombre ?? 'Sin regional' }}
                        </td>

                        <td>
                            {{ $bodega->tipo_bodega ?? '—' }}
                        </td>

                        <td>

                            @if($bodega->activo)

                                <span class="ortomax-status ortomax-status-active">
                                    ACTIVA
                                </span>

                            @else

                                <span class="ortomax-status ortomax-status-inactive">
                                    INACTIVA
                                </span>

                            @endif

                        </td>
                        <td class="ortomax-actions-cell">

                            <div class="ortomax-actions">
                        
                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-secondary"
                                    onclick="verBodega({{ $bodega->id }})"
                                >
                                    Ver
                                </button>
                        
                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-warning"
                                    onclick="editarBodega({{ $bodega->id }})"
                                >
                                    Editar
                                </button>
                        
                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-danger"
                                    onclick="eliminarBodega({{ $bodega->id }})"
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
                            No existen bodegas registradas.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- Modal Nueva Bodega --}}
<div
    id="modalNuevaBodega"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Nueva Bodega
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarNuevaBodega()"
            >
                ×
            </button>

        </div>


        <form id="formNuevaBodega">

            <div class="ortomax-modal-body">

                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Regional *
                    </label>

                    <select
                        id="nuevaBodegaRegional"
                        name="regional_id"
                        class="ortomax-form-control"
                        required
                    >

                        <option value="">
                            Seleccione una regional
                        </option>

                    </select>

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Código *
                    </label>

                    <input
                        type="text"
                        id="nuevaBodegaCodigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        id="nuevaBodegaNombre"
                        name="nombre"
                        class="ortomax-form-control"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Tipo de bodega
                    </label>

                    <input
                        type="text"
                        id="nuevaBodegaTipo"
                        name="tipo_bodega"
                        class="ortomax-form-control"
                        maxlength="50"
                        placeholder="Ej. PRINCIPAL, SECUNDARIA"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Dirección
                    </label>

                    <input
                        type="text"
                        id="nuevaBodegaDireccion"
                        name="direccion"
                        class="ortomax-form-control"
                        maxlength="255"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        id="nuevaBodegaTelefono"
                        name="telefono"
                        class="ortomax-form-control"
                        maxlength="50"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">

                        <input
                            type="checkbox"
                            id="nuevaBodegaActivo"
                            name="activo"
                            value="1"
                            checked
                        >

                        Bodega activa

                    </label>

                </div>

            </div>


            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarNuevaBodega()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Guardar Bodega
                </button>

            </div>

        </form>

    </div>

</div>


{{-- Modal Editar Bodega --}}
<div
    id="modalEditarBodega"
    class="ortomax-modal"
    style="display:none;"
>
    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Editar Bodega
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarEditarBodega()"
            >
                ×
            </button>

        </div>


        <form id="formEditarBodega">

            <div class="ortomax-modal-body">

                <input
                    type="hidden"
                    id="editarBodegaId"
                >


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Regional *
                    </label>

                    <select
                        id="editarBodegaRegional"
                        name="regional_id"
                        class="ortomax-form-control"
                        required
                    >
                        <option value="">
                            Seleccione una regional
                        </option>
                    </select>

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Código *
                    </label>

                    <input
                        type="text"
                        id="editarBodegaCodigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        id="editarBodegaNombre"
                        name="nombre"
                        class="ortomax-form-control"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Tipo de bodega
                    </label>

                    <input
                        type="text"
                        id="editarBodegaTipo"
                        name="tipo_bodega"
                        class="ortomax-form-control"
                        maxlength="50"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Dirección
                    </label>

                    <input
                        type="text"
                        id="editarBodegaDireccion"
                        name="direccion"
                        class="ortomax-form-control"
                        maxlength="255"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        id="editarBodegaTelefono"
                        name="telefono"
                        class="ortomax-form-control"
                        maxlength="50"
                    >

                </div>


                <div class="ortomax-form-group">

                    <label class="ortomax-form-label">

                        <input
                            type="checkbox"
                            id="editarBodegaActivo"
                            name="activo"
                            value="1"
                        >

                        Bodega activa

                    </label>

                </div>

            </div>


            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarEditarBodega()"
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


{{-- Modal Eliminar Bodega --}}
<div
    id="modalEliminarBodega"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Eliminar Bodega
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarEliminarBodega()"
            >
                ×
            </button>

        </div>


        <div class="ortomax-modal-body">

            <p>
                ¿Está seguro de eliminar esta bodega?
            </p>

            <p>
                <strong id="eliminarBodegaNombre"></strong>
            </p>

            <p
                style="color:#DC3545;"
            >
                Esta acción no se puede deshacer.
            </p>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarEliminarBodega()"
            >
                Cancelar
            </button>

            <button
                type="button"
                class="ortomax-btn ortomax-btn-danger"
                id="btnConfirmarEliminarBodega"
            >
                Eliminar
            </button>

        </div>

    </div>

</div>



{{-- Modal Ver Bodega --}}
<div
    id="modalVerBodega"
    class="ortomax-modal"
    style="display:none;"
>

    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">

            <h2>
                Información de la Bodega
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModalBodega()"
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
                    id="verBodegaCodigo"
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
                    id="verBodegaNombre"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Regional
                </label>

                <input
                    type="text"
                    id="verBodegaRegional"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Tipo de bodega
                </label>

                <input
                    type="text"
                    id="verBodegaTipo"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Dirección
                </label>

                <input
                    type="text"
                    id="verBodegaDireccion"
                    class="ortomax-form-control"
                    readonly
                >

            </div>


            <div class="ortomax-form-group">

                <label class="ortomax-form-label">
                    Teléfono
                </label>

                <input
                    type="text"
                    id="verBodegaTelefono"
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
                    id="verBodegaEstado"
                    class="ortomax-form-control"
                    readonly
                >

            </div>

        </div>


        <div class="ortomax-modal-footer">

            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModalBodega()"
            >
                Cerrar
            </button>

        </div>

    </div>

</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | Datos de Bodegas
    |--------------------------------------------------------------------------
    */

    window.bodegasData = @json($bodegas->values());


    /*
    |--------------------------------------------------------------------------
    | Ver Bodega
    |--------------------------------------------------------------------------
    */

    function verBodega(id)
    {
        const bodega = window.bodegasData.find(
            item => Number(item.id) === Number(id)
        );

        if (!bodega) {
            return;
        }

        document.getElementById('verBodegaCodigo').value =
            bodega.codigo ?? '';

        document.getElementById('verBodegaNombre').value =
            bodega.nombre ?? '';

        document.getElementById('verBodegaRegional').value =
            bodega.regional
                ? bodega.regional.nombre
                : 'Sin regional';

        document.getElementById('verBodegaTipo').value =
            bodega.tipo_bodega ?? '';

        document.getElementById('verBodegaDireccion').value =
            bodega.direccion ?? '';

        document.getElementById('verBodegaTelefono').value =
            bodega.telefono ?? '';

        document.getElementById('verBodegaEstado').value =
            bodega.activo
                ? 'ACTIVA'
                : 'INACTIVA';

        document.getElementById('modalVerBodega').style.display =
            'flex';
    }


    /*
    |--------------------------------------------------------------------------
    | Cerrar modal
    |--------------------------------------------------------------------------
    */

    function cerrarModalBodega()
    {
        document.getElementById('modalVerBodega').style.display =
            'none';
    }


    /*
    |--------------------------------------------------------------------------
    | Cerrar modal haciendo clic fuera
    |--------------------------------------------------------------------------
    */

    document.getElementById('modalVerBodega')
        .addEventListener('click', function(event) {

            if (event.target === this) {

                cerrarModalBodega();

            }

        });


/*
|--------------------------------------------------------------------------
| Nueva Bodega
|--------------------------------------------------------------------------
*/

async function abrirNuevaBodega()
{
    const modal = document.getElementById('modalNuevaBodega');

    const select = document.getElementById(
        'nuevaBodegaRegional'
    );

    select.innerHTML = `
        <option value="">
            Cargando regionales...
        </option>
    `;

    modal.style.display = 'flex';

    try {

        const response = await fetch(
            '/bodegas/create',
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        const data = await response.json();

        if (!data.ok) {
            throw new Error(
                'No fue posible cargar las regionales.'
            );
        }

        select.innerHTML = `
            <option value="">
                Seleccione una regional
            </option>
        `;

        data.regionales.forEach(function(regional) {

            const option = document.createElement('option');

            option.value = regional.id;

            option.textContent =
                regional.codigo + ' - ' + regional.nombre;

            select.appendChild(option);

        });

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Error al cargar regionales
            </option>
        `;

        alert(
            'No fue posible cargar las regionales.'
        );
    }
}


function cerrarNuevaBodega()
{
    document.getElementById(
        'modalNuevaBodega'
    ).style.display = 'none';

    document.getElementById(
        'formNuevaBodega'
    ).reset();

    document.getElementById(
        'nuevaBodegaActivo'
    ).checked = true;
}


/*
|--------------------------------------------------------------------------
| Guardar Nueva Bodega
|--------------------------------------------------------------------------
*/

document.getElementById(
    'formNuevaBodega'
).addEventListener('submit', async function(event) {

    event.preventDefault();

    const form = event.target;

    const datos = new FormData(form);

    try {

        const response = await fetch(
            '/bodegas',
            {
                method: 'POST',

                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',

                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        ).getAttribute('content')
                },

                body: datos
            }
        );

        const data = await response.json();

        if (!response.ok) {

            if (data.errors) {

                const mensajes = Object.values(
                    data.errors
                )
                .flat()
                .join('\n');

                alert(mensajes);

            } else {

                alert(
                    data.message ??
                    'No fue posible crear la bodega.'
                );
            }

            return;
        }


        /*
        |------------------------------------------------------------------
        | Agregamos la nueva bodega a los datos locales
        |------------------------------------------------------------------
        */

        window.bodegasData.push(
            data.bodega
        );


        /*
        |------------------------------------------------------------------
        | Agregamos la fila a la tabla
        |------------------------------------------------------------------
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
            'data-bodega-id',
            data.bodega.id
        );

        tr.innerHTML = `

                <td>
                    <strong>
                        ${data.bodega.codigo}
                    </strong>
                </td>

                <td>
                    ${data.bodega.nombre}
                </td>

                <td>
                    ${
                        data.bodega.regional
                            ? data.bodega.regional.nombre
                            : 'Sin regional'
                    }
                </td>

                <td>
                    ${data.bodega.tipo_bodega ?? '—'}
                </td>

                <td>
                    ${
                        data.bodega.activo
                            ? '<span class="ortomax-status ortomax-status-active">ACTIVA</span>'
                            : '<span class="ortomax-status ortomax-status-inactive">INACTIVA</span>'
                    }
                </td>

 <td class="ortomax-actions-cell">

    <div class="ortomax-actions">

        <button
            type="button"
            class="ortomax-btn ortomax-btn-secondary"
            onclick="verBodega(${data.bodega.id})"
        >
            Ver
        </button>

        <button
            type="button"
            class="ortomax-btn ortomax-btn-warning"
            onclick="editarBodega(${data.bodega.id})"
        >
            Editar
        </button>

        <button
            type="button"
            class="ortomax-btn ortomax-btn-danger"
            onclick="eliminarBodega(${data.bodega.id})"
        >
            Eliminar
        </button>

    </div>

</td>               `;

tbody.appendChild(tr);


        cerrarNuevaBodega();

        /*alert(
            data.mensaje ??
            'Bodega creada correctamente.'
        );
        */

    } catch (error) {

        console.error(error);

        alert(
            'Ocurrió un error al guardar la bodega.'
        );
    }

});


/*
|--------------------------------------------------------------------------
| Editar Bodega
|--------------------------------------------------------------------------
*/

async function editarBodega(id)
{
    const modal = document.getElementById(
        'modalEditarBodega'
    );

    const select = document.getElementById(
        'editarBodegaRegional'
    );

    try {

        select.innerHTML = `
            <option value="">
                Cargando regionales...
            </option>
        `;

        modal.style.display = 'flex';


        const response = await fetch(
            `/bodegas/${id}/edit`,
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );


        const data = await response.json();


        if (!response.ok || !data.ok) {

            throw new Error(
                data.message ??
                'No fue posible cargar la bodega.'
            );

        }


        const bodega = data.bodega;


        /*
        |--------------------------------------------------------------------------
        | Datos de la bodega
        |--------------------------------------------------------------------------
        */

        document.getElementById(
            'editarBodegaId'
        ).value = bodega.id;

        document.getElementById(
            'editarBodegaCodigo'
        ).value = bodega.codigo ?? '';

        document.getElementById(
            'editarBodegaNombre'
        ).value = bodega.nombre ?? '';

        document.getElementById(
            'editarBodegaTipo'
        ).value = bodega.tipo_bodega ?? '';

        document.getElementById(
            'editarBodegaDireccion'
        ).value = bodega.direccion ?? '';

        document.getElementById(
            'editarBodegaTelefono'
        ).value = bodega.telefono ?? '';

        document.getElementById(
            'editarBodegaActivo'
        ).checked = Boolean(
            bodega.activo
        );


        /*
        |--------------------------------------------------------------------------
        | Regionales
        |--------------------------------------------------------------------------
        */

        select.innerHTML = `
            <option value="">
                Seleccione una regional
            </option>
        `;


        data.regionales.forEach(function(regional) {

            const option =
                document.createElement('option');

            option.value = regional.id;

            option.textContent =
                regional.codigo +
                ' - ' +
                regional.nombre;

            if (
                Number(regional.id) ===
                Number(bodega.regional_id)
            ) {
                option.selected = true;
            }

            select.appendChild(option);

        });


    } catch (error) {

        console.error(error);

        cerrarEditarBodega();

        alert(
            error.message ??
            'No fue posible cargar la bodega.'
        );

    }
}


/*
|--------------------------------------------------------------------------
| Cerrar Editar Bodega
|--------------------------------------------------------------------------
*/

function cerrarEditarBodega()
{
    document.getElementById(
        'modalEditarBodega'
    ).style.display = 'none';
}


/*
|--------------------------------------------------------------------------
| Guardar cambios de Bodega
|--------------------------------------------------------------------------
*/

document.getElementById(
    'formEditarBodega'
).addEventListener(
    'submit',
    async function(event) {

        event.preventDefault();


        const id =
            document.getElementById(
                'editarBodegaId'
            ).value;


        const form =
            event.target;


        const datos =
            new FormData(form);


        datos.append(
            '_method',
            'PUT'
        );


        try {

            const response =
                await fetch(
                    `/bodegas/${id}`,
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
                        'No fue posible actualizar la bodega.'
                    );

                }

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Actualizar datos locales
            |--------------------------------------------------------------------------
            */

            const indice =
                window.bodegasData.findIndex(
                    item =>
                        Number(item.id) ===
                        Number(id)
                );


            if (indice !== -1) {

                window.bodegasData[indice] =
                    data.bodega;

            }


            /*
            |--------------------------------------------------------------------------
            | Actualizar fila de la tabla
            |--------------------------------------------------------------------------
            */

            const fila =
                document.querySelector(
                    `tr[data-bodega-id="${id}"]`
                );


            if (fila) {

                fila.innerHTML = `

                    <td>
                        <strong>
                            ${data.bodega.codigo}
                        </strong>
                    </td>

                    <td>
                        ${data.bodega.nombre}
                    </td>

                    <td>
                        ${
                            data.bodega.regional
                                ? data.bodega.regional.nombre
                                : 'Sin regional'
                        }
                    </td>

                    <td>
                        ${data.bodega.tipo_bodega ?? '—'}
                    </td>

                    <td>
                        ${
                            data.bodega.activo
                                ? '<span class="ortomax-status ortomax-status-active">ACTIVA</span>'
                                : '<span class="ortomax-status ortomax-status-inactive">INACTIVA</span>'
                        }
                    </td>

  <td class="ortomax-actions-cell">

    <div class="ortomax-actions">

        <button
            type="button"
            class="ortomax-btn ortomax-btn-secondary"
            onclick="verBodega(${data.bodega.id})"
        >
            Ver
        </button>

        <button
            type="button"
            class="ortomax-btn ortomax-btn-warning"
            onclick="editarBodega(${data.bodega.id})"
        >
            Editar
        </button>

        <button
            type="button"
            class="ortomax-btn ortomax-btn-danger"
            onclick="eliminarBodega(${data.bodega.id})"
        >
            Eliminar
        </button>

    </div>

</td>
                `;

            }


            cerrarEditarBodega();





        } catch (error) {

            console.error(error);

            alert(
                'Ocurrió un error al actualizar la bodega.'
            );

        }

    }
);


/*
|--------------------------------------------------------------------------
| Eliminar Bodega
|--------------------------------------------------------------------------
*/

let bodegaEliminarId = null;


function eliminarBodega(id)
{
    const bodega =
        window.bodegasData.find(
            item =>
                Number(item.id) === Number(id)
        );

    if (!bodega) {
        return;
    }

    bodegaEliminarId = id;

    document.getElementById(
        'eliminarBodegaNombre'
    ).textContent =
        `${bodega.codigo} - ${bodega.nombre}`;

    document.getElementById(
        'modalEliminarBodega'
    ).style.display = 'flex';
}


function cerrarEliminarBodega()
{
    bodegaEliminarId = null;

    document.getElementById(
        'modalEliminarBodega'
    ).style.display = 'none';
}


document.getElementById(
    'btnConfirmarEliminarBodega'
).addEventListener(
    'click',
    async function() {

        if (!bodegaEliminarId) {
            return;
        }

        const id = bodegaEliminarId;

        try {

            const response =
                await fetch(
                    `/bodegas/${id}`,
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
                    data.mensaje ??
                    data.message ??
                    'No fue posible eliminar la bodega.'
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Eliminar del arreglo local
            |--------------------------------------------------------------------------
            */

            window.bodegasData =
                window.bodegasData.filter(
                    item =>
                        Number(item.id) !==
                        Number(id)
                );


            /*
            |--------------------------------------------------------------------------
            | Eliminar fila de la tabla
            |--------------------------------------------------------------------------
            */

            const fila =
                document.querySelector(
                    `tr[data-bodega-id="${id}"]`
                );


            if (fila) {
                fila.remove();
            }


            /*
            |--------------------------------------------------------------------------
            | Si no quedan bodegas
            |--------------------------------------------------------------------------
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
                            No existen bodegas registradas.
                        </td>
                    </tr>
                `;

            }


            cerrarEliminarBodega();

        } catch (error) {

            console.error(error);

            alert(
                'Ocurrió un error al eliminar la bodega.'
            );

        }

    }
);



</script>

@endsection