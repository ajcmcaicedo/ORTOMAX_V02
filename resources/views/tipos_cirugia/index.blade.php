@extends('layouts.app')

@section('title', 'Tipos de cirugía')

@section('content')
@include('notas_entrega._estilos')

<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">Tipos de cirugía</h1>
            <p class="ortomax-subtitle">Plantillas de equipos sugeridos para cada cirugía</p>
        </div>
    </div>

    @include('notas_entrega._errores')

    @can('create', App\Models\TipoCirugia::class)
        <form method="POST" action="{{ route('tipos_cirugia.store') }}" class="ne-grid" style="margin-bottom:18px;">
            @csrf
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="codigo">Código *</label>
                <input id="codigo" name="codigo" class="ortomax-form-control" value="{{ old('codigo') }}" placeholder="RADIO-DISTAL" required>
            </div>
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="nombre">Nombre *</label>
                <input id="nombre" name="nombre" class="ortomax-form-control" value="{{ old('nombre') }}" placeholder="Radio distal" required>
            </div>
            <div class="ortomax-form-group">
                <label class="ortomax-form-label" for="region_anatomica">Región anatómica</label>
                <input id="region_anatomica" name="region_anatomica" class="ortomax-form-control" value="{{ old('region_anatomica') }}" placeholder="Miembro superior">
            </div>
            <div style="align-self:end;">
                <button class="ortomax-btn ortomax-btn-primary">+ Crear tipo</button>
            </div>
        </form>
    @endcan

    <div class="ortomax-table-container">
        <table class="ortomax-table">
            <thead>
                <tr><th>Código</th><th>Nombre</th><th>Región</th><th>Equipos en plantilla</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($tipos as $tipo)
                    <tr>
                        <td><strong>{{ $tipo->codigo }}</strong></td>
                        <td>{{ $tipo->nombre }}</td>
                        <td>{{ $tipo->region_anatomica ?? '—' }}</td>
                        <td>{{ $tipo->plantilla_count }}</td>
                        <td>
                            <span class="ortomax-status {{ $tipo->activo ? 'ortomax-status-active' : 'ortomax-status-inactive' }}">
                                {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td><a href="{{ route('tipos_cirugia.edit', $tipo) }}" class="ortomax-btn ortomax-btn-secondary">Plantilla</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ortomax-empty">No hay tipos de cirugía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
