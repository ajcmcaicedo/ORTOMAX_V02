@extends('layouts.app')

@section('title', 'Instituciones')

@section('content')

<div class="ortomax-card">

    {{-- ==========================================================
         ENCABEZADO DEL MÓDULO
         ========================================================== --}}

    <div class="ortomax-module-header">

        <div>

            <h1 class="ortomax-title">
                Instituciones
            </h1>

            <div class="ortomax-subtitle">
                Administración de clínicas, hospitales e instituciones ORTOMAX
            </div>

        </div>


        {{-- NUEVA INSTITUCIÓN --}}

        <div class="instituciones-header-actions">

            @if(auth()->user()->tienePermiso('instituciones.importar'))
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="abrirModal('modalImportarInstituciones')"
                >
                    📥 Importar Excel
                </button>
            @endif

            @if(auth()->user()->tienePermiso('instituciones.exportar'))
                <a
                    href="{{ route('instituciones.exportar') }}"
                    class="ortomax-btn ortomax-btn-secondary"
                >
                    📤 Exportar Excel
                </a>
            @endif

            @if(auth()->user()->tienePermiso('instituciones.crear'))
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-primary"
                    onclick="abrirModal('modalNuevaInstitucion')"
                >
                    + Nueva Institución
                </button>
            @endif

        </div>

    </div>


    {{-- ==========================================================
         BUSCADOR
         ========================================================== --}}

    <form
        method="GET"
        action="{{ route('instituciones.index') }}"
        style="margin-bottom:20px;"
    >

        <div class="ortomax-form-group">

            <label
                for="q"
                class="ortomax-form-label"
            >
                Buscar institución
            </label>

            <div style="
                display:flex;
                gap:10px;
                align-items:center;
            ">

                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $q }}"
                    class="ortomax-form-control"
                    placeholder="Código, RUC, razón social, clínica, ciudad..."
                    autocomplete="off"
                    style="max-width:500px;"
                >

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Buscar
                </button>

                @if($q !== '')

                    <a
                        href="{{ route('instituciones.index') }}"
                        class="ortomax-btn ortomax-btn-secondary"
                    >
                        Limpiar
                    </a>

                @endif

            </div>

        </div>

    </form>


    {{-- ==========================================================
         MENSAJE DE ÉXITO
         ========================================================== --}}

    @if(session('success'))

        <div class="ortomax-alert ortomax-alert-success">
            {{ session('success') }}
        </div>

    @endif


    {{-- ==========================================================
         TABLA
         ========================================================== --}}

    <div class="ortomax-table-container instituciones-table-container">

        <table class="ortomax-table instituciones-table">

            <colgroup>
                <col class="col-codigo">
                <col class="col-tipo">
                <col class="col-ruc">
                <col class="col-razon">
                <col class="col-clinica">
                <col class="col-ciudad">
                <col class="col-regional">
                <col class="col-estado">
                <col class="col-acciones">
            </colgroup>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Tipo</th>
                    <th>RUC</th>
                    <th>Razón Social</th>
                    <th>Clínica / Hospital</th>
                    <th>Ciudad</th>
                    <th>Regional</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody>

                @forelse($instituciones as $institucion)

                    <tr>

                        {{-- CÓDIGO --}}

                        <td class="institucion-cell-codigo">
                            <strong>
                                {{ $institucion->codigo }}
                            </strong>
                        </td>


                        {{-- TIPO --}}

                        <td class="institucion-cell-tipo">
                            {{ $institucion->tipo }}
                        </td>


                        {{-- RUC --}}

                        <td class="institucion-cell-ruc">
                            {{ $institucion->ruc ?: '—' }}
                        </td>


                        {{-- RAZÓN SOCIAL --}}

                        <td class="institucion-cell-razon" title="{{ $institucion->razon_social }}">
                            {{ $institucion->razon_social }}
                        </td>


                        {{-- CLÍNICA / HOSPITAL --}}

                        <td class="institucion-cell-clinica" title="{{ $institucion->nombre_comercial ?: '—' }}">
                            {{ $institucion->nombre_comercial ?: '—' }}
                        </td>


                        {{-- CIUDAD --}}

                        <td class="institucion-cell-ciudad">
                            {{ $institucion->ciudad ?: '—' }}
                        </td>


                        {{-- REGIONAL --}}

                        <td class="institucion-cell-regional">

                            @if($institucion->regional)

                                {{ $institucion->regional->nombre }}

                            @else

                                Sin regional

                            @endif

                        </td>


                        {{-- ESTADO --}}

                        <td class="institucion-cell-estado">

                            @if($institucion->activo)

                                <span class="ortomax-status ortomax-status-active">
                                    ACTIVA
                                </span>

                            @else

                                <span class="ortomax-status ortomax-status-inactive">
                                    INACTIVA
                                </span>

                            @endif

                        </td>


                        {{-- ACCIONES --}}

                        <td class="institucion-cell-acciones">

                            <div class="instituciones-actions-wrap">

                            @if(auth()->user()->tienePermiso('instituciones.ver'))

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-secondary"
                                    onclick="verInstitucion({{ $institucion->id }})"
                                >
                                    Ver
                                </button>

                            @endif


                            @if(auth()->user()->tienePermiso('instituciones.editar'))

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-warning"
                                    onclick="editarInstitucion({{ $institucion->id }})"
                                >
                                    Editar
                                </button>

                            @endif


                            @if(auth()->user()->tienePermiso('instituciones.eliminar'))

                                <button
                                    type="button"
                                    class="ortomax-btn ortomax-btn-danger"
                                    onclick="eliminarInstitucion(
                                        {{ $institucion->id }},
                                        @js($institucion->codigo)
                                    )"
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
                            colspan="9"
                            style="
                                text-align:center;
                                padding:40px;
                            "
                        >
                            No existen instituciones registradas.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- ==========================================================
         PAGINACIÓN
         ========================================================== --}}

    @if($instituciones->hasPages())

        <div style="margin-top:20px;">

            {{ $instituciones->links() }}

        </div>

    @endif

