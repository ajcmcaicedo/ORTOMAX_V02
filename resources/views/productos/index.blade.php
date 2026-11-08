@extends('layouts.app')

@section('title', 'Productos')

@section('content')

<div class="ortomax-card">

    {{-- Encabezado --}}
    <div class="ortomax-module-header">

        <div>
            <h1 class="ortomax-title">
                Productos
            </h1>

            <p class="ortomax-subtitle">
                Productos sincronizados desde Contífico
            </p>
        </div>

    </div>


    {{-- Buscador --}}
    <div style="margin-top: 25px;">

        <form
            method="GET"
            action="{{ route('productos.index') }}"
            style="
                display:flex;
                gap:10px;
                align-items:end;
                flex-wrap:wrap;
            "
        >

            <div style="flex:1; min-width:280px;">

                <label class="ortomax-form-label">
                    Buscar producto
                </label>

                <input
                    type="text"
                    name="q"
                    value="{{ $busqueda }}"
                    class="ortomax-form-control"
                    placeholder="Código, nombre o código de barras..."
                >

            </div>

            <div>

                <button
                    type="submit"
                    class="ortomax-btn ortomax-btn-primary"
                >
                    Buscar
                </button>

            </div>

            @if($busqueda !== '')

                <div>

                    <a
                        href="{{ route('productos.index') }}"
                        class="ortomax-btn ortomax-btn-secondary"
                        style="text-decoration:none;"
                    >
                        Limpiar
                    </a>

                </div>

            @endif

        </form>

    </div>


    {{-- Información --}}
    <div style="margin-top:20px;">

        <p class="ortomax-subtitle">

            @if($busqueda !== '')

                Resultados para:
                <strong>{{ $busqueda }}</strong>

            @else

                Total de productos:
                <strong>{{ $productos->total() }}</strong>

            @endif

        </p>

    </div>


    {{-- Tabla --}}
    <div class="ortomax-table-container" style="margin-top:20px;">

        <table class="ortomax-table">

            <thead>

                <tr>

                    <th>CODIGO</th>

                    <th>CODGIOJBG</th>

                    <th>CATEGORIA</th>

                    <th>NOMBRE</th>

                    <th>PROVEEDOR</th>

                    <th>Estado</th>

                </tr>

            </thead>


            <tbody>

                @forelse($productos as $producto)
            
                    <tr>
            
                        <td>
                            <strong>
                                {{ $producto->codigo ?? '—' }}
                            </strong>
                        </td>
            
                        <td>
                            {{ $producto->codigojbg ?? '—' }}
                        </td>
            
                        <td>
                            {{ $producto->categoria ?? '—' }}
                        </td>
            
                        <td>
                            {{ $producto->nombre ?? '—' }}
                        </td>
            
                        <td>
                            {{ $producto->marca ?? '—' }}
                        </td>
            
                        <td>
            
                            @if($producto->estado === 'A')
            
                                <span class="ortomax-status ortomax-status-active">
                                    ACTIVO
                                </span>
            
                            @else
            
                                <span class="ortomax-status ortomax-status-inactive">
                                    {{ $producto->estado ?? '—' }}
                                </span>
            
                            @endif
            
                        </td>
            
                    </tr>
            
                @empty
            
                    <tr>
            
                        <td
                            colspan="6"
                            style="text-align:center; padding:40px;"
                        >
                            No se encontraron productos.
                        </td>
            
                    </tr>
            
                @endforelse
            
            </tbody>
        </table>

    </div>


    {{-- Paginación --}}
    @if($productos->hasPages())

        <div style="
            margin-top:25px;
            display:flex;
            justify-content:center;
        ">

            {{ $productos->links() }}

        </div>

    @endif

</div>

@endsection