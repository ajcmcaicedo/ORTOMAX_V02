@extends('layouts.app')

@section('title', 'Notas de Entrega')

@section('content')
@include('notas_entrega._estilos')

<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">Notas de Entrega</h1>
            <p class="ortomax-subtitle">Alistamiento, despacho y consumo de equipos quirúrgicos</p>
        </div>
        @can('create', App\Models\NotaEntrega::class)
            <a href="{{ route('notas_entrega.create') }}" class="ortomax-btn ortomax-btn-primary">+ Nueva nota</a>
        @endcan
    </div>

    <form method="GET" class="ne-grid" style="margin-bottom:16px;">
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="buscar">Número o institución</label>
            <input id="buscar" name="buscar" class="ortomax-form-control" value="{{ request('buscar') }}">
        </div>
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="estado">Estado</label>
            <select id="estado" name="estado" class="ortomax-form-control">
                <option value="">Todos</option>
                @foreach($estados as $e)
                    <option value="{{ $e->value }}" @selected(request('estado') === $e->value)>{{ $e->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="desde">Cirugía desde</label>
            <input type="date" id="desde" name="desde" class="ortomax-form-control" value="{{ request('desde') }}">
        </div>
        <div class="ortomax-form-group">
            <label class="ortomax-form-label" for="hasta">Cirugía hasta</label>
            <input type="date" id="hasta" name="hasta" class="ortomax-form-control" value="{{ request('hasta') }}">
        </div>
        <div class="ne-acciones" style="align-self:end;">
            <button class="ortomax-btn ortomax-btn-secondary">Filtrar</button>
            <a href="{{ route('notas_entrega.index') }}" class="ne-muted">Limpiar</a>
        </div>
    </form>

    <div class="ortomax-table-container">
        <table class="ortomax-table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cirugía</th>
                    <th>Institución</th>
                    <th>Tipo de cirugía</th>
                    <th>Instrumentador</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($notas as $nota)
                    <tr>
                        <td><strong>{{ $nota->numero }}</strong></td>
                        <td>{{ $nota->fecha_cirugia_programada?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $nota->institucion?->nombre_comercial ?: $nota->institucion?->razon_social }}</td>
                        <td>{{ $nota->tipoCirugia?->nombre ?? '—' }}</td>
                        <td>{{ $nota->instrumentador?->name ?? '—' }}</td>
                        <td><span class="ne-estado ne-estado-{{ $nota->estado->value }}">{{ $nota->estado->etiqueta() }}</span></td>
                        <td><a href="{{ route('notas_entrega.show', $nota) }}" class="ortomax-btn ortomax-btn-secondary">Abrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="ortomax-empty">No hay notas con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="ortomax-pagination">{{ $notas->links() }}</div>
</div>
@endsection
