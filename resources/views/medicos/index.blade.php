@extends('layouts.app')

@section('title', 'Médicos')

@section('content')
@include('notas_entrega._estilos')

<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">Médicos</h1>
            <p class="ortomax-subtitle">Cirujanos que aparecen en las notas de entrega</p>
        </div>
        <form method="GET" class="ne-acciones">
            <input name="buscar" class="ortomax-form-control" placeholder="Buscar" value="{{ request('buscar') }}">
            <button class="ortomax-btn ortomax-btn-secondary">Buscar</button>
        </form>
    </div>

    @include('notas_entrega._errores')

    <form method="POST" action="{{ route('medicos.store') }}" class="ne-grid" style="margin-bottom:18px;">
        @csrf
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="nombres">Nombres *</label>
            <input id="nombres" name="nombres" class="ortomax-form-control" value="{{ old('nombres') }}" required>
        </div>
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="identificacion">Identificación</label>
            <input id="identificacion" name="identificacion" class="ortomax-form-control" value="{{ old('identificacion') }}">
        </div>
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="especialidad">Especialidad</label>
            <input id="especialidad" name="especialidad" class="ortomax-form-control" value="{{ old('especialidad', 'Traumatología') }}">
        </div>
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="telefono">Teléfono</label>
            <input id="telefono" name="telefono" class="ortomax-form-control" value="{{ old('telefono') }}">
        </div>
        <div style="align-self:end;"><button class="ortomax-btn ortomax-btn-primary">+ Registrar</button></div>
    </form>

    <div class="ortomax-table-container">
        <table class="ortomax-table">
            <thead><tr><th>Nombres</th><th>Identificación</th><th>Especialidad</th><th>Teléfono</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse($medicos as $m)
                    <tr>
                        <td>{{ $m->nombres }}</td>
                        <td>{{ $m->identificacion ?? '—' }}</td>
                        <td>{{ $m->especialidad ?? '—' }}</td>
                        <td>{{ $m->telefono ?? '—' }}</td>
                        <td>
                            <span class="ortomax-status {{ $m->activo ? 'ortomax-status-active' : 'ortomax-status-inactive' }}">
                                {{ $m->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="ortomax-empty">No hay médicos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="ortomax-pagination">{{ $medicos->links() }}</div>
</div>
@endsection