</div>





<style>
    .instituciones-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    #modalImportarInstituciones .ortomax-modal-content {
        width: 94vw;
        max-width: 1100px;
    }

    #modalImportarInstituciones .ortomax-modal-body {
        padding: 16px 22px;
    }

    .import-excel-dropzone {
        border: 1px dashed #aeb8c2;
        border-radius: 8px;
        padding: 16px;
        background: #f8fafc;
    }

    .import-excel-file {
        width: 100%;
        box-sizing: border-box;
    }

    .import-summary {
        display: grid;
        grid-template-columns: repeat(5, minmax(110px, 1fr));
        gap: 8px;
        margin: 14px 0;
    }

    .import-summary-card {
        border: 1px solid #dfe5ea;
        border-radius: 7px;
        padding: 9px 12px;
        background: #fff;
    }

    .import-summary-card strong {
        display: block;
        font-size: 18px;
        line-height: 1.2;
    }

    .import-summary-card span {
        font-size: 12px;
        color: #667085;
    }

    .import-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 600;
    }

    .import-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex: 0 0 8px;
    }

    .import-status-new .import-status-dot { background: #12b76a; }
    .import-status-update .import-status-dot { background: #f79009; }
    .import-status-nochange .import-status-dot { background: #98a2b3; }
    .import-status-error .import-status-dot { background: #f04438; }

    .import-summary-label {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .import-summary-label .import-status-dot {
        width: 7px;
        height: 7px;
        flex-basis: 7px;
    }

    .import-preview-wrap {
        max-height: 340px;
        overflow: auto;
        border: 1px solid #dfe5ea;
        border-radius: 7px;
    }

    .import-preview-table {
        width: max-content;
        min-width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .import-preview-table th,
    .import-preview-table td {
        padding: 6px 8px;
        border-bottom: 1px solid #e8edf1;
        white-space: nowrap;
        text-align: left;
        vertical-align: top;
    }

    .import-preview-table th {
        position: sticky;
        top: 0;
        background: #f4f7f9;
        z-index: 1;
    }

    .import-row-error td {
        background: #fff4f4;
    }

    .import-row-new td {
        background: #f0fdf4;
    }

    .import-row-update td {
        background: #fffbeb;
    }

    .import-row-nochange td {
        background: #f8fafc;
    }

    .import-changes {
        font-size: 12px;
        line-height: 1.5;
        white-space: normal;
        min-width: 260px;
    }

    .import-change-item {
        margin-bottom: 3px;
    }

    .import-old-value {
        color: #b42318;
        text-decoration: line-through;
    }

    .import-new-value {
        color: #027a48;
        font-weight: 600;
    }

    .import-error-box {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 7px;
        border: 1px solid #f1b5b5;
        background: #fff5f5;
        color: #9b1c1c;
        font-size: 13px;
        max-height: 160px;
        overflow: auto;
    }

    @media (max-width: 700px) {
        .instituciones-header-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .instituciones-header-actions .ortomax-btn {
            flex: 1 1 100%;
        }

        .import-summary {
            grid-template-columns: repeat(2, minmax(120px, 1fr));
        }

        #modalImportarInstituciones .ortomax-modal-content {
            width: 96vw;
        }
    }
</style>

<style>
    /*
    |--------------------------------------------------------------------------
    | TABLA INSTITUCIONES - PRESENTACION COMPACTA Y ORDENADA
    |--------------------------------------------------------------------------
    */
    .instituciones-table-container {
        width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    .instituciones-table {
        width: 100%;
        min-width: 1120px;
        table-layout: fixed;
    }

    .instituciones-table th,
    .instituciones-table td {
        vertical-align: middle;
    }

    .instituciones-table th {
        white-space: nowrap;
    }

    .instituciones-table .col-codigo { width: 90px; }
    .instituciones-table .col-tipo { width: 105px; }
    .instituciones-table .col-ruc { width: 125px; }
    .instituciones-table .col-razon { width: 225px; }
    .instituciones-table .col-clinica { width: 225px; }
    .instituciones-table .col-ciudad { width: 105px; }
    .instituciones-table .col-regional { width: 115px; }
    .instituciones-table .col-estado { width: 90px; }
    .instituciones-table .col-acciones { width: 180px; }

    .institucion-cell-codigo,
    .institucion-cell-tipo,
    .institucion-cell-ruc,
    .institucion-cell-ciudad,
    .institucion-cell-regional,
    .institucion-cell-estado {
        white-space: nowrap;
    }

    .institucion-cell-razon,
    .institucion-cell-clinica {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 1px;
    }

    .institucion-cell-acciones {
        white-space: nowrap;
    }

    .instituciones-actions-wrap {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 5px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .instituciones-actions-wrap .ortomax-btn {
        flex: 0 0 auto;
        white-space: nowrap;
        padding: 6px 8px;
        font-size: 12px;
        line-height: 1.2;
    }

    @media (max-width: 900px) {
        .instituciones-table {
            min-width: 1000px;
        }
    }
</style>

<style>
    /*
    |--------------------------------------------------------------------------
    | MODAL INSTITUCIONES - FORMATO COMPACTO
    |--------------------------------------------------------------------------
    */
    #modalNuevaInstitucion .ortomax-modal-content {
        width: 92vw;
        max-width: 880px;
    }

    #modalNuevaInstitucion .ortomax-modal-body {
        padding: 16px 24px;
    }

    #modalNuevaInstitucion .institucion-form-row {
        display: grid;
        grid-template-columns: 155px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        margin-bottom: 7px;
    }

    #modalNuevaInstitucion .institucion-form-row > .ortomax-form-label {
        margin: 0;
        text-align: left;
    }

    #modalNuevaInstitucion .institucion-form-row > .ortomax-form-control {
        width: 100%;
        min-height: 34px;
        height: 34px;
        padding: 6px 10px;
        box-sizing: border-box;
    }

    #modalNuevaInstitucion .institucion-form-row > .institucion-form-help {
        grid-column: 2;
        margin-top: -8px;
        font-size: 12px;
    }

    #modalNuevaInstitucion .institucion-form-row-full {
        display: block;
        margin-bottom: 7px;
    }

    #modalNuevaInstitucion .institucion-form-row-full .ortomax-form-label {
        display: block;
        margin-bottom: 6px;
    }

    @media (max-width: 700px) {
        #modalNuevaInstitucion .ortomax-modal-content {
            width: 96vw;
        }

        #modalNuevaInstitucion .institucion-form-row {
            grid-template-columns: 1fr;
            gap: 6px;
        }

        #modalNuevaInstitucion .institucion-form-row > .institucion-form-help {
            grid-column: 1;
            margin-top: 0;
        }
    }
