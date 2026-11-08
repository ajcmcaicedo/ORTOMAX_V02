@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

<div class="ortomax-card">

    <div class="ortomax-module-header">

        <div>

            <h1 class="ortomax-title">
                Bienvenido a ORTOMAX
            </h1>

            <p class="ortomax-subtitle">
                Sistema de Gestión de Implantes Ortopédicos
            </p>

        </div>

    </div>


    <div style="margin-top: 30px;">

        <h2>
            Panel principal
        </h2>

        <p>
            Desde este sistema podrá administrar la información
            de regionales, bodegas, productos, lotes, equipos
            y activos de ORTOMAX.
        </p>

    </div>


    <div
        style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:20px;
            margin-top:30px;
        "
    >

        <div class="ortomax-card">

            <h3>
                Regionales
            </h3>

            <p>
                Administración de las regionales de ORTOMAX.
            </p>

            @if(auth()->user()->tienePermiso('regionales.ver'))

                <a
                    href="{{ route('regionales.index') }}"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Ver Regionales
                </a>

            @endif

        </div>


        <div class="ortomax-card">

            <h3>
                Bodegas
            </h3>

            <p>
                Administración de bodegas y ubicaciones.
            </p>

            @if(auth()->user()->tienePermiso('bodegas.ver'))

                <a
                    href="{{ url('/bodegas') }}"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Ver Bodegas
                </a>

            @endif

        </div>


        <div class="ortomax-card">

            <h3>
                Productos
            </h3>

            <p>
                Consulta y administración de productos.
            </p>

            @if(auth()->user()->tienePermiso('productos.ver'))

                <a
                    href="{{ url('/productos') }}"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Ver Productos
                </a>

            @endif

        </div>


        <div class="ortomax-card">

            <h3>
                Inventario
            </h3>

            <p>
                Control de inventario y movimientos.
            </p>

        </div>

    </div>

</div>

@endsection