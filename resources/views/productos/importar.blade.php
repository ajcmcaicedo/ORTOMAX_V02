@extends('layouts.app')

@section('title', 'Solicitud rápida de Productos')

@section('content')

<div class="ortomax-card">

    {{-- Encabezado --}}
    <div class="ortomax-module-header">

        <div>
            <h1 class="ortomax-title">
                Solicitud rápida de Productos
            </h1>

            <p class="ortomax-subtitle">
                Solicitar nuevos productos o actualizar productos temporales mediante Excel
            </p>
        </div>

        <div>
            <a
                href="{{ route('productos.index') }}"
                class="ortomax-btn ortomax-btn-secondary"
                style="text-decoration:none;"
            >
                ← Volver a Productos
            </a>
        </div>

    </div>


    {{-- Selección de archivo --}}
    <div style="margin-top:25px;">

        <form id="formImportarProductos">

            @csrf

            <div style="
                display:flex;
                gap:15px;
                align-items:end;
                flex-wrap:wrap;
            ">

                {{-- Archivo Excel --}}
                <div style="flex:1; min-width:350px;">

                    <label class="ortomax-form-label">
                        Archivo Excel
                    </label>

                    <input
                        type="file"
                        id="archivo"
                        name="archivo"
                        class="ortomax-form-control"
                        accept=".xlsx,.xls"
                        required
                    >

                    <small style="
                        display:block;
                        margin-top:7px;
                        color:#6c757d;
                    ">
                        Formatos permitidos: XLSX y XLS. Máximo 10 MB.
                    </small>

                </div>

                {{-- Descargar plantilla --}}
                <div>

                    <a
                        href="{{ route('productos.importar.plantilla') }}"
                        class="ortomax-btn ortomax-btn-secondary"
                        style="text-decoration:none; white-space:nowrap;"
                    >
                        📥 Descargar plantilla
                    </a>

                </div>

                {{-- Analizar --}}
                <div>

                    <button
                        type="submit"
                        id="btnAnalizar"
                        class="ortomax-btn ortomax-btn-primary"
                    >
                        🔍 Analizar archivo
                    </button>

                </div>

            </div>

        </form>

    </div>


    {{-- Mensaje --}}
    <div
        id="mensaje"
        style="
            display:none;
            margin-top:20px;
            padding:15px;
            border-radius:8px;
        "
    ></div>


    {{-- Resumen --}}
    <div
        id="resumen"
        style="
            display:none;
            margin-top:25px;
        "
    >

        <h3 style="margin-bottom:15px;">
            Resultado del análisis
        </h3>

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
            gap:15px;
        ">

            <div class="ortomax-card" style="padding:20px;">
                <div class="ortomax-subtitle">TOTAL</div>
                <div id="total" style="font-size:28px;font-weight:bold;">
                    0
                </div>
            </div>

            <div class="ortomax-card" style="padding:20px;">
                <div class="ortomax-subtitle">NUEVOS</div>
                <div id="nuevos" style="font-size:28px;font-weight:bold;">
                    0
                </div>
            </div>

            <div class="ortomax-card" style="padding:20px;">
                <div class="ortomax-subtitle">ACTUALIZAR</div>
                <div id="actualizar" style="font-size:28px;font-weight:bold;">
                    0
                </div>
            </div>

            <div class="ortomax-card" style="padding:20px;">
                <div class="ortomax-subtitle">SIN CAMBIOS</div>
                <div id="sinCambios" style="font-size:28px;font-weight:bold;">
                    0
                </div>
            </div>

            <div class="ortomax-card" style="padding:20px;">
                <div class="ortomax-subtitle">ERRORES</div>
                <div id="errores" style="font-size:28px;font-weight:bold;">
                    0
                </div>
            </div>

        </div>

    </div>


    {{-- Vista previa --}}
    <div
        id="contenedorPreview"
        style="
            display:none;
            margin-top:30px;
        "
    >

        <div style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:10px;
            margin-bottom:15px;
        ">

            <div>

                <h3 style="margin:0;">
                    Vista previa
                </h3>

                <p class="ortomax-subtitle" style="margin-top:5px;">
                    Revise los cambios antes de confirmar.
                </p>

            </div>

            <button
                type="button"
                id="btnConfirmar"
                class="ortomax-btn ortomax-btn-primary"
                disabled
            >
                ✅ Confirmar importación
            </button>

        </div>


        <div class="ortomax-table-container">

            <table class="ortomax-table">

                <thead>

                    <tr>
                        <th>FILA</th>
                        <th>CODIGO</th>
                        <th>NOMBRE</th>
                        <th>DESCRIPCIÓN</th>
                        <th>ESTADO</th>
                        <th>CAMBIOS</th>
                    </tr>

                </thead>

                <tbody id="tablaPreview">

                </tbody>

            </table>

        </div>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('formImportarProductos');
    const archivo = document.getElementById('archivo');
    const btnAnalizar = document.getElementById('btnAnalizar');
    const btnConfirmar = document.getElementById('btnConfirmar');

    const mensaje = document.getElementById('mensaje');
    const resumen = document.getElementById('resumen');
    const contenedorPreview = document.getElementById('contenedorPreview');
    const tablaPreview = document.getElementById('tablaPreview');

    let tokenImportacion = null;


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


    function textoEstado(estado) {

        switch (estado) {

            case 'nuevo':
                return '🟢 NUEVO';

            case 'actualizar':
                return '🟡 ACTUALIZAR';

            case 'sin_cambios':
                return '⚪ SIN CAMBIOS';

            case 'error':
                return '🔴 ERROR';

            default:
                return estado ?? '';

        }

    }


    function mostrarCambios(cambios) {

        if (!cambios || Object.keys(cambios).length === 0) {
            return '—';
        }

        let html = '<ul style="margin:0;padding-left:18px;">';

        Object.entries(cambios).forEach(function ([campo, valores]) {

            if (
                valores &&
                typeof valores === 'object' &&
                'actual' in valores &&
                'nuevo' in valores
            ) {

                html += '<li>' +
                    '<strong>' + escaparHtml(campo) + '</strong>: ' +
                    escaparHtml(String(valores.actual ?? '')) +
                    ' → ' +
                    escaparHtml(String(valores.nuevo ?? '')) +
                    '</li>';

            } else {

                html += '<li>' +
                    '<strong>' + escaparHtml(campo) + '</strong>' +
                    '</li>';

            }

        });

        html += '</ul>';

        return html;

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
        btnAnalizar.textContent = '⏳ Analizando...';

        mensaje.style.display = 'none';
        resumen.style.display = 'none';
        contenedorPreview.style.display = 'none';
        btnConfirmar.disabled = true;
        tokenImportacion = null;

        try {

            const response = await fetch(
                "{{ route('productos.importar.preview') }}",
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'input[name="_token"]'
                            ).value,
                        'Accept': 'application/json'
                    },
                    body: formData
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {

                throw new Error(
                    data.message || 'No se pudo analizar el archivo.'
                );

            }

            tokenImportacion = data.token;


            document.getElementById('total').textContent =
                data.total ?? 0;

            document.getElementById('nuevos').textContent =
                data.nuevos ?? 0;

            document.getElementById('actualizar').textContent =
                data.actualizar ?? 0;

            document.getElementById('sinCambios').textContent =
                data.sin_cambios ?? 0;

            document.getElementById('errores').textContent =
                data.errores ?? 0;

            resumen.style.display = 'block';


            tablaPreview.innerHTML = '';

            (data.preview || []).forEach(function (registro) {

                const fila = document.createElement('tr');

                fila.innerHTML = `
                    <td>${escaparHtml(String(registro.fila ?? ''))}</td>

                    <td>
                        <strong>
                            ${escaparHtml(registro.codigo ?? '')}
                        </strong>
                    </td>

                    <td>
                        ${escaparHtml(registro.nombre ?? '')}
                    </td>

                    <td>
                        ${escaparHtml(registro.descripcion ?? '')}
                    </td>

                    <td>
                        ${escaparHtml(textoEstado(registro.estado))}
                    </td>

                    <td>
                        ${mostrarCambios(registro.cambios)}
                    </td>
                `;

                tablaPreview.appendChild(fila);

            });


            contenedorPreview.style.display = 'block';

            /*
             * Solo permitimos confirmar si no existen errores.
             */
            btnConfirmar.disabled =
                Number(data.errores ?? 0) > 0;

            mostrarMensaje(
                'Archivo analizado correctamente. Revise la vista previa antes de confirmar.',
                'success'
            );

        } catch (error) {

            mostrarMensaje(
                error.message || 'Ocurrió un error al analizar el archivo.'
            );

        } finally {

            btnAnalizar.disabled = false;
            btnAnalizar.textContent = '🔍 Analizar archivo';

        }

    });


    btnConfirmar.addEventListener('click', async function () {

        if (!tokenImportacion) {

            mostrarMensaje(
                'No existe una importación pendiente de confirmar.'
            );

            return;
        }


        const confirmar = confirm(
            '¿Está seguro de confirmar la importación?\\n\\n' +
            'Los registros nuevos serán creados y los cambios serán aplicados.'
        );


        if (!confirmar) {
            return;
        }


        btnConfirmar.disabled = true;
        btnConfirmar.textContent = '⏳ Guardando...';


        try {

            const response = await fetch(
                "{{ route('productos.importar.confirmar') }}",
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
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

                throw new Error(
                    data.message ||
                    'No se pudo confirmar la importación.'
                );

            }


            mostrarMensaje(
                'Importación finalizada correctamente. ' +
                'Nuevos: ' + (data.creados ?? 0) +
                ' | Actualizados: ' + (data.actualizados ?? 0) +
                ' | Sin cambios: ' + (data.sin_cambios ?? 0),
                'success'
            );


            tokenImportacion = null;

            btnConfirmar.disabled = true;

            archivo.value = '';

        } catch (error) {

            mostrarMensaje(
                error.message ||
                'Ocurrió un error al confirmar la importación.'
            );

            btnConfirmar.disabled = false;

        } finally {

            btnConfirmar.textContent = '✅ Confirmar importación';

        }

    });

});

</script>

@endsection