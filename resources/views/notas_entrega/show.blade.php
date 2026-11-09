@extends('layouts.app')

@section('title', 'Nota ' . $nota->numero)

@section('content')
@include('notas_entrega._estilos')

@php
    $puedeEditar = Gate::allows('update', $nota);
    $puedeAlistar = Gate::allows('alistar', $nota);
    $puedePaciente = Gate::allows('editarPaciente', $nota);
@endphp

<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">
                Nota {{ $nota->numero }}
                <span class="ne-estado ne-estado-{{ $nota->estado->value }}">{{ $nota->estado->etiqueta() }}</span>
            </h1>
            <p class="ortomax-subtitle">
                {{ $nota->institucion?->nombre_comercial ?: $nota->institucion?->razon_social }}
                @if($nota->fecha_cirugia_programada) · {{ $nota->fecha_cirugia_programada->format('d/m/Y H:i') }} @endif
                @if($nota->tipoCirugia) · {{ $nota->tipoCirugia->nombre }} @endif
            </p>
        </div>

        <div class="ne-acciones">
            @can('iniciarAlistamiento', $nota)
                <form method="POST" action="{{ route('notas_entrega.iniciar_alistamiento', $nota) }}">
                    @csrf
                    <input type="hidden" name="version" value="{{ $nota->version }}">
                    <button class="ortomax-btn ortomax-btn-primary" @disabled($nota->lineasEquipo->isEmpty())
                            title="{{ $nota->lineasEquipo->isEmpty() ? 'Agregue al menos un equipo' : '' }}">
                        Iniciar alistamiento
                    </button>
                </form>
            @endcan
            @can('anular', $nota)
                <button type="button" class="ortomax-btn ortomax-btn-danger" onclick="abrirModal('modalAnular')">Anular</button>
            @endcan
            <a href="{{ route('notas_entrega.index') }}" class="ortomax-btn ortomax-btn-secondary">← Notas</a>
        </div>
    </div>

    @include('notas_entrega._errores')

    {{-- DATOS DE LA CIRUGÍA --}}
    <div class="ne-seccion">
        <h2>Datos de la cirugía</h2>
        <form method="POST" action="{{ route('notas_entrega.update', $nota) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="version" value="{{ $nota->version }}">
            @include('notas_entrega._cabecera', ['soloLectura' => !$puedeEditar])
            @if($puedeEditar)
                <div class="ne-acciones" style="margin-top:12px;">
                    <button class="ortomax-btn ortomax-btn-primary">Guardar cambios</button>
                    @if(!$nota->tipo_cirugia_id)
                        <span class="ne-muted">Elija el tipo de cirugía para recibir sugerencias de equipos.</span>
                    @endif
                </div>
            @endif
        </form>
    </div>

    {{-- PACIENTE --}}
    @if($puedePaciente || $paciente !== null)
        <div class="ne-seccion">
            <h2>Paciente</h2>
            <form method="POST" action="{{ route('notas_entrega.paciente', $nota) }}">
                @csrf
                @method('PUT')
                @include('notas_entrega._paciente', ['paciente' => $paciente, 'soloLectura' => !$puedePaciente])
                @if($puedePaciente)
                    <button class="ortomax-btn ortomax-btn-secondary" style="margin-top:8px;">Guardar paciente</button>
                @endif
            </form>
        </div>
    @endif
</div>

{{-- EQUIPOS --}}
<div class="ortomax-card">
    <div class="ortomax-module-header">
        <div>
            <h2 class="ortomax-title" style="font-size:20px;">Equipos de la nota</h2>
            <p class="ortomax-subtitle">{{ $nota->lineasEquipo->count() }} equipo(s)</p>
        </div>
        @if($puedeAlistar && $nota->tipo_cirugia_id)
            <button type="button" class="ortomax-btn ortomax-btn-secondary" id="btnSugerencias">
                Sugerir equipos para {{ $nota->tipoCirugia->nombre }}
            </button>
        @endif
    </div>

    <div id="panelSugerencias" hidden class="ne-seccion" style="margin-top:0;"></div>

    <div class="ortomax-table-container">
        <table class="ortomax-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Equipo</th>
                    <th>Alistamiento</th>
                    @if($puedeAlistar)<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($nota->lineasEquipo as $linea)
                    <tr>
                        <td><strong>{{ $linea->codigo }}</strong></td>
                        <td>{{ $linea->descripcion }}</td>
                        <td><span class="ne-etiqueta">Pendiente</span></td>
                        @if($puedeAlistar)
                            <td>
                                <form method="POST" action="{{ route('notas_entrega.equipos.destroy', [$nota, $linea]) }}"
                                      onsubmit="return confirmarRetiro(this, {{ $nota->estado === App\Enums\EstadoNotaEntrega::BORRADOR ? 'false' : 'true' }});">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="motivo">
                                    <button class="ortomax-btn ortomax-btn-danger">Quitar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="4" class="ortomax-empty">Aún no hay equipos. Use la sugerencia o busque por código o nombre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($puedeAlistar)
        <div class="ne-seccion">
            <label class="ortomax-form-label" for="buscarEquipo">Agregar equipo</label>
            <input id="buscarEquipo" class="ortomax-form-control" placeholder="Código o nombre (ej.: P13NAC5, ARIX, BATERÍA)" autocomplete="off">
            <div id="resultadosEquipo" class="ne-resultados" hidden></div>
        </div>
    @endif
