@extends('layouts.app')

@section('title', 'Importacion de Lotes')

@section('content')

<div class="ortomax-card">

    <div class="ortomax-module-header">
        <div>
            <h1 class="ortomax-title">Importacion y Actualizacion de Lotes</h1>
            <p class="ortomax-subtitle">
                Registrar nuevos lotes y actualizar informacion existente mediante Excel.
                Esta operacion no modifica las existencias del inventario.
            </p>
        </div>

        <div>
            <a href="{{ route('productos.index') }}"
               class="ortomax-btn ortomax-btn-secondary"
               style="text-decoration:none;">
                Volver a Productos
            </a>
        </div>
    </div>

    {{-- FORMULARIO DE IMPORTACION --}}
    <div style="margin-top:25px;">

        <form id="formImportarLotes">
            @csrf

            <div style="margin-bottom:15px;">
                <label class="ortomax-form-label">Archivo Excel</label>

                <input type="file"
                       id="archivoLotes"
                       name="archivo"
                       class="ortomax-form-control"
                       accept=".xlsx,.xls"
                       required>

                <small style="display:block;margin-top:7px;color:#6c757d;">
                    Formatos XLSX y XLS. Maximo 10 MB.
                </small>
            </div>

            <div style="
                display:flex;
                align-items:center;
                justify-content:flex-end;
                gap:12px;
                flex-wrap:wrap;
            ">
                <a href="{{ route('lotes.importar.plantilla') }}"
                   class="ortomax-btn ortomax-btn-secondary"
                   style="text-decoration:none;white-space:nowrap;">
                    Descargar plantilla
                </a>

                <button type="submit"
                        id="btnAnalizarLotes"
                        class="ortomax-btn ortomax-btn-primary">
                    Analizar archivo
                </button>
            </div>
        </form>

        {{-- EXPORTACION DE LOTES --}}
        <div style="
            margin-top:22px;
            padding:18px;
            background:#f8fafc;
            border:1px solid #dce3ea;
            border-radius:10px;
        ">
            <div style="margin-bottom:12px;">
                <strong style="font-size:15px;">
                    Exportacion de inventario de lotes
                </strong>

                <p class="ortomax-subtitle" style="margin-top:5px;">
                    Generar un archivo Excel con todos los lotes registrados.
                </p>
            </div>

            <button type="button"
                    id="btnExportarLotes"
                    class="ortomax-btn ortomax-btn-primary">
                Exportar lotes a Excel
            </button>

            {{-- BARRA DE PROGRESO --}}
            <div id="progresoExportacion"
                 style="display:none;margin-top:18px;">

                <div style="
                    display:flex;
                    justify-content:space-between;
                    margin-bottom:8px;
                ">
                    <strong id="textoProgreso">Preparando archivo...</strong>
                    <strong id="porcentajeProgreso">0%</strong>
                </div>

                <div style="
                    height:15px;
                    background:#e9ecef;
                    border-radius:10px;
                    overflow:hidden;
                ">
                    <div id="barraProgreso"
                         style="
                            height:100%;
                            width:0%;
                            background:#198754;
                            transition:width .3s;
                         ">
                    </div>
                </div>

                <small id="detalleProgreso"
                       style="display:block;margin-top:8px;color:#6c757d;">
                    Esperando...
                </small>

                <div id="descargaLista" style="display:none;margin-top:15px;">
                    <a id="enlaceDescarga"
                       class="ortomax-btn ortomax-btn-primary"
                       style="text-decoration:none;">
                        Descargar Excel generado
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- MENSAJES --}}
    <div id="mensajeLotes"
         style="display:none;margin-top:20px;padding:15px;border-radius:8px;">
    </div>

    {{-- RESUMEN --}}
    <div id="resumenLotes" style="display:none;margin-top:25px;">

        <h3 style="margin-bottom:15px;">Resultado del analisis</h3>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">

            <div class="ortomax-card" style="padding:18px;">
                <div class="ortomax-subtitle">TOTAL</div>
                <div id="totalLotes" style="font-size:28px;font-weight:bold;">0</div>
            </div>

            <div class="ortomax-card" style="padding:18px;">
                <div class="ortomax-subtitle">NUEVOS</div>
                <div id="nuevosLotes" style="font-size:28px;font-weight:bold;color:#198754;">0</div>
            </div>

            <div class="ortomax-card" style="padding:18px;">
                <div class="ortomax-subtitle">ACTUALIZAR</div>
                <div id="actualizarLotes" style="font-size:28px;font-weight:bold;color:#b7791f;">0</div>
            </div>

            <div class="ortomax-card" style="padding:18px;">
                <div class="ortomax-subtitle">SIN CAMBIOS</div>
                <div id="sinCambiosLotes" style="font-size:28px;font-weight:bold;color:#64748b;">0</div>
            </div>

            <div class="ortomax-card" style="padding:18px;">
                <div class="ortomax-subtitle">ERRORES</div>
                <div id="erroresLotes" style="font-size:28px;font-weight:bold;color:#dc3545;">0</div>
            </div>

        </div>

        <div style="margin-top:15px;padding:13px;background:#fff3cd;color:#664d03;border-radius:8px;">
            <strong>Importante:</strong>
            Las celdas vacias de los campos opcionales pueden borrar su informacion
            existente. Revise los cambios antes de confirmar.
        </div>

    </div>

    {{-- VISTA PREVIA --}}
    <div id="contenedorPreviewLotes" style="display:none;margin-top:30px;">

        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:15px;">

            <div>
                <h3 style="margin:0;">Vista previa de Lotes</h3>
                <p class="ortomax-subtitle" style="margin-top:5px;">
                    Se muestran hasta 100 registros. El analisis y la importacion incluyen todos los registros.
                </p>
            </div>

            <button type="button"
                    id="btnConfirmarLotes"
                    class="ortomax-btn ortomax-btn-primary"
                    disabled>
                Confirmar importacion
            </button>

        </div>

        <div class="ortomax-table-container">
            <table class="ortomax-table">
                <thead>
                    <tr>
                        <th>FILA</th>
                        <th>CODIGO PRODUCTO</th>
                        <th>NUMERO LOTE</th>
                        <th>FECHA FABRICACION</th>
                        <th>FECHA VENCIMIENTO</th>
                        <th>REGISTRO SANITARIO</th>
                        <th>ESTADO</th>
                        <th>RESULTADO / CAMBIOS</th>
                    </tr>
                </thead>

                <tbody id="tablaPreviewLotes"></tbody>
            </table>
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('formImportarLotes');
    const archivo = document.getElementById('archivoLotes');
    const btnAnalizar = document.getElementById('btnAnalizarLotes');
    const btnConfirmar = document.getElementById('btnConfirmarLotes');

    const mensaje = document.getElementById('mensajeLotes');
    const resumen = document.getElementById('resumenLotes');
    const contenedorPreview = document.getElementById('contenedorPreviewLotes');
    const tablaPreview = document.getElementById('tablaPreviewLotes');

    let tokenImportacion = null;
    let resumenActual = null;

    function mostrarMensaje(texto, tipo = 'error') {
        mensaje.style.display = 'block';
        mensaje.textContent = texto;

        if (tipo === 'success') {
            mensaje.style.background = '#d1e7dd';
            mensaje.style.color = '#0f5132';
        } else {
            mensaje.style.background = '#f8d7da';
            mensaje.style.color = '#842029';
        }
    }

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function mostrarValor(valor) {
        if (valor === null || valor === undefined || String(valor).trim() === '') {
            return 'VACIO';
        }

        return String(valor);
    }

    function etiquetaEstado(estado) {
        const configuracion = {
            nuevo: ['NUEVO', '#d1e7dd', '#0f5132'],
            actualizar: ['ACTUALIZAR', '#fff3cd', '#664d03'],
            sin_cambios: ['SIN CAMBIOS', '#e2e3e5', '#41464b'],
            error: ['ERROR', '#f8d7da', '#842029']
        };

        const item = configuracion[estado] || [
            estado || 'DESCONOCIDO',
            '#e2e3e5',
            '#41464b'
        ];

        return `<span style="
            display:inline-block;
            padding:5px 9px;
            border-radius:5px;
            background:${item[1]};
            color:${item[2]};
            font-size:12px;
            font-weight:bold;
            white-space:nowrap;">
            ${escaparHtml(item[0])}
        </span>`;
    }

    function mostrarCambios(cambios) {

        if (!cambios || Object.keys(cambios).length === 0) {
            return '';
        }

        const nombres = {
            fecha_fabricacion: 'Fecha fabricacion',
            fecha_vencimiento: 'Fecha vencimiento',
            registro_sanitario: 'Registro sanitario',
            estado: 'Estado',
            observaciones: 'Observaciones'
        };

        let html = `
            <div style="min-width:260px;">
                <strong style="display:block;margin-bottom:8px;">
                    Modificaciones detectadas:
                </strong>
        `;

        Object.entries(cambios).forEach(([campo, valores]) => {

            const nombre = nombres[campo] || campo;

            const anterior = mostrarValor(valores.actual);
            const nuevo = mostrarValor(valores.nuevo);

            html += `
                <div style="
                    padding:8px;
                    margin-bottom:7px;
                    background:#f8f9fa;
                    border-left:3px solid #d69e2e;
                    border-radius:4px;">

                    <strong>${escaparHtml(nombre)}</strong>

                    <div style="font-size:12px;margin-top:4px;">
                        <span style="color:#b02a37;">
                            Anterior: ${escaparHtml(anterior)}
                        </span>
                    </div>

                    <div style="font-size:12px;margin-top:3px;">
                        <span style="color:#198754;">
                            Nuevo: ${escaparHtml(nuevo)}
                        </span>
                    </div>

                </div>
            `;
        });

        html += '</div>';

        return html;
    }

    function mostrarErrores(errores) {

        if (!Array.isArray(errores) || errores.length === 0) {
            return '';
        }

        return `
            <div style="color:#842029;min-width:200px;">
                <strong>Errores:</strong>
                <ul style="padding-left:18px;margin:5px 0;">
                    ${errores.map(error => `<li>${escaparHtml(error)}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    function actualizarResumen(data) {

        document.getElementById('totalLotes').textContent = data.total ?? 0;
        document.getElementById('nuevosLotes').textContent = data.nuevos ?? 0;
        document.getElementById('actualizarLotes').textContent = data.actualizar ?? 0;
        document.getElementById('sinCambiosLotes').textContent = data.sin_cambios ?? 0;
        document.getElementById('erroresLotes').textContent = data.errores ?? 0;

    }

    form.addEventListener('submit', async function (event) {

        event.preventDefault();

        if (!archivo.files.length) {
            mostrarMensaje('Seleccione un archivo Excel.');
            return;
        }

        const formData = new FormData();
        formData.append('archivo', archivo.files[0]);

        btnAnalizar.disabled = true;
        btnAnalizar.textContent = 'Analizando...';

        mensaje.style.display = 'none';
        resumen.style.display = 'none';
        contenedorPreview.style.display = 'none';

        btnConfirmar.disabled = true;
        tokenImportacion = null;
        resumenActual = null;

        try {

            const response = await fetch(
                "{{ route('lotes.importar.preview') }}",
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector(
                            'input[name="_token"]'
                        ).value,
                        'Accept': 'application/json'
                    },
                    body: formData
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo analizar el archivo.');
            }

            tokenImportacion = data.token;
            resumenActual = data;

            actualizarResumen(data);

            resumen.style.display = 'block';
            tablaPreview.innerHTML = '';

            (data.preview || []).forEach(function (registro) {

                const fila = document.createElement('tr');

                const estado = registro.estado || '';

                let detalle = '';

                if (estado === 'actualizar') {
                    detalle = mostrarCambios(registro.cambios);
                } else if (estado === 'error') {
                    detalle = mostrarErrores(registro.errores);
                } else if (estado === 'nuevo') {
                    detalle = '<span style="color:#198754;">Se creara este lote.</span>';
                } else {
                    detalle = '<span style="color:#64748b;">No requiere modificaciones.</span>';
                }

                fila.innerHTML = `
                    <td>${escaparHtml(String(registro.fila ?? ''))}</td>

                    <td>
                        <strong>${escaparHtml(registro.codigo_producto ?? '')}</strong>
                    </td>

                    <td>${escaparHtml(registro.numero_lote ?? '')}</td>

                    <td>${escaparHtml(mostrarValor(registro.fecha_fabricacion))}</td>

                    <td>${escaparHtml(mostrarValor(registro.fecha_vencimiento))}</td>

                    <td>${escaparHtml(mostrarValor(registro.registro_sanitario))}</td>

                    <td>${etiquetaEstado(estado)}</td>

                    <td>${detalle}</td>
                `;

                tablaPreview.appendChild(fila);

            });

            contenedorPreview.style.display = 'block';

            btnConfirmar.disabled = Number(data.errores ?? 0) > 0;

            if (Number(data.errores ?? 0) > 0) {
                mostrarMensaje(
                    'El archivo contiene errores. Corrijalos antes de confirmar.'
                );
            } else {
                mostrarMensaje(
                    'Archivo analizado correctamente. Revise las modificaciones antes de confirmar.',
                    'success'
                );
            }

        } catch (error) {

            mostrarMensaje(error.message || 'Ocurrio un error al analizar el archivo.');

        } finally {

            btnAnalizar.disabled = false;
            btnAnalizar.textContent = 'Analizar archivo';

        }

    });

    btnConfirmar.addEventListener('click', async function () {

        if (!tokenImportacion || !resumenActual) {
            mostrarMensaje('No existe una importacion pendiente de confirmar.');
            return;
        }

        const nuevos = Number(resumenActual.nuevos ?? 0);
        const actualizar = Number(resumenActual.actualizar ?? 0);
        const sinCambios = Number(resumenActual.sin_cambios ?? 0);

        const confirmar = confirm(
            'CONFIRMAR IMPORTACION DE LOTES\n\n' +
            'Lotes nuevos: ' + nuevos + '\n' +
            'Lotes a actualizar: ' + actualizar + '\n' +
            'Sin cambios: ' + sinCambios + '\n\n' +
            'ATENCION: Los campos opcionales vacios en Excel borraran ' +
            'la informacion existente de esos campos.\n\n' +
            'Las cantidades de inventario NO seran modificadas.\n\n' +
            'Desea continuar?'
        );

        if (!confirmar) return;

        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Guardando...';

        try {

            const response = await fetch(
                "{{ route('lotes.importar.confirmar') }}",
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'input[name="_token"]'
                        ).value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token: tokenImportacion
                    })
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo confirmar la importacion.');
            }

            mostrarMensaje(
                'IMPORTACION FINALIZADA CORRECTAMENTE\n\n' +
                'Lotes creados: ' + (data.creados ?? 0) + '\n' +
                'Lotes actualizados: ' + (data.actualizados ?? 0) + '\n' +
                'Sin cambios: ' + (data.sin_cambios ?? 0) + '\n' +
                'Total de lotes registrados: ' + (data.total_lotes ?? 0),
                'success'
            );

            tokenImportacion = null;
            resumenActual = null;

            btnConfirmar.disabled = true;
            archivo.value = '';

        } catch (error) {

            mostrarMensaje(error.message || 'Ocurrio un error al confirmar.');

            btnConfirmar.disabled = false;

        } finally {

            btnConfirmar.textContent = 'Confirmar importacion';

        }

    });

    // ==========================================
