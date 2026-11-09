@extends('layouts.app')

@section('title', 'Nueva Nota de Entrega')

@section('content')
@include('notas_entrega._estilos')

<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">Nueva Nota de Entrega</h1>
            <p class="ortomax-subtitle">Se crea en borrador con los datos disponibles; lo demás se completa después.</p>
        </div>
        <a href="{{ route('notas_entrega.index') }}" class="ortomax-btn ortomax-btn-secondary">← Volver</a>
    </div>

    @include('notas_entrega._errores')

    <form method="POST" action="{{ route('notas_entrega.store') }}">
        @csrf

        <div class="ne-seccion">
            <h2>Datos de la cirugía</h2>
            @include('notas_entrega._cabecera', ['soloLectura' => false])
        </div>

        @if(auth()->user()->tienePermiso('pacientes.ver'))
            <div class="ne-seccion">
                <h2>Paciente <span class="ne-muted">(opcional, puede confirmarse después)</span></h2>
                @include('notas_entrega._paciente', ['paciente' => null, 'soloLectura' => false])
            </div>
        @endif

        <div class="ne-acciones" style="margin-top:20px;">
            <button class="ortomax-btn ortomax-btn-primary">Crear borrador</button>
        </div>
    </form>
</div>
@endsection
