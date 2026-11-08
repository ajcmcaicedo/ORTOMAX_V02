@extends('layouts.app')

@section('title', 'Sincronización de Productos Contífico')

@section('content')

<div class="container-fluid">

    {{-- ========================================================= --}}
    {{-- ENCABEZADO --}}
    {{-- ========================================================= --}}

    <div class="ortomax-page-header">

        <div>

            <h1>
                🔄 Sincronización de Productos Contífico
            </h1>

            <p>
                Actualización de productos desde Contífico hacia ORTOMAX AI.
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- TARJETA PRINCIPAL --}}
    {{-- ========================================================= --}}

    <div class="ortomax-card">

        <div class="ortomax-card-header">

            <h3>
                Productos Contífico
            </h3>

        </div>


        <div class="ortomax-card-body">

            <p>
                Desde esta sección podrá ejecutar la sincronización
                de los productos registrados en Contífico.
            </p>


            {{-- ================================================= --}}
            {{-- BOTÓN DE SINCRONIZACIÓN --}}
            {{-- ================================================= --}}

            <div style="margin-top: 25px;">

                <button
                    type="button"
                    id="btnSincronizarProductos"
                    class="btn btn-primary"
                    onclick="ejecutarSincronizacionProductos()"
                >
                    🔄 Sincronizar Productos Contífico
                </button>


                {{-- ============================================= --}}
                {{-- RESULTADO / ESTADO --}}
                {{-- ============================================= --}}

                <div
                    id="resultadoSincronizacion"
                    style="margin-top: 15px;"
                ></div>

            </div>

        </div>

    </div>

</div>


{{-- ============================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ============================================================= --}}

<script>

let intervaloEstadoSincronizacion = null;


/*
|--------------------------------------------------------------------------
| EJECUTAR SINCRONIZACIÓN
|--------------------------------------------------------------------------
*/