// EXPORTACION DE LOTES CON PROGRESO REAL
// ==========================================

const btnExportar = document.getElementById('btnExportarLotes');
const panelProgreso = document.getElementById('progresoExportacion');
const barraProgreso = document.getElementById('barraProgreso');
const textoProgreso = document.getElementById('textoProgreso');
const porcentajeProgreso = document.getElementById('porcentajeProgreso');
const detalleProgreso = document.getElementById('detalleProgreso');
const descargaLista = document.getElementById('descargaLista');
const enlaceDescarga = document.getElementById('enlaceDescarga');

let temporizadorProgreso = null;

btnExportar.addEventListener('click', async function () {

    btnExportar.disabled = true;
    btnExportar.textContent = 'Iniciando exportacion...';

    panelProgreso.style.display = 'block';
    descargaLista.style.display = 'none';

    barraProgreso.style.width = '0%';
    porcentajeProgreso.textContent = '0%';
    textoProgreso.textContent = 'Preparando archivo...';
    detalleProgreso.textContent = 'Iniciando proceso en el servidor...';

    try {

        const response = await fetch(
            "{{ route('lotes.exportar.iniciar') }}",
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector(
                        'input[name="_token"]'
                    ).value,
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo iniciar la exportacion.');
        }

        consultarProgreso(data.token);

    } catch (error) {

        textoProgreso.textContent = 'Error en la exportacion';
        detalleProgreso.textContent = error.message;
        btnExportar.disabled = false;
        btnExportar.textContent = 'Exportar lotes a Excel';

    }

});

