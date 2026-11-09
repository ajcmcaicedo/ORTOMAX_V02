@extends('layouts.app')

@section('title', 'Plantilla ' . $tipo->nombre)

@section('content')
@include('notas_entrega._estilos')

<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">{{ $tipo->nombre }}</h1>
            <p class="ortomax-subtitle">Equipos que se sugieren al elegir esta cirugía en una nota</p>
        </div>
        <a href="{{ route('tipos_cirugia.index') }}" class="ortomax-btn ortomax-btn-secondary">← Tipos de cirugía</a>
    </div>

    @include('notas_entrega._errores')

    <form method="POST" action="{{ route('tipos_cirugia.update', $tipo) }}">
        @csrf
        @method('PUT')
        <div class="ne-grid">
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="codigo">Código</label>
                <input id="codigo" name="codigo" class="ortomax-form-control" value="{{ old('codigo', $tipo->codigo) }}" @disabled(!$puedeEditar)>
            </div>
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="nombre">Nombre</label>
                <input id="nombre" name="nombre" class="ortomax-form-control" value="{{ old('nombre', $tipo->nombre) }}" @disabled(!$puedeEditar)>
            </div>
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="region_anatomica">Región anatómica</label>
                <input id="region_anatomica" name="region_anatomica" class="ortomax-form-control" value="{{ old('region_anatomica', $tipo->region_anatomica) }}" @disabled(!$puedeEditar)>
            </div>
            <label style="display:flex; gap:8px; align-items:center; align-self:end;">
                <input type="checkbox" name="activo" value="1" @checked($tipo->activo) @disabled(!$puedeEditar)> Activo
            </label>
        </div>
        @if($puedeEditar)
            <button class="ortomax-btn ortomax-btn-primary" style="margin-top:12px;">Guardar</button>
        @endif
    </form>
</div>

<div class="ortomax-card">
    <h2 class="ortomax-title" style="font-size:20px;">Plantilla de equipos</h2>
    <p class="ne-muted">Cada fila es un modelo de equipo (código de referencia). Al crear la nota se propone la copia disponible (N.1, N.2…).</p>

    <div class="ortomax-table-container">
        <table class="ortomax-table">
            <thead>
                <tr><th>Orden</th><th>Referencia</th><th>Obligatorio</th><th>Observación</th>@if($puedeEditar)<th></th>@endif</tr>
            </thead>
            <tbody>
                @forelse($tipo->plantilla as $linea)
                    <tr>
                        @if($puedeEditar)
                            <td><input form="f{{ $linea->id }}" type="number" name="orden" min="0" value="{{ $linea->orden }}" class="ortomax-form-control" style="width:80px;"></td>
                            <td><strong>{{ $linea->codigo_referencia }}</strong></td>
                            <td><input form="f{{ $linea->id }}" type="checkbox" name="obligatorio" value="1" @checked($linea->obligatorio)></td>
                            <td><input form="f{{ $linea->id }}" name="observacion" value="{{ $linea->observacion }}" class="ortomax-form-control"></td>
                            <td class="ne-acciones">
                                <button form="f{{ $linea->id }}" class="ortomax-btn ortomax-btn-secondary">Guardar</button>
                                <form method="POST" action="{{ route('tipos_cirugia.equipos.destroy', [$tipo, $linea]) }}" onsubmit="return confirm('¿Quitar de la plantilla?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ortomax-btn ortomax-btn-danger">Quitar</button>
                                </form>
                            </td>
                        @else
                            <td>{{ $linea->orden }}</td>
                            <td><strong>{{ $linea->codigo_referencia }}</strong></td>
                            <td>{{ $linea->obligatorio ? 'Sí' : 'No' }}</td>
                            <td>{{ $linea->observacion }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="5" class="ortomax-empty">La plantilla está vacía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($puedeEditar)
        {{-- Formularios de cada fila (fuera de la tabla; los campos usan el atributo form) --}}
        @foreach($tipo->plantilla as $linea)
            <form method="POST" action="{{ route('tipos_cirugia.equipos.update', [$tipo, $linea]) }}" id="f{{ $linea->id }}">
                @csrf
                @method('PUT')
            </form>
        @endforeach

        <form method="POST" action="{{ route('tipos_cirugia.equipos.store', $tipo) }}" class="ne-grid ne-seccion">
            @csrf
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="codigo_referencia">Equipo (referencia)</label>
                <input id="codigo_referencia" name="codigo_referencia" list="referencias" class="ortomax-form-control" required
                       placeholder="Escriba para buscar…" value="{{ old('codigo_referencia') }}">
                <datalist id="referencias">
                    @foreach($referencias as $r)
                        <option value="{{ $r->codigo_referencia }}">{{ $r->nombre }} ({{ $r->copias }} copia(s))</option>
                    @endforeach
                </datalist>
            </div>
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="observacion">Observación</label>
                <input id="observacion" name="observacion" class="ortomax-form-control" value="{{ old('observacion') }}">
            </div>
            <label style="display:flex; gap:8px; align-items:center; align-self:end;">
                <input type="checkbox" name="obligatorio" value="1" checked> Obligatorio
            </label>
            <div style="align-self:end;"><button class="ortomax-btn ortomax-btn-primary">+ Agregar a la plantilla</button></div>
        </form>
    @endif
</div>
@endsection
