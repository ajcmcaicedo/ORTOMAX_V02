{{-- Variables: $paciente (array|null), $soloLectura (bool) --}}
@php($ro = $soloLectura ?? false)
@php($p = $paciente ?? [])

<div class="ne-grid">
    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="paciente_identificacion">Identificación</label>
        <input id="paciente_identificacion" name="paciente[identificacion]" class="ortomax-form-control" autocomplete="off"
               value="{{ old('paciente.identificacion', $p['identificacion'] ?? '') }}" @disabled($ro)>
    </div>
    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="paciente_nombres">Nombres del paciente</label>
        <input id="paciente_nombres" name="paciente[nombres]" class="ortomax-form-control" autocomplete="off"
               value="{{ old('paciente.nombres', $p['nombres'] ?? '') }}" @disabled($ro)>
    </div>
    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="paciente_historia">Historia clínica</label>
        <input id="paciente_historia" name="paciente[historia_clinica]" class="ortomax-form-control" autocomplete="off"
               value="{{ old('paciente.historia_clinica', $p['historia_clinica'] ?? '') }}" @disabled($ro)>
    </div>
    <div class="ortomax-form-group">
        <label class="ortomax-form-label" for="paciente_aseguradora">Aseguradora / convenio</label>
        <input id="paciente_aseguradora" name="paciente[aseguradora]" class="ortomax-form-control"
               value="{{ old('paciente.aseguradora', $p['aseguradora'] ?? '') }}" @disabled($ro)>
    </div>
</div>
<label style="display:flex; gap:8px; align-items:center; margin-top:10px;">
    <input type="hidden" name="paciente[confirmado]" value="0">
    <input type="checkbox" name="paciente[confirmado]" value="1" @checked(old('paciente.confirmado', $p['confirmado'] ?? false)) @disabled($ro)>
    Datos del paciente confirmados
</label>
<p class="ne-muted">Datos sensibles: se guardan cifrados y cada consulta queda registrada en la bitácora.</p>