async function consultarProgreso(token) {

    try {

        const response = await fetch(
            `/lotes/exportar/progreso/${encodeURIComponent(token)}`,
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo consultar el progreso.');
        }

        const porcentaje = Math.min(
            100,
            Math.max(0, Number(data.porcentaje || 0))
        );

        barraProgreso.style.width = porcentaje + '%';
        porcentajeProgreso.textContent = porcentaje + '%';

        detalleProgreso.textContent =
            Number(data.procesados).toLocaleString() +
            ' de ' +
            Number(data.total).toLocaleString() +
            ' registros procesados';

        if (data.estado === 'completado') {

            clearTimeout(temporizadorProgreso);

            textoProgreso.textContent = 'Excel generado correctamente';
            descargaLista.style.display = 'block';

            enlaceDescarga.href =
                `/lotes/exportar/descargar/${encodeURIComponent(token)}`;

            btnExportar.disabled = false;
            btnExportar.textContent = 'Exportar nuevamente';

            return;
        }

        if (data.estado === 'error') {

            clearTimeout(temporizadorProgreso);

            textoProgreso.textContent = 'Error al generar el Excel';
            detalleProgreso.textContent = data.error || 'Error desconocido';

            btnExportar.disabled = false;
            btnExportar.textContent = 'Intentar nuevamente';

            return;
        }

        textoProgreso.textContent =
            data.estado === 'pendiente'
                ? 'En espera del trabajador...'
                : 'Generando archivo Excel...';

        temporizadorProgreso = setTimeout(
            () => consultarProgreso(token),
            1000
        );

    } catch (error) {

        textoProgreso.textContent = 'Error consultando progreso';
        detalleProgreso.textContent = error.message;

        btnExportar.disabled = false;
        btnExportar.textContent = 'Intentar nuevamente';

    }

}

});
</script>

@endsection