</style>


<style>
    #modalVerInstitucion .ortomax-modal-content,
    #modalEditarInstitucion .ortomax-modal-content {
        width: 92vw;
        max-width: 880px;
    }

    #modalVerInstitucion .ortomax-modal-body,
    #modalEditarInstitucion .ortomax-modal-body {
        padding: 16px 24px;
    }

    .institucion-ver-row {
        display: grid;
        grid-template-columns: 155px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        margin-bottom: 8px;
    }

    .institucion-ver-label {
        font-weight: 600;
        color: #333;
    }

    .institucion-ver-value {
        min-height: 34px;
        padding: 7px 10px;
        border: 1px solid #d9e0e6;
        border-radius: 6px;
        background: #f8f9fa;
        box-sizing: border-box;
    }

    #modalEditarInstitucion .institucion-edit-row {
        display: grid;
        grid-template-columns: 155px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        margin-bottom: 7px;
    }

    #modalEditarInstitucion .institucion-edit-row > label {
        margin: 0;
    }

    #modalEditarInstitucion .institucion-edit-row > input,
    #modalEditarInstitucion .institucion-edit-row > select {
        width: 100%;
        min-height: 34px;
        height: 34px;
        padding: 6px 10px;
        box-sizing: border-box;
    }

    @media (max-width: 700px) {
        #modalVerInstitucion .ortomax-modal-content,
        #modalEditarInstitucion .ortomax-modal-content {
            width: 96vw;
            max-height: 94vh;
        }

        #modalVerInstitucion .ortomax-modal-body,
        #modalEditarInstitucion .ortomax-modal-body {
            padding: 14px 16px;
        }

        .institucion-ver-row,
        #modalEditarInstitucion .institucion-edit-row {
            grid-template-columns: 1fr;
            gap: 4px;
            margin-bottom: 9px;
        }
    }
</style>

{{-- ==============================================================
     MODAL NUEVA INSTITUCIÓN
     ============================================================== --}}

@if(auth()->user()->tienePermiso('instituciones.crear'))

<div
    id="modalNuevaInstitucion"
    class="ortomax-modal"