</div>

{{-- HISTORIAL --}}
<div class="ortomax-card">
    <h2 class="ortomax-title" style="font-size:18px;">Historial</h2>
    <ul class="ne-historial">
        @forelse($historial as $h)
            <li>
                <span class="ne-muted">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                — {{ $usuarios[$h->usuario_id] ?? 'Sistema' }}:
                <strong>{{ str_replace('_', ' ', strtolower($h->evento)) }}</strong>
                @if($h->evento === 'NOTA_ESTADO')
                    ({{ $h->valores_anteriores['estado'] ?? '' }} → {{ $h->valores_nuevos['estado'] ?? '' }})
                @elseif(isset($h->valores_nuevos['codigo']) || isset($h->valores_anteriores['codigo']))
                    {{ $h->valores_nuevos['codigo'] ?? $h->valores_anteriores['codigo'] }}
                @endif
                @if($h->motivo) · <em>{{ $h->motivo }}</em> @endif
            </li>
        @empty
            <li class="ne-muted">Sin movimientos.</li>
        @endforelse
    </ul>
</div>

{{-- MODAL ANULAR --}}
@can('anular', $nota)
    <div class="ortomax-modal" id="modalAnular">
        <div class="ortomax-modal-content">
            <form method="POST" action="{{ route('notas_entrega.anular', $nota) }}">
                @csrf
                <div class="ortomax-modal-header">
                    <h3 class="ortomax-modal-title">Anular nota {{ $nota->numero }}</h3>
                    <button type="button" class="ortomax-modal-close" onclick="cerrarModal('modalAnular')">×</button>
                </div>
                <div class="ortomax-modal-body">
                    <label class="ortomax-form-label" for="motivoAnular">Motivo (obligatorio)</label>
                    <textarea id="motivoAnular" name="motivo" class="ortomax-form-control" required minlength="5"></textarea>
                </div>
                <div class="ortomax-modal-footer">
                    <button type="button" class="ortomax-btn ortomax-btn-secondary" onclick="cerrarModal('modalAnular')">Cancelar</button>
                    <button class="ortomax-btn ortomax-btn-danger">Anular nota</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@if($puedeAlistar)
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const urls = {
        buscar: @json(route('notas_entrega.equipos.buscar', $nota)),
        agregar: @json(route('notas_entrega.equipos.store', $nota)),
        sugerencias: @json(route('notas_entrega.sugerencias', $nota)),
        aceptar: @json(route('notas_entrega.sugerencias.aceptar', $nota)),
    };

    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    async function pedir(url, opciones = {}) {
        const r = await fetch(url, {
            ...opciones,
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
        });
        const datos = await r.json().catch(() => ({}));
        if (!r.ok) {
            const errores = datos.errors ? Object.values(datos.errors).flat().join(' ') : '';
            throw new Error(datos.message && !errores ? datos.message : (errores || 'Error al procesar la solicitud.'));
        }
        return datos;
    }

    window.confirmarRetiro = function (form, exigeMotivo) {
        if (!exigeMotivo) {
            return confirm('¿Quitar este equipo de la nota?');
        }
        const motivo = prompt('Motivo para retirar el equipo (obligatorio):');
        if (!motivo || !motivo.trim()) {
            return false;
        }
        form.querySelector('[name=motivo]').value = motivo.trim();
        return true;
    };

    /* ---------- Búsqueda manual ---------- */
    const input = document.getElementById('buscarEquipo');
    const caja = document.getElementById('resultadosEquipo');
    let temporizador;

    input.addEventListener('input', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscar, 300);
    });

    async function buscar() {
        const q = input.value.trim();
        if (q.length < 2) { caja.hidden = true; return; }
        try {
            const { equipos } = await pedir(urls.buscar + '?q=' + encodeURIComponent(q));
            caja.hidden = false;
            caja.innerHTML = equipos.length ? equipos.map((e) => `
                <div>
                    <span><strong>${esc(e.codigo)}</strong> ${esc(e.nombre)}
                        ${e.motivo ? `<br><span class="ne-muted">${esc(e.motivo)}</span>` : ''}
                        ${e.en_borradores.length ? `<br><span class="ne-muted">También en borrador: ${esc(e.en_borradores.join(', '))}</span>` : ''}
                    </span>
                    ${e.en_nota ? '<span class="ne-ok">En la nota</span>'
                        : e.disponible ? `<button type="button" class="ortomax-btn ortomax-btn-primary" data-agregar="${e.id}">Agregar</button>`
                        : '<span class="ne-etiqueta">No disponible</span>'}
                </div>`).join('') : '<div class="ne-muted">Sin resultados.</div>';
        } catch (err) {
            caja.hidden = false;
            caja.innerHTML = `<div class="ne-error">${esc(err.message)}</div>`;
        }
    }

    caja.addEventListener('click', async (ev) => {
        const id = ev.target.dataset.agregar;
        if (!id) return;
        ev.target.disabled = true;
        try {
            await pedir(urls.agregar, { method: 'POST', body: JSON.stringify({ equipo_id: Number(id) }) });
            location.reload();
        } catch (err) {
            ev.target.disabled = false;
            alert(err.message);
        }
    });

    /* ---------- Sugerencias por tipo de cirugía ---------- */
    const btn = document.getElementById('btnSugerencias');
    const panel = document.getElementById('panelSugerencias');

    btn?.addEventListener('click', async () => {
        panel.hidden = false;
        panel.innerHTML = '<p class="ne-muted">Consultando disponibilidad…</p>';
        try {
            const { sugerencias } = await pedir(urls.sugerencias);
            pintarSugerencias(sugerencias);
        } catch (err) {
            panel.innerHTML = `<div class="ne-error">${esc(err.message)}</div>`;
        }
    });

    function opcion(s, e, marcada) {
        return `<label><input type="radio" name="ref-${s.plantilla_id}" value="${e.equipo_id}" ${marcada ? 'checked' : ''}>
            <strong>${esc(e.codigo)}</strong> ${esc(e.nombre)}
            ${e.misma_bodega ? '' : '<span class="ne-etiqueta">Otra bodega</span>'}
            ${e.en_borradores.length ? `<span class="ne-muted">(también en borrador ${esc(e.en_borradores.map(o => o.numero).join(', '))})</span>` : ''}
        </label>`;
    }

    function pintarSugerencias(lista) {
        if (!lista.length) {
            panel.innerHTML = '<div class="ne-aviso">La plantilla de este tipo de cirugía no tiene equipos configurados.</div>';
            return;
        }

        panel.innerHTML = '<p class="ne-muted">Revise y confirme. Nada se agrega hasta que pulse «Agregar seleccionados».</p>' + lista.map((s) => {
            const ocupados = s.no_disponibles.map((e) => `<div class="ne-aviso">${esc(e.codigo)}: ${esc(e.motivo)}</div>`).join('');
            let cuerpo = '';

            if (s.estado === 'YA_EN_NOTA') {
                cuerpo = `<span class="ne-ok">✓ Ya en la nota: ${esc(s.propuesto.codigo)}</span>`;
            } else if (s.estado === 'DISPONIBLE') {
                cuerpo = `<label><input type="checkbox" data-incluir="${s.plantilla_id}" ${s.obligatorio ? 'checked' : ''}> Incluir</label>`
                    + opcion(s, s.propuesto, true)
                    + s.otras_disponibles.map((e) => opcion(s, e, false)).join('');
            } else if (s.estado === 'SIN_DISPONIBLE') {
                cuerpo = '<div class="ne-error">No hay copias disponibles de este equipo.</div>';
            } else {
                cuerpo = '<div class="ne-error">No existen equipos con esta referencia.</div>';
            }

            return `<div class="ne-sugerencia">
                <div class="ne-sugerencia-cabecera">
                    <strong>${esc(s.codigo_referencia)}</strong>
                    <span class="ne-etiqueta ${s.obligatorio ? 'ne-etiqueta-obligatorio' : ''}">${s.obligatorio ? 'Obligatorio' : 'Opcional'}</span>
                </div>
                ${s.observacion ? `<div class="ne-muted">${esc(s.observacion)}</div>` : ''}
                ${cuerpo}${ocupados}
            </div>`;
        }).join('') + '<button type="button" class="ortomax-btn ortomax-btn-primary" id="btnAceptar">Agregar seleccionados</button>';

        document.getElementById('btnAceptar').addEventListener('click', aceptar);
    }

    async function aceptar(ev) {
        const equipos = [...panel.querySelectorAll('[data-incluir]:checked')]
            .map((c) => panel.querySelector(`input[name="ref-${c.dataset.incluir}"]:checked`))
            .filter(Boolean)
            .map((r) => Number(r.value));

        if (!equipos.length) { alert('No seleccionó ningún equipo.'); return; }

        ev.target.disabled = true;
        try {
            const r = await pedir(urls.aceptar, { method: 'POST', body: JSON.stringify({ equipos }) });
            if (r.rechazados.length) {
                alert('Algunos equipos no se agregaron:\n' + r.rechazados.map((x) => '• ' + x.motivo).join('\n'));
            }
            location.reload();
        } catch (err) {
            ev.target.disabled = false;
            alert(err.message);
        }
    }
})();
</script>
@endif
@endsection
