@extends('layouts.app')

@section('title', 'Nueva Institución')

@section('content')

<div class="container-fluid">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="mb-1">Nueva Institución</h1>

            <p class="text-muted mb-0">
                Registrar una nueva clínica, hospital o institución.
            </p>
        </div>

        <a href="{{ route('instituciones.index') }}"
           class="btn btn-outline-secondary">
            ← Volver
        </a>

    </div>


    {{-- ERRORES --}}
    @if($errors->any())

        <div class="alert alert-danger">

            <strong>Por favor revise los siguientes campos:</strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- FORMULARIO --}}
    <form method="POST"
          action="{{ route('instituciones.store') }}">

        @csrf

        <div class="card mb-4">

            <div class="card-header">
                <strong>Datos principales</strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- CÓDIGO --}}
                    <div class="col-md-3">

                        <label for="codigo"
                               class="form-label">
                            Código <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="codigo"
                            id="codigo"
                            value="{{ old('codigo') }}"
                            class="form-control"
                            maxlength="30"
                            required
                            autocomplete="off"
                        >

                        <small class="text-muted">
                            Código interno único de ORTOMAX.
                        </small>

                    </div>


                    {{-- TIPO --}}
                    <div class="col-md-3">

                        <label for="tipo"
                               class="form-label">
                            Tipo <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="tipo"
                            id="tipo"
                            value="{{ old('tipo') }}"
                            class="form-control"
                            maxlength="30"
                            required
                            placeholder="Ej.: CLINICA"
                        >

                    </div>


                    {{-- RUC --}}
                    <div class="col-md-3">

                        <label for="ruc"
                               class="form-label">
                            RUC
                        </label>

                        <input
                            type="text"
                            name="ruc"
                            id="ruc"
                            value="{{ old('ruc') }}"
                            class="form-control"
                            maxlength="30"
                            autocomplete="off"
                        >

                        <small class="text-muted">
                            El RUC puede repetirse.
                        </small>

                    </div>


                    {{-- CIUDAD --}}
                    <div class="col-md-3">

                        <label for="ciudad"
                               class="form-label">
                            Ciudad
                        </label>

                        <input
                            type="text"
                            name="ciudad"
                            id="ciudad"
                            value="{{ old('ciudad') }}"
                            class="form-control"
                            maxlength="100"
                        >

                    </div>


                    {{-- RAZÓN SOCIAL --}}
                    <div class="col-md-6">

                        <label for="razon_social"
                               class="form-label">
                            Razón Social <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="razon_social"
                            id="razon_social"
                            value="{{ old('razon_social') }}"
                            class="form-control"
                            maxlength="200"
                            required
                        >

                    </div>


                    {{-- NOMBRE COMERCIAL --}}
                    <div class="col-md-6">

                        <label for="nombre_comercial"
                               class="form-label">
                            Clínica / Hospital
                        </label>

                        <input
                            type="text"
                            name="nombre_comercial"
                            id="nombre_comercial"
                            value="{{ old('nombre_comercial') }}"
                            class="form-control"
                            maxlength="200"
                        >

                    </div>


                    {{-- DIRECCIÓN --}}
                    <div class="col-md-8">

                        <label for="direccion"
                               class="form-label">
                            Dirección
                        </label>

                        <input
                            type="text"
                            name="direccion"
                            id="direccion"
                            value="{{ old('direccion') }}"
                            class="form-control"
                            maxlength="255"
                        >

                    </div>


                    {{-- REGIONAL --}}
                    <div class="col-md-4">

                        <label for="regional_id"
                               class="form-label">
                            Regional
                        </label>

                        <select
                            name="regional_id"
                            id="regional_id"
                            class="form-select"
                        >

                            <option value="">
                                -- Sin regional --
                            </option>

                            @foreach($regionales as $regional)

                                <option
                                    value="{{ $regional->id }}"
                                    @selected(old('regional_id') == $regional->id)
                                >
                                    {{ $regional->codigo }}
                                    - 
                                    {{ $regional->nombre }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

        </div>


        {{-- DATOS DE CONTACTO --}}
        <div class="card mb-4">

            <div class="card-header">
                <strong>Datos de contacto</strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- TELÉFONO --}}
                    <div class="col-md-4">

                        <label for="telefono"
                               class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="telefono"
                            id="telefono"
                            value="{{ old('telefono') }}"
                            class="form-control"
                            maxlength="50"
                        >

                    </div>


                    {{-- EMAIL --}}
                    <div class="col-md-4">

                        <label for="email"
                               class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            class="form-control"
                            maxlength="150"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- CAMPOS RESERVADOS --}}
        <div class="card mb-4">

            <div class="card-header">
                <strong>Campos adicionales</strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label for="campo1"
                               class="form-label">
                            Campo 1
                        </label>

                        <input
                            type="text"
                            name="campo1"
                            id="campo1"
                            value="{{ old('campo1') }}"
                            class="form-control"
                            maxlength="255"
                        >

                    </div>


                    <div class="col-md-6">

                        <label for="campo2"
                               class="form-label">
                            Campo 2
                        </label>

                        <input
                            type="text"
                            name="campo2"
                            id="campo2"
                            value="{{ old('campo2') }}"
                            class="form-control"
                            maxlength="255"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- ESTADO --}}
        <div class="card mb-4">

            <div class="card-header">
                <strong>Estado</strong>
            </div>

            <div class="card-body">

                <div class="form-check">

                    <input
                        type="checkbox"
                        name="activo"
                        id="activo"
                        value="1"
                        class="form-check-input"
                        @checked(old('activo', true))
                    >

                    <label for="activo"
                           class="form-check-label">
                        Institución activa
                    </label>

                </div>

            </div>

        </div>


        {{-- BOTONES --}}
        <div class="d-flex justify-content-end gap-2 mb-5">

            <a href="{{ route('instituciones.index') }}"
               class="btn btn-secondary">
                Cancelar
            </a>

            <button type="submit"
                    class="btn btn-primary">
                Guardar Institución
            </button>

        </div>

    </form>

</div>

@endsection