>

    <div class="ortomax-modal-content">

        {{-- CABECERA --}}

        <div class="ortomax-modal-header">

            <h2 class="ortomax-modal-title">
                Nueva Institución
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModal('modalNuevaInstitucion')"
                aria-label="Cerrar"
            >
                &times;
            </button>

        </div>


        {{-- FORMULARIO --}}

        <form
            id="formNuevaInstitucion"
            action="{{ route('instituciones.store') }}"
            method="POST"
        >

            @csrf


            <div class="ortomax-modal-body">

                {{-- ERRORES --}}

                <div
                    id="errorNuevaInstitucion"
                    class="ortomax-alert ortomax-alert-danger"
                    style="display:none;"
                ></div>


                {{-- DATOS PRINCIPALES --}}

                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionCodigo"
                        class="ortomax-form-label"
                    >
                        Código *
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionCodigo"
                        name="codigo"
                        class="ortomax-form-control"
                        maxlength="30"
                        required
                        autocomplete="off"
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionTipo"
                        class="ortomax-form-label"
                    >
                        Tipo *
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionTipo"
                        name="tipo"
                        class="ortomax-form-control"
                        maxlength="30"
                        placeholder="Ej.: CLINICA"
                        required
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionRuc"
                        class="ortomax-form-label"
                    >
                        RUC
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionRuc"
                        name="ruc"
                        class="ortomax-form-control"
                        maxlength="30"
                        autocomplete="off"
                    >


                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionRazonSocial"
                        class="ortomax-form-label"
                    >
                        Razón Social *
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionRazonSocial"
                        name="razon_social"
                        class="ortomax-form-control"
                        maxlength="200"
                        required
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionNombreComercial"
                        class="ortomax-form-label"
                    >
                        Clínica / Hospital
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionNombreComercial"
                        name="nombre_comercial"
                        class="ortomax-form-control"
                        maxlength="200"
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionDireccion"
                        class="ortomax-form-label"
                    >
                        Dirección
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionDireccion"
                        name="direccion"
                        class="ortomax-form-control"
                        maxlength="255"
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionCiudad"
                        class="ortomax-form-label"
                    >
                        Ciudad
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionCiudad"
                        name="ciudad"
                        class="ortomax-form-control"
                        maxlength="100"
                    >

                </div>


                {{-- REGIONAL --}}

                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionRegional"
                        class="ortomax-form-label"
                    >
                        Regional
                    </label>

                    <select
                        id="nuevaInstitucionRegional"
                        name="regional_id"
                        class="ortomax-form-control"
                    >

                        <option value="">
                            Seleccione una regional
                        </option>

                        @foreach($regionales as $regional)

                            <option value="{{ $regional->id }}">

                                {{ $regional->codigo }}
                                -
                                {{ $regional->nombre }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- CONTACTO --}}

                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionTelefono"
                        class="ortomax-form-label"
                    >
                        Teléfono
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionTelefono"
                        name="telefono"
                        class="ortomax-form-control"
                        maxlength="50"
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionEmail"
                        class="ortomax-form-label"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        id="nuevaInstitucionEmail"
                        name="email"
                        class="ortomax-form-control"
                        maxlength="150"
                    >

                </div>


                {{-- CAMPOS RESERVADOS --}}

                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionCampo1"
                        class="ortomax-form-label"
                    >
                        Campo 1
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionCampo1"
                        name="campo1"
                        class="ortomax-form-control"
                        maxlength="255"
                    >

                </div>


                <div class="ortomax-form-group institucion-form-row">

                    <label
                        for="nuevaInstitucionCampo2"
                        class="ortomax-form-label"
                    >
                        Campo 2
                    </label>

                    <input
                        type="text"
                        id="nuevaInstitucionCampo2"
                        name="campo2"
                        class="ortomax-form-control"
                        maxlength="255"
                    >

                </div>


                {{-- ESTADO --}}

                <div class="ortomax-form-group institucion-form-row">

                    <label
                        class="ortomax-form-label"
                    >
                        Estado
                    </label>

                    <label style="display:flex; align-items:center; gap:8px;">

                        <input
                            type="checkbox"
                            id="nuevaInstitucionActivo"
                            name="activo"
                            value="1"
                            checked
                        >

                        Institución activa

                    </label>

                </div>

            </div>


            {{-- PIE DEL MODAL --}}

            <div class="ortomax-modal-footer">

                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarModal('modalNuevaInstitucion')"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Guardar Institución
                </button>

            </div>

        </form>

    </div>

</div>

@endif




{{-- ==============================================================
     MODAL VER INSTITUCIÓN
     ============================================================== --}}

@if(auth()->user()->tienePermiso('instituciones.ver'))
<div id="modalVerInstitucion" class="ortomax-modal">
    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">
            <h2 class="ortomax-modal-title" id="tituloVerInstitucion">
                Información de Institución
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModal('modalVerInstitucion')"
                aria-label="Cerrar"
            >
                &times;
            </button>
        </div>

        <div class="ortomax-modal-body">

            <div id="errorVerInstitucion" class="ortomax-alert ortomax-alert-danger" style="display:none;"></div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Código</div>
                <div class="institucion-ver-value" id="verInstitucionCodigo">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Tipo</div>
                <div class="institucion-ver-value" id="verInstitucionTipo">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">RUC</div>
                <div class="institucion-ver-value" id="verInstitucionRuc">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Razón Social</div>
                <div class="institucion-ver-value" id="verInstitucionRazonSocial">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Clínica / Hospital</div>
                <div class="institucion-ver-value" id="verInstitucionNombreComercial">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Dirección</div>
                <div class="institucion-ver-value" id="verInstitucionDireccion">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Ciudad</div>
                <div class="institucion-ver-value" id="verInstitucionCiudad">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Regional</div>
                <div class="institucion-ver-value" id="verInstitucionRegional">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Teléfono</div>
                <div class="institucion-ver-value" id="verInstitucionTelefono">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Email</div>
                <div class="institucion-ver-value" id="verInstitucionEmail">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Campo 1</div>
                <div class="institucion-ver-value" id="verInstitucionCampo1">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Campo 2</div>
                <div class="institucion-ver-value" id="verInstitucionCampo2">—</div>
            </div>

            <div class="institucion-ver-row">
                <div class="institucion-ver-label">Estado</div>
                <div class="institucion-ver-value" id="verInstitucionEstado">—</div>
            </div>

        </div>

        <div class="ortomax-modal-footer">
            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModal('modalVerInstitucion')"
            >
                Cerrar
            </button>
        </div>
    </div>
</div>
@endif


{{-- ==============================================================
     MODAL EDITAR INSTITUCIÓN
     ============================================================== --}}

