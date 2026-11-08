<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', config('ortomax.empresa'))
        - {{ config('ortomax.nombre') }}
    </title>

    <link
        rel="stylesheet"
        href="{{ asset('css/app.css') }}"
    >

</head>


<body>

<div class="ortomax-layout">

    @include('layouts.sidebar')


    <main class="ortomax-main">

        @include('layouts.header')


        <section class="ortomax-content">

            @if(session('success'))

                <div class="ortomax-card">
                    {{ session('success') }}
                </div>

            @endif


            @yield('content')

        </section>

    </main>

</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES GENERALES DE MODALES
    |--------------------------------------------------------------------------
    */

    function abrirModal(id)
    {

        const modal =
            document.getElementById(id);


        if (!modal) {
            return;
        }


        modal.classList.add('active');


        document.body.classList.add(
            'ortomax-modal-open'
        );

    }


    function cerrarModal(id)
    {

        const modal =
            document.getElementById(id);


        if (!modal) {
            return;
        }


        modal.classList.remove('active');


        document.body.classList.remove(
            'ortomax-modal-open'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CERRAR MODAL AL HACER CLICK AFUERA
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function(event)
        {

            if (
                event.target.classList.contains(
                    'ortomax-modal'
                )
            ) {

                event.target.classList.remove(
                    'active'
                );


                document.body.classList.remove(
                    'ortomax-modal-open'
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CERRAR MODAL CON ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function(event)
        {

            if (event.key === 'Escape') {

                document
                    .querySelectorAll(
                        '.ortomax-modal.active'
                    )
                    .forEach(
                        function(modal)
                        {

                            modal.classList.remove(
                                'active'
                            );

                        }
                    );


                document.body.classList.remove(
                    'ortomax-modal-open'
                );

            }

        }
    );

</script>


</body>

</html>