@extends('layouts.app')

@section('title', 'Sincronización de Bodegas Contífico')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                🔄 Sincronización de Bodegas Contífico
            </h2>

            <p class="text-muted mb-0">
                Sincroniza las bodegas de Contífico con ORTOMAX AI.
            </p>
        </div>
    </div>


    <div class="card shadow-sm">

        <div class="card-header">
            <strong>Sincronización</strong>
        </div>

        <div class="card-body">

            <div class="mb-4">

                <button
                    type="button"
                    id="btnSincronizarBodegas"
                    class="btn btn-primary"
                    onclick="ejecutarSincronizacionBodegas()"
                >
                    🔄 Sincronizar Bodegas
                </button>

            </div>


            <div id="resultadoSincronizacion">

                <div class="alert alert-secondary">
                    Sistema listo para sincronizar las bodegas de Contífico.
                </div>

            </div>

        </div>

    </div>

</div>


<script>

let intervaloEstadoBodegas = null;


/*
|--------------------------------------------------------------------------
| Ejecutar sincronización
|--------------------------------------------------------------------------
*/

function ejecutarSincronizacionBodegas()
{
    const boton = document.getElementById('btnSincronizarBodegas');

    boton.disabled = true;

    boton.innerHTML = '⏳ Iniciando sincronización...';

    mostrarEstadoEnEjecucion();


    fetch('{{ route('sincronizaciones.bodegas.ejecutar') }}', {
        method: 'POST',

        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({})
    })

    .then(response => response.json())

    .then(data => {

        console.log('Respuesta ejecución:', data);

        if (data.estado === 'EN_EJECUCION') {

            iniciarConsultaEstado();

            return;
        }


        if (data.estado === 'ERROR') {

            mostrarError(data.mensaje || 'Error al iniciar la sincronización.');

            boton.disabled = false;

            boton.innerHTML = '🔄 Sincronizar Bodegas';

            return;
        }


        if (data.estado === 'FINALIZADA') {

            mostrarFinalizada(data);

            boton.disabled = false;

            boton.innerHTML = '🔄 Sincronizar Bodegas';

            return;
        }


        iniciarConsultaEstado();

    })

    .catch(error => {

        console.error(error);

        mostrarError(
            'No fue posible iniciar la sincronización.'
        );

        boton.disabled = false;

        boton.innerHTML = '🔄 Sincronizar Bodegas';

    });
}


/*
|--------------------------------------------------------------------------
| Consultar estado
|--------------------------------------------------------------------------
*/

function consultarEstadoSincronizacionBodegas()
{
    fetch('{{ route('sincronizaciones.bodegas.estado') }}', {
        method: 'GET',

        headers: {
            'Accept': 'application/json'
        }
    })

    .then(response => response.json())

    .then(data => {

        console.log('Estado bodegas:', data);


        if (data.estado === 'EN_EJECUCION') {

            mostrarEstadoEnEjecucion();

            return;
        }


        if (data.estado === 'FINALIZADA') {

            detenerConsultaEstado();

            mostrarFinalizada(data);

            const boton = document.getElementById(
                'btnSincronizarBodegas'
            );

            boton.disabled = false;

            boton.innerHTML = '🔄 Sincronizar Bodegas';

            return;
        }


        if (data.estado === 'ERROR') {

            detenerConsultaEstado();

            mostrarError(
                data.mensaje || 'Error durante la sincronización.'
            );

            const boton = document.getElementById(
                'btnSincronizarBodegas'
            );

            boton.disabled = false;

            boton.innerHTML = '🔄 Sincronizar Bodegas';

            return;
        }


        mostrarEstadoListo();

    })

    .catch(error => {

        console.error(error);

    });
}


/*
|--------------------------------------------------------------------------
| Estado en ejecución
|--------------------------------------------------------------------------
*/

function mostrarEstadoEnEjecucion()
{
    document.getElementById(
        'resultadoSincronizacion'
    ).innerHTML = `

        <div class="alert alert-info">

            <strong>
                ⏳ Sincronización en curso
            </strong>

            <br>

            Consultando las bodegas de Contífico...

            <br>

            Por favor espere.

        </div>

    `;
}


/*
|--------------------------------------------------------------------------
| Sincronización finalizada
|--------------------------------------------------------------------------
*/

function mostrarFinalizada(data)
{
    document.getElementById(
        'resultadoSincronizacion'
    ).innerHTML = `

        <div class="alert alert-success">

            <h5 class="mb-3">
                ✅ Sincronización finalizada correctamente
            </h5>

            <div class="row">

                <div class="col-md-4">

                    <div class="border rounded p-3">

                        <div class="text-muted">
                            Bodegas procesadas
                        </div>

                        <h3 class="mb-0">
                            ${data.bodegas_procesadas ?? 0}
                        </h3>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="border rounded p-3">

                        <div class="text-muted">
                            Bodegas nuevas
                        </div>

                        <h3 class="mb-0">
                            ${data.bodegas_nuevas ?? 0}
                        </h3>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="border rounded p-3">

                        <div class="text-muted">
                            Bodegas actualizadas
                        </div>

                        <h3 class="mb-0">
                            ${data.bodegas_actualizadas ?? 0}
                        </h3>

                    </div>

                </div>

            </div>

        </div>

    `;
}


/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/

function mostrarError(mensaje)
{
    document.getElementById(
        'resultadoSincronizacion'
    ).innerHTML = `

        <div class="alert alert-danger">

            <strong>
                ❌ Error en la sincronización
            </strong>

            <br>

            ${mensaje}

        </div>

    `;
}


/*
|--------------------------------------------------------------------------
| Estado listo
|--------------------------------------------------------------------------
*/

function mostrarEstadoListo()
{
    document.getElementById(
        'resultadoSincronizacion'
    ).innerHTML = `

        <div class="alert alert-secondary">

            Sistema listo para sincronizar las bodegas de Contífico.

        </div>

    `;
}


/*
|--------------------------------------------------------------------------
| Iniciar polling
|--------------------------------------------------------------------------
*/

function iniciarConsultaEstado()
{
    detenerConsultaEstado();

    consultarEstadoSincronizacionBodegas();

    intervaloEstadoBodegas = setInterval(
        consultarEstadoSincronizacionBodegas,
        3000
    );
}


/*
|--------------------------------------------------------------------------
| Detener polling
|--------------------------------------------------------------------------
*/

function detenerConsultaEstado()
{
    if (intervaloEstadoBodegas !== null) {

        clearInterval(intervaloEstadoBodegas);

        intervaloEstadoBodegas = null;

    }
}


/*
|--------------------------------------------------------------------------
| Al cargar la página
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function()
    {
        consultarEstadoInicialBodegas();
    }
);

</script>

@endsection