@if(auth()->user()->tienePermiso('instituciones.editar'))
<div id="modalEditarInstitucion" class="ortomax-modal">
    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">
            <h2 class="ortomax-modal-title">
                Editar Institución
            </h2>

            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModal('modalEditarInstitucion')"
                aria-label="Cerrar"
            >
                &times;
            </button>
        </div>

        <form id="formEditarInstitucion">
            <div class="ortomax-modal-body">

                <div id="errorEditarInstitucion" class="ortomax-alert ortomax-alert-danger" style="display:none;"></div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionCodigo" class="ortomax-form-label">Código *</label>
                    <input type="text" id="editarInstitucionCodigo" name="codigo" class="ortomax-form-control" maxlength="30" required>
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionTipo" class="ortomax-form-label">Tipo *</label>
                    <input type="text" id="editarInstitucionTipo" name="tipo" class="ortomax-form-control" maxlength="30" required>
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionRuc" class="ortomax-form-label">RUC</label>
                    <input type="text" id="editarInstitucionRuc" name="ruc" class="ortomax-form-control" maxlength="30">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionRazonSocial" class="ortomax-form-label">Razón Social *</label>
                    <input type="text" id="editarInstitucionRazonSocial" name="razon_social" class="ortomax-form-control" maxlength="200" required>
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionNombreComercial" class="ortomax-form-label">Clínica / Hospital</label>
                    <input type="text" id="editarInstitucionNombreComercial" name="nombre_comercial" class="ortomax-form-control" maxlength="200">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionDireccion" class="ortomax-form-label">Dirección</label>
                    <input type="text" id="editarInstitucionDireccion" name="direccion" class="ortomax-form-control" maxlength="255">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionCiudad" class="ortomax-form-label">Ciudad</label>
                    <input type="text" id="editarInstitucionCiudad" name="ciudad" class="ortomax-form-control" maxlength="100">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionRegional" class="ortomax-form-label">Regional</label>
                    <select id="editarInstitucionRegional" name="regional_id" class="ortomax-form-control">
                        <option value="">Seleccione una regional</option>
                        @foreach($regionales as $regional)
                            <option value="{{ $regional->id }}">
                                {{ $regional->codigo }} - {{ $regional->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionTelefono" class="ortomax-form-label">Teléfono</label>
                    <input type="text" id="editarInstitucionTelefono" name="telefono" class="ortomax-form-control" maxlength="50">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionEmail" class="ortomax-form-label">Email</label>
                    <input type="email" id="editarInstitucionEmail" name="email" class="ortomax-form-control" maxlength="150">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionCampo1" class="ortomax-form-label">Campo 1</label>
                    <input type="text" id="editarInstitucionCampo1" name="campo1" class="ortomax-form-control" maxlength="255">
                </div>

                <div class="institucion-edit-row">
                    <label for="editarInstitucionCampo2" class="ortomax-form-label">Campo 2</label>
                    <input type="text" id="editarInstitucionCampo2" name="campo2" class="ortomax-form-control" maxlength="255">
                </div>

                <div class="institucion-edit-row">
                    <label class="ortomax-form-label">Estado</label>
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" id="editarInstitucionActivo" name="activo" value="1">
                        Institución activa
                    </label>
                </div>

            </div>

            <div class="ortomax-modal-footer">
                <button
                    type="button"
                    class="ortomax-btn ortomax-btn-secondary"
                    onclick="cerrarModal('modalEditarInstitucion')"
                >
                    Cancelar
                </button>

                <button type="submit" class="ortomax-btn ortomax-btn-primary">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endif



@if(auth()->user()->tienePermiso('instituciones.importar'))
<div
    id="modalImportarInstituciones"
    class="ortomax-modal"
>
    <div class="ortomax-modal-content">

        <div class="ortomax-modal-header">
            <div class="ortomax-modal-title">
                Importar Instituciones desde Excel
            </div>
            <button
                type="button"
                class="ortomax-modal-close"
                onclick="cerrarModal('modalImportarInstituciones')"
            >
                &times;
            </button>
        </div>

        <div class="ortomax-modal-body">

            <div id="importInstitucionesError" class="import-error-box" style="display:none;"></div>
            <div id="importInstitucionesSuccess" class="ortomax-alert ortomax-alert-success" style="display:none;"></div>

            <div id="importInstitucionesPasoArchivo">
                <div class="import-excel-dropzone">
                    <div style="font-weight:600; margin-bottom:6px;">Seleccione el archivo Excel</div>
                    <div style="font-size:12px; color:#667085; margin-bottom:10px;">
                        Formato aceptado: el mismo formato maestro utilizado por Exportar Excel: CODIGO, Tipo, RUC, Razón Social_Cliente, Clínica_Hospital, Dirección, Campo1 y Campo2.
                    </div>
                    <input
                        type="file"
                        id="archivoImportarInstituciones"
                        class="ortomax-form-control import-excel-file"
                        accept=".xlsx,.xls"
                    >
                </div>
            </div>

            <div id="importInstitucionesResultado" style="display:none;">
                <div class="import-summary">
                    <div class="import-summary-card">
                        <strong id="importTotal">0</strong>
                        <span>Registros</span>
                    </div>
                    <div class="import-summary-card">
                        <strong id="importNuevos">0</strong>
                        <span class="import-summary-label"><span class="import-status-dot" style="background:#12b76a;"></span>Nuevos</span>
                    </div>
                    <div class="import-summary-card">
                        <strong id="importActualizar">0</strong>
                        <span class="import-summary-label"><span class="import-status-dot" style="background:#f79009;"></span>Actualizar</span>
                    </div>
                    <div class="import-summary-card">
                        <strong id="importSinCambios">0</strong>
                        <span class="import-summary-label"><span class="import-status-dot" style="background:#98a2b3;"></span>Sin cambios</span>
                    </div>
                    <div class="import-summary-card">
                        <strong id="importErrores">0</strong>
                        <span class="import-summary-label"><span class="import-status-dot" style="background:#f04438;"></span>Errores</span>
                    </div>
                </div>

                <div id="importInstitucionesPreview" class="import-preview-wrap"></div>
            </div>

        </div>

        <div class="ortomax-modal-footer">
            <button
                type="button"
                class="ortomax-btn ortomax-btn-secondary"
                onclick="cerrarModal('modalImportarInstituciones')"
            >
                Cancelar
            </button>

            <button
                type="button"
                id="btnAnalizarInstituciones"
                class="ortomax-btn ortomax-btn-primary"
                onclick="analizarImportacionInstituciones()"
            >
                Analizar archivo
            </button>

            <button
                type="button"
                id="btnConfirmarImportacionInstituciones"
                class="ortomax-btn ortomax-btn-primary"
                style="display:none;"
                onclick="confirmarImportacionInstituciones()"
            >
                Confirmar importación
            </button>
        </div>
    </div>
</div>
@endif

{{-- ==============================================================
     JAVASCRIPT
     ============================================================== --}}

<script>

/*
|--------------------------------------------------------------------------
| Cerrar modal haciendo clic fuera
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    const modal =
        document.getElementById('modalNuevaInstitucion');

    if (modal) {

        modal.addEventListener('click', function (event) {

            if (event.target === this) {

                cerrarModal(
                    'modalNuevaInstitucion'
                );

            }

        });

    }

});



/*
|--------------------------------------------------------------------------
| Ver institución
|--------------------------------------------------------------------------
*/

function verInstitucion(id)
{
    const error = document.getElementById('errorVerInstitucion');

    if (error) {
        error.style.display = 'none';
        error.textContent = '';
    }

    fetch("{{ url('/instituciones') }}/" + id, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('No fue posible consultar la institución.');
        }

        return response.json();
    })
    .then(function(data) {
        const i = data.institucion;

        document.getElementById('verInstitucionCodigo').textContent = i.codigo || '—';
        document.getElementById('verInstitucionTipo').textContent = i.tipo || '—';
        document.getElementById('verInstitucionRuc').textContent = i.ruc || '—';
        document.getElementById('verInstitucionRazonSocial').textContent = i.razon_social || '—';
        document.getElementById('verInstitucionNombreComercial').textContent = i.nombre_comercial || '—';
        document.getElementById('verInstitucionDireccion').textContent = i.direccion || '—';
        document.getElementById('verInstitucionCiudad').textContent = i.ciudad || '—';

        const regional = i.regional
            ? ((i.regional.codigo || '') + ' - ' + (i.regional.nombre || ''))
            : 'Sin regional';

        document.getElementById('verInstitucionRegional').textContent = regional;
        document.getElementById('verInstitucionTelefono').textContent = i.telefono || '—';
        document.getElementById('verInstitucionEmail').textContent = i.email || '—';
        document.getElementById('verInstitucionCampo1').textContent = i.campo1 || '—';
        document.getElementById('verInstitucionCampo2').textContent = i.campo2 || '—';
        document.getElementById('verInstitucionEstado').textContent = i.activo ? 'ACTIVA' : 'INACTIVA';

        abrirModal('modalVerInstitucion');
    })
    .catch(function(error) {
        if (error) {
            console.error(error);
        }

        if (error) {
            const box = document.getElementById('errorVerInstitucion');

            if (box) {
                box.textContent = 'No fue posible cargar la información de la institución.';
                box.style.display = 'block';
                abrirModal('modalVerInstitucion');
            }
        }
    });
}


