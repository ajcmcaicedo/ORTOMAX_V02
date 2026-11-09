{{--
    Campos de cabecera de la nota.
    Variables: $nota, $soloLectura (bool), catálogos del controlador.
--}}
@php($ro = $soloLectura ?? false)

<div class="ne-grid">
    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="institucion_id">Institución *</label>
        <select id="institucion_id" name="institucion_id" class="ortomax-form-control" required @disabled($ro)>
            <option value="">Seleccione…</option>
            @foreach($instituciones as $i)
                <option value="{{ $i->id }}" @selected(old('institucion_id', $nota->institucion_id) == $i->id)>
                    {{ $i->nombre_comercial ?: $i->razon_social }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="fecha_cirugia_programada">Fecha y hora de cirugía</label>
        <input type="datetime-local" id="fecha_cirugia_programada" name="fecha_cirugia_programada" class="ortomax-form-control"
               value="{{ old('fecha_cirugia_programada', $nota->fecha_cirugia_programada?->format('Y-m-d\TH:i')) }}" @disabled($ro)>
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="tipo_cirugia_id">Tipo de cirugía</label>
        <select id="tipo_cirugia_id" name="tipo_cirugia_id" class="ortomax-form-control" @disabled($ro)>
            <option value="">Sin definir</option>
            @foreach($tiposCirugia as $t)
                <option value="{{ $t->id }}" @selected(old('tipo_cirugia_id', $nota->tipo_cirugia_id) == $t->id)>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="medico_id">Médico</label>
        <select id="medico_id" name="medico_id" class="ortomax-form-control" @disabled($ro)>
            <option value="">Sin definir</option>
            @foreach($medicos as $m)
                <option value="{{ $m->id }}" @selected(old('medico_id', $nota->medico_id) == $m->id)>
                    {{ $m->nombres }}{{ $m->especialidad ? ' — ' . $m->especialidad : '' }}
                </option>
            @endforeach
        </select>
        @unless($ro)
            <a href="{{ route('medicos.index') }}" target="_blank" class="ne-muted">+ Registrar médico</a>
        @endunless
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="bodega_origen_id">Bodega de despacho *</label>
        <select id="bodega_origen_id" name="bodega_origen_id" class="ortomax-form-control" required @disabled($ro)>
            <option value="">Seleccione…</option>
            @foreach($bodegas as $b)
                <option value="{{ $b->id }}" @selected(old('bodega_origen_id', $nota->bodega_origen_id) == $b->id)>{{ $b->codigo }} — {{ $b->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="regional_id">Regional</label>
        <select id="regional_id" name="regional_id" class="ortomax-form-control" @disabled($ro)>
            <option value="">La de la institución</option>
            @foreach($regionales as $r)
                <option value="{{ $r->id }}" @selected(old('regional_id', $nota->regional_id) == $r->id)>{{ $r->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="instrumentador_id">Instrumentador</label>
        <select id="instrumentador_id" name="instrumentador_id" class="ortomax-form-control" @disabled($ro)>
            <option value="">Sin asignar</option>
            @foreach($instrumentadores as $u)
                <option value="{{ $u->id }}" @selected(old('instrumentador_id', $nota->instrumentador_id) == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="preparador_id">Preparador</label>
        <select id="preparador_id" name="preparador_id" class="ortomax-form-control" @disabled($ro)>
            <option value="">Sin asignar</option>
            @foreach($preparadores as $u)
                <option value="{{ $u->id }}" @selected(old('preparador_id', $nota->preparador_id) == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="ortomax-form-group" style="margin-top:14px;">
    <label class="ortomax-form-label" for="observaciones">Observaciones</label>
    <textarea id="observaciones" name="observaciones" class="ortomax-form-control" rows="2"
              placeholder="Ej.: Pedido por WhatsApp del gerente. Paciente por confirmar." @disabled($ro)>{{ old('observaciones', $nota->observaciones) }}</textarea>
</div>