function ejecutarSincronizacionProductos()
{
    const boton =
        document.getElementById(
            'btnSincronizarProductos'
        );

    const resultado =
        document.getElementById(
            'resultadoSincronizacion'
        );


    /*
    |--------------------------------------------------------------------------
    | Estado inicial
    |--------------------------------------------------------------------------
    */

    boton.disabled = true;

    boton.innerHTML =
        '⏳ Iniciando sincronización...';


    resultado.innerHTML = `

        <div style="
            padding: 12px;
            border-radius: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        ">

            ⏳ Iniciando sincronización de productos...

        </div>

    `;


    /*
    |--------------------------------------------------------------------------
    | Enviar solicitud al servidor
    |--------------------------------------------------------------------------
    */

    fetch(
        "{{ route('sincronizaciones.productos.ejecutar') }}",
        {
            method: 'POST',

            headers:
            {
                'Content-Type':
                    'application/json',

                'Accept':
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest',

                'X-CSRF-TOKEN':
                    document
                        .querySelector(
                            'meta[name="csrf-token"]'
                        )
                        .getAttribute('content')
            }
        }
    )


    /*
    |--------------------------------------------------------------------------
    | Procesar respuesta
    |--------------------------------------------------------------------------
    */

    .then(async function(response)
    {

        const data =
            await response.json();


        if (!response.ok)
        {

            throw {
                status: response.status,
                data: data
            };

        }


        return data;

    })


    /*
    |--------------------------------------------------------------------------
    | Respuesta correcta
    |--------------------------------------------------------------------------
    */

    .then(function(data)
    {

        if (data.ok)
        {

            mostrarEstadoEnEjecucion();

            iniciarConsultaEstado();

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | El servidor respondió pero no pudo iniciar
        |--------------------------------------------------------------------------
        */

        mostrarError(
            data.mensaje ||
            'No fue posible iniciar la sincronización.'
        );


        boton.disabled = false;

        boton.innerHTML =
            '🔄 Sincronizar Productos Contífico';

    })


    /*
    |--------------------------------------------------------------------------
    | Error
    |--------------------------------------------------------------------------
    */

    .catch(function(error)
    {

        console.error(
            'ERROR COMPLETO:',
            error
        );


        /*
        |--------------------------------------------------------------------------
        | Ya existe una sincronización
        |--------------------------------------------------------------------------
        */

        if (
            error.status === 409 &&
            error.data &&
            error.data.estado === 'EN_EJECUCION'
        )
        {

            mostrarEstadoEnEjecucion();

            iniciarConsultaEstado();

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Mensaje principal
        |--------------------------------------------------------------------------
        */

        let mensaje =
            'No fue posible iniciar la sincronización.';


        /*
        |--------------------------------------------------------------------------
        | Mensaje enviado por Laravel
        |--------------------------------------------------------------------------
        */

        if (
            error.data &&
            error.data.mensaje
        )
        {

            mensaje =
                error.data.mensaje;

        }


        /*
        |--------------------------------------------------------------------------
        | Detalle del error
        |--------------------------------------------------------------------------
        */

        if (
            error.data &&
            error.data.error
        )
        {

            mensaje +=
                '<br><br>' +
                '<strong>Detalle:</strong><br>' +
                error.data.error;

        }


        /*
        |--------------------------------------------------------------------------
        | Salida adicional
        |--------------------------------------------------------------------------
        */

        if (
            error.data &&
            error.data.salida
        )
        {

            mensaje +=
                '<br><br>' +
                '<strong>Salida:</strong><br>' +
                error.data.salida;

        }


        mostrarError(
            mensaje
        );


        boton.disabled = false;

        boton.innerHTML =
            '🔄 Sincronizar Productos Contífico';

    });

}


/*
|--------------------------------------------------------------------------
| CONSULTAR ESTADO DE SINCRONIZACIÓN
|--------------------------------------------------------------------------
*/

function consultarEstadoSincronizacion()
{

    fetch(
        "{{ route('sincronizaciones.productos.estado') }}",
        {
            method: 'GET',

            headers:
            {
                'Accept':
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest'
            }
        }
    )


    /*
    |--------------------------------------------------------------------------
    | Convertir respuesta
    |--------------------------------------------------------------------------
    */

    .then(function(response)
    {

        return response.json();

    })


    /*
    |--------------------------------------------------------------------------
    | Procesar estado
    |--------------------------------------------------------------------------
    */

    .then(function(data)
    {

        console.log(
            'Estado sincronización:',
            data
        );


        /*
        |--------------------------------------------------------------------------
        | EN EJECUCIÓN
        |--------------------------------------------------------------------------
        */

        if (
            data.estado === 'EN_EJECUCION'
        )
        {

            mostrarEstadoEnEjecucion();

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | FINALIZADA
        |--------------------------------------------------------------------------
        */

        if (
            data.estado === 'FINALIZADA'
        )
        {

            detenerConsultaEstado();

            mostrarFinalizada(
                data
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

        if (
            data.estado === 'ERROR'
        )
        {

            detenerConsultaEstado();

            mostrarError(
                data.mensaje ||
                'La sincronización terminó con errores.'
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | LISTO
        |--------------------------------------------------------------------------
        */

        if (
            data.estado === 'LISTO'
        )
        {

            detenerConsultaEstado();

            mostrarEstadoListo();

            return;

        }

    })


    /*
    |--------------------------------------------------------------------------
    | Error consultando estado
    |--------------------------------------------------------------------------
    */

    .catch(function(error)
    {

        console.error(
            'Error consultando estado:',
            error
        );

    });

}


/*
|--------------------------------------------------------------------------
| MOSTRAR ESTADO EN EJECUCIÓN
|--------------------------------------------------------------------------
*/

function mostrarEstadoEnEjecucion()
{

    const boton =
        document.getElementById(
            'btnSincronizarProductos'
        );

    const resultado =
        document.getElementById(
            'resultadoSincronizacion'
        );


    boton.disabled = true;

    boton.innerHTML =
        '⏳ Sincronización en curso...';


    resultado.innerHTML = `

        <div style="
            padding: 14px;
            border-radius: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        ">

            <strong>
                ⏳ Sincronización en curso
            </strong>


            <div style="
                margin-top: 6px;
                font-size: 14px;
            ">

                Se están actualizando los productos
                desde Contífico.

                Esta ventana puede permanecer abierta.

            </div>

        </div>

    `;

}


/*
|--------------------------------------------------------------------------
| MOSTRAR SINCRONIZACIÓN FINALIZADA
|--------------------------------------------------------------------------
*/

function mostrarFinalizada(data)
{

    const boton =
        document.getElementById(
            'btnSincronizarProductos'
        );

    const resultado =
        document.getElementById(
            'resultadoSincronizacion'
        );


    boton.disabled = false;

    boton.innerHTML =
        '🔄 Sincronizar Productos Contífico';


    resultado.innerHTML = `

        <div style="
            padding: 14px;
            border-radius: 6px;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        ">

            <strong>
                ✅ Sincronización finalizada correctamente
            </strong>


            <div style="
                margin-top: 8px;
                font-size: 14px;
            ">

                Productos procesados:

                <strong>
                    ${data.productos ?? 'N/D'}
                </strong>


                &nbsp; | &nbsp;


                Categorías:

                <strong>
                    ${data.categorias ?? 'N/D'}
                </strong>

            </div>

        </div>

    `;

}


/*
|--------------------------------------------------------------------------
| MOSTRAR ERROR
|--------------------------------------------------------------------------
*/

function mostrarError(mensaje)
{

    const boton =
        document.getElementById(
            'btnSincronizarProductos'
        );

    const resultado =
        document.getElementById(
            'resultadoSincronizacion'
        );


    boton.disabled = false;

    boton.innerHTML =
        '🔄 Sincronizar Productos Contífico';


    resultado.innerHTML = `

        <div style="
            padding: 14px;
            border-radius: 6px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        ">

            <strong>
                ❌ Error en la sincronización
            </strong>


            <div style="
                margin-top: 6px;
                font-size: 14px;
            ">

                ${mensaje}

            </div>

        </div>

    `;

}


/*
|--------------------------------------------------------------------------
| MOSTRAR ESTADO LISTO
|--------------------------------------------------------------------------
*/

function mostrarEstadoListo()
{

    const boton =
        document.getElementById(
            'btnSincronizarProductos'
        );


    boton.disabled = false;

    boton.innerHTML =
        '🔄 Sincronizar Productos Contífico';

}


/*
|--------------------------------------------------------------------------
| INICIAR CONSULTA AUTOMÁTICA
|--------------------------------------------------------------------------
*/

function iniciarConsultaEstado()
{

    detenerConsultaEstado();


    /*
    | Primera consulta inmediata
    */

    consultarEstadoSincronizacion();


    /*
    | Consultar cada 3 segundos
    */

    intervaloEstadoSincronizacion =
        setInterval(
            consultarEstadoSincronizacion,
            3000
        );

}


/*
|--------------------------------------------------------------------------
| DETENER CONSULTA AUTOMÁTICA
|--------------------------------------------------------------------------
*/

function detenerConsultaEstado()
{

    if (
        intervaloEstadoSincronizacion
    )
    {

        clearInterval(
            intervaloEstadoSincronizacion
        );


        intervaloEstadoSincronizacion =
            null;

    }

}


/*
|--------------------------------------------------------------------------
| COMPROBAR ESTADO AL CARGAR LA PÁGINA
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function()
    {

        consultarEstadoSincronizacion();

    }
);

</script>

@endsection