/*
|--------------------------------------------------------------------------
| Editar institución
|--------------------------------------------------------------------------
*/

function editarInstitucion(id)
{
    const error = document.getElementById('errorEditarInstitucion');

    if (error) {
        error.style.display = 'none';
        error.textContent = '';
    }

    fetch("{{ url('/instituciones') }}/" + id + "/edit", {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('No fue posible consultar la institución.');
        }

        return response.json();
    })
    .then(function(data) {
        const i = data.institucion;

        document.getElementById('formEditarInstitucion').dataset.id = i.id;

        document.getElementById('editarInstitucionCodigo').value = i.codigo || '';
        document.getElementById('editarInstitucionTipo').value = i.tipo || '';
        document.getElementById('editarInstitucionRuc').value = i.ruc || '';
        document.getElementById('editarInstitucionRazonSocial').value = i.razon_social || '';
        document.getElementById('editarInstitucionNombreComercial').value = i.nombre_comercial || '';
        document.getElementById('editarInstitucionDireccion').value = i.direccion || '';
        document.getElementById('editarInstitucionCiudad').value = i.ciudad || '';
        document.getElementById('editarInstitucionRegional').value = i.regional_id || '';
        document.getElementById('editarInstitucionTelefono').value = i.telefono || '';
        document.getElementById('editarInstitucionEmail').value = i.email || '';
        document.getElementById('editarInstitucionCampo1').value = i.campo1 || '';
        document.getElementById('editarInstitucionCampo2').value = i.campo2 || '';
        document.getElementById('editarInstitucionActivo').checked = !!i.activo;

        abrirModal('modalEditarInstitucion');
    })
    .catch(function(error) {
        console.error(error);

        if (error) {
            const box = document.getElementById('errorEditarInstitucion');

            if (box) {
                box.textContent = 'No fue posible cargar la información de la institución.';
                box.style.display = 'block';
                abrirModal('modalEditarInstitucion');
            }
        }
    });
}


/*
|--------------------------------------------------------------------------
| Guardar edición
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('formEditarInstitucion');

    if (!form) {
        return;
    }

    form.addEventListener('submit', function(event) {

        event.preventDefault();

        const id = form.dataset.id;
        const error = document.getElementById('errorEditarInstitucion');
        const data = new FormData(form);

        data.set('_method', 'PUT');

        if (!document.getElementById('editarInstitucionActivo').checked) {
            data.set('activo', '0');
        }

        fetch("{{ url('/instituciones') }}/" + id, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: data
        })
        .then(function(response) {

            return response.json().then(function(body) {
                return {
                    ok: response.ok,
                    status: response.status,
                    body: body
                };
            });

        })
        .then(function(result) {

            if (!result.ok) {

                if (result.status === 422 && result.body.errors) {

                    const mensajes = Object.values(result.body.errors)
                        .flat()
                        .join('<br>');

                    error.innerHTML = mensajes;
                } else {

                    error.textContent =
                        result.body.message ||
                        'No fue posible actualizar la institución.';
                }

                error.style.display = 'block';

                return;
            }

            cerrarModal('modalEditarInstitucion');
            window.location.reload();
        })
        .catch(function(errorAjax) {

            console.error(errorAjax);

            error.textContent =
                'Ocurrió un error al actualizar la institución.';

            error.style.display = 'block';
        });
    });
});


/*
|--------------------------------------------------------------------------
| Eliminar institución
|--------------------------------------------------------------------------
*/

function eliminarInstitucion(id, codigo)
{
    if (!confirm(
        '¿Está seguro de eliminar la institución "' +
        codigo +
        '"?'
    )) {
        return;
    }


    fetch(
        "{{ url('/instituciones') }}/" + id,
        {
            method: 'DELETE',

            headers: {
                'Accept': 'application/json',

                'X-Requested-With':
                    'XMLHttpRequest',

                'X-CSRF-TOKEN':
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    ).getAttribute('content')
            }
        }
    )
    .then(function (response) {

        return response.json();

    })
    .then(function (data) {

        if (data.success) {

            window.location.reload();

            return;

        }

        alert(
            data.message ??
            'No fue posible eliminar la institución.'
        );

    })
    .catch(function (error) {

        console.error(error);

        alert(
            'Ocurrió un error al eliminar la institución.'
        );

    });
}


/*
|--------------------------------------------------------------------------
| Importar instituciones desde Excel
|--------------------------------------------------------------------------
*/

let tokenImportacionInstituciones = null;

function analizarImportacionInstituciones()
{
    const input = document.getElementById('archivoImportarInstituciones');
    const error = document.getElementById('importInstitucionesError');
    const success = document.getElementById('importInstitucionesSuccess');
    const resultado = document.getElementById('importInstitucionesResultado');
    const btn = document.getElementById('btnAnalizarInstituciones');

    if (!input || !input.files || !input.files.length) {
        mostrarErrorImportacionInstituciones('Seleccione un archivo Excel.');
        return;
    }

    error.style.display = 'none';
    success.style.display = 'none';
    resultado.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Analizando...';

    const formData = new FormData();
    formData.append('archivo', input.files[0]);

    fetch("{{ route('instituciones.importar.preview') }}", {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(function(response) {
        return response.json().then(function(body) {
            return { ok: response.ok, body: body };
        });
    })
    .then(function(result) {
        btn.disabled = false;
        btn.textContent = 'Analizar archivo';

        if (!result.ok || !result.body.success) {
            const message = result.body.message || 'No fue posible analizar el archivo.';
            mostrarErrorImportacionInstituciones(message);
            return;
        }

        const data = result.body;
        tokenImportacionInstituciones = data.token;

        document.getElementById('importTotal').textContent = data.total;
        document.getElementById('importNuevos').textContent = data.nuevos;
        document.getElementById('importActualizar').textContent = data.actualizar;
        document.getElementById('importSinCambios').textContent = data.sin_cambios || 0;
        document.getElementById('importErrores').textContent = data.errores;

        let html = '<table class="import-preview-table">';
        html += '<thead><tr>';
        html += '<th>Fila</th><th>Código</th><th>Tipo</th><th>RUC</th><th>Razón Social</th><th>Clínica/Hospital</th><th>Estado</th><th>Detalle</th>';
        html += '</tr></thead><tbody>';

        data.preview.forEach(function(item) {
            const tieneError = item.errores && item.errores.length;
            let claseFila = '';
            let estadoTexto = '<span>—</span>';

            if (item.estado === 'nuevo') {
                claseFila = 'import-row-new';
                estadoTexto = '<span class=\"import-status import-status-new\"><span class=\"import-status-dot\"></span>Nuevo</span>';
            } else if (item.estado === 'actualizar') {
                claseFila = 'import-row-update';
                estadoTexto = '<span class=\"import-status import-status-update\"><span class=\"import-status-dot\"></span>Actualizar</span>';
            } else if (item.estado === 'sin_cambios') {
                claseFila = 'import-row-nochange';
                estadoTexto = '<span class=\"import-status import-status-nochange\"><span class=\"import-status-dot\"></span>Sin cambios</span>';
            } else if (item.estado === 'error') {
                claseFila = 'import-row-error';
                estadoTexto = '<span class=\"import-status import-status-error\"><span class=\"import-status-dot\"></span>Error</span>';
            }

            if (tieneError) {
                claseFila = 'import-row-error';
                estadoTexto = '<span class=\"import-status import-status-error\"><span class=\"import-status-dot\"></span>Error</span>';
            }

            html += '<tr class="' + claseFila + '">';
            html += '<td>' + escapeHtmlInstitucion(item.fila ?? '') + '</td>';
            html += '<td>' + escapeHtmlInstitucion(item.codigo ?? '') + '</td>';
            html += '<td>' + escapeHtmlInstitucion(item.tipo ?? '') + '</td>';
            html += '<td>' + escapeHtmlInstitucion(item.ruc ?? '') + '</td>';
            html += '<td>' + escapeHtmlInstitucion(item.razon_social ?? '') + '</td>';
            html += '<td>' + escapeHtmlInstitucion(item.nombre_comercial ?? '') + '</td>';
            html += '<td>' + estadoTexto + '</td>';

            let detalle = '';

            if (tieneError) {
                detalle = escapeHtmlInstitucion(item.errores.join(' '));
            } else if (item.estado === 'actualizar' && item.cambios && Object.keys(item.cambios).length) {
                detalle = '<div class="import-changes">';

                Object.keys(item.cambios).forEach(function(campo) {
                    const cambio = item.cambios[campo];
                    const nombre = (cambio && cambio.nombre) ? cambio.nombre : campo;
                    const anterior = cambio && (cambio.anterior ?? cambio.antes ?? cambio.valor_anterior ?? '');
                    const nuevo = cambio && (cambio.nuevo ?? cambio.despues ?? cambio.valor_nuevo ?? '');

                    detalle += '<div class="import-change-item">';
                    detalle += '<strong>' + escapeHtmlInstitucion(nombre) + '</strong>: ';
                    detalle += '<span class="import-old-value">' + escapeHtmlInstitucion(anterior) + '</span>';
                    detalle += ' → ';
                    detalle += '<span class="import-new-value">' + escapeHtmlInstitucion(nuevo) + '</span>';
                    detalle += '</div>';
                });

                detalle += '</div>';
            } else if (item.estado === 'sin_cambios') {
                detalle = 'Sin modificaciones';
            } else if (item.estado === 'nuevo') {
                detalle = 'Se creará el registro';
            } else {
                detalle = '—';
            }

            html += '<td>' + detalle + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        const previewWrap = document.getElementById('importInstitucionesPreview');
        previewWrap.innerHTML = html;
        previewWrap.scrollLeft = 0;
        previewWrap.scrollTop = 0;
        resultado.style.display = 'block';

        const btnConfirmar = document.getElementById('btnConfirmarImportacionInstituciones');
        btnConfirmar.style.display = data.errores === 0 ? 'inline-flex' : 'none';

        if (data.errores > 0) {
            mostrarErrorImportacionInstituciones('Corrija los errores indicados en el archivo y vuelva a analizarlo. No se ha modificado la base de datos.');
        }
    })
    .catch(function(error) {
        console.error(error);
        btn.disabled = false;
        btn.textContent = 'Analizar archivo';
        mostrarErrorImportacionInstituciones('Ocurrió un error al analizar el archivo.');
    });
}

function confirmarImportacionInstituciones()
{
    if (!tokenImportacionInstituciones) {
        mostrarErrorImportacionInstituciones('No existe una vista previa válida para confirmar.');
        return;
    }

    if (!confirm('¿Desea confirmar la importación de las instituciones analizadas?')) {
        return;
    }

    const btn = document.getElementById('btnConfirmarImportacionInstituciones');
    btn.disabled = true;
    btn.textContent = 'Importando...';

    fetch("{{ route('instituciones.importar.confirmar') }}", {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ token: tokenImportacionInstituciones })
    })
    .then(function(response) {
        return response.json().then(function(body) {
            return { ok: response.ok, body: body };
        });
    })
    .then(function(result) {
        btn.disabled = false;
        btn.textContent = 'Confirmar importación';

        if (!result.ok || !result.body.success) {
            mostrarErrorImportacionInstituciones(result.body.message || 'No fue posible completar la importación.');
            return;
        }

        document.getElementById('importInstitucionesError').style.display = 'none';
        const success = document.getElementById('importInstitucionesSuccess');
        success.textContent = result.body.message + ' Nuevos: ' + result.body.nuevos + '. Actualizados: ' + result.body.actualizados + '.';
        success.style.display = 'block';

        setTimeout(function() {
            cerrarModal('modalImportarInstituciones');
            window.location.reload();
        }, 900);
    })
    .catch(function(error) {
        console.error(error);
        btn.disabled = false;
        btn.textContent = 'Confirmar importación';
        mostrarErrorImportacionInstituciones('Ocurrió un error al confirmar la importación.');
    });
}

function mostrarErrorImportacionInstituciones(mensaje)
{
    const box = document.getElementById('importInstitucionesError');
    if (!box) return;
    box.textContent = mensaje;
    box.style.display = 'block';
}

function escapeHtmlInstitucion(value)
{
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


</script>

@endsection