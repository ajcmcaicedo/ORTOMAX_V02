{{-- Estilos del menu lateral ORTOMAX --}}
<style>
    .ortomax-sidebar {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        overflow: hidden;
    }

    .ortomax-sidebar .ortomax-logo {
        padding: 22px 16px 18px;
        text-align: center;
        flex-shrink: 0;
    }

    .ortomax-sidebar .ortomax-logo img {
        display: block;
        width: 76px;
        height: 76px;
        object-fit: contain;
        margin: 0 auto 9px;
    }

    .ortomax-sidebar .ortomax-company-name {
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .2px;
    }

    .ortomax-sidebar .ortomax-menu {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 10px 10px 24px;
    }

    .ortomax-sidebar .ortomax-menu-title,
    .ortomax-sidebar .nav-section-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 22px 10px 9px;
        padding: 0 0 8px;
        color: #c9efff;
        font-size: 12px;
        line-height: 1.3;
        font-weight: 800;
        letter-spacing: 1.15px;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,.22);
        list-style: none;
    }

    .ortomax-sidebar .ortomax-menu-title:first-child {
        margin-top: 8px;
    }

    .ortomax-sidebar .ortomax-menu-title .menu-section-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        border-radius: 6px;
        background: rgba(255,255,255,.16);
        color: #fff;
        font-size: 10px;
        letter-spacing: .3px;
    }

    .ortomax-sidebar .ortomax-menu > a,
    .ortomax-sidebar .ortomax-menu li > a {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-height: 40px;
        box-sizing: border-box;
        margin: 3px 0;
        padding: 9px 11px;
        border-radius: 8px;
        color: #fff;
        font-size: 13.5px;
        font-weight: 500;
        line-height: 1.35;
        text-decoration: none;
        transition: background .18s ease, transform .18s ease;
    }

    .ortomax-sidebar .ortomax-menu > a:hover,
    .ortomax-sidebar .ortomax-menu li > a:hover {
        background: rgba(255,255,255,.16);
        color: #fff;
        transform: translateX(2px);
    }

    .ortomax-sidebar .ortomax-menu a.ortomax-menu-pendiente {
        cursor: default;
        opacity: .88;
    }

    .ortomax-sidebar .ortomax-menu a.ortomax-menu-pendiente:hover {
        transform: none;
        background: rgba(255,255,255,.08);
    }

    /* Los elementos de cada opcion no deben encogerse ni desbordarse. */
    .ortomax-sidebar .ortomax-menu > a > span,
    .ortomax-sidebar .ortomax-menu li > a > span {
        min-width: 0;
    }

    .ortomax-sidebar .ortomax-menu > a > span:first-child,
    .ortomax-sidebar .ortomax-menu li > a > span:first-child {
        flex: 0 0 18px;
        text-align: center;
    }

    .ortomax-sidebar .ortomax-menu > a > span:nth-child(2),
    .ortomax-sidebar .ortomax-menu li > a > span:nth-child(2) {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    /* Solo la ultima etiqueta de los modulos pendientes lleva insignia. */
    .ortomax-sidebar .ortomax-menu a.ortomax-menu-pendiente > span:last-child {
        flex: 0 0 auto;
        margin-left: auto;
        padding: 3px 5px;
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 5px;
        color: #e5f7ff;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .25px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .ortomax-sidebar .ortomax-menu a.ortomax-menu-pendiente > span:nth-child(2) {
        flex: 1 1 auto;
    }

    .ortomax-sidebar .ortomax-menu ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .ortomax-sidebar .ortomax-menu::-webkit-scrollbar {
        width: 6px;
    }

    .ortomax-sidebar .ortomax-menu::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,.28);
        border-radius: 10px;
    }

    @media (max-width: 768px) {
        .ortomax-sidebar .ortomax-menu-title,
        .ortomax-sidebar .nav-section-title {
            font-size: 11px;
        }

        .ortomax-sidebar .ortomax-menu > a,
        .ortomax-sidebar .ortomax-menu li > a {
            font-size: 13px;
        }
    }
</style>

<aside class="ortomax-sidebar">

    {{-- LOGO Y NOMBRE DE LA EMPRESA --}}
    <div class="ortomax-logo">
        <img
            src="{{ asset(config('ortomax.logo')) }}"
            alt="{{ config('ortomax.empresa') }}"
        >

        <div class="ortomax-company-name">
            {{ config('ortomax.nombre') }}
        </div>
    </div>

    <nav class="ortomax-menu">

        {{-- 01. PRINCIPAL --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">01</span>
            Principal
        </div>

        <a href="{{ url('/') }}">
            <span aria-hidden="true">🏠</span>
            <span>Inicio</span>
        </a>

        {{-- 02. MAESTROS --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">02</span>
            Maestros
        </div>

        @if(auth()->user()->tienePermiso('regionales.ver'))
            <a href="{{ route('regionales.index') }}">
                <span aria-hidden="true">🌎</span>
                <span>Regionales</span>
            </a>
        @endif

        @if(auth()->user()->tienePermiso('bodegas.ver'))
            <a href="{{ url('/bodegas') }}">
                <span aria-hidden="true">🏢</span>
                <span>Bodegas</span>
            </a>
        @endif

        @if(auth()->user()->tienePermiso('proveedores.ver'))
            <a href="{{ route('proveedores.index') }}">
                <span aria-hidden="true">🏭</span>
                <span>Proveedores</span>
            </a>
        @endif

        @if(auth()->user()->tienePermiso('productos.ver'))
            <a href="{{ url('/productos') }}">
                <span aria-hidden="true">📦</span>
                <span>Productos</span>
            </a>
        @endif

        @if(auth()->user()->tienePermiso('productos_maestros.ver'))
            <a href="{{ route('productos_maestros.index') }}">
                <span aria-hidden="true">📋</span>
                <span>Productos Maestros</span>
            </a>
        @endif

        @if(auth()->user()->tienePermiso('productos_proveedores.ver'))
            <a href="{{ route('productos_proveedores.index') }}">
                <span aria-hidden="true">🏷️</span>
                <span>Productos por Proveedor</span>
            </a>
        @endif

        {{-- 03. INVENTARIO --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">03</span>
            Inventario
        </div>

        <a href="{{ route('lotes.importar') }}">
            <span aria-hidden="true">🏷️</span>
            <span>Lotes</span>
        </a>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">🔧</span>
            <span>Instrumentales</span>
            <span>PENDIENTE</span>
        </a>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">🧰</span>
            <span>Equipos</span>
            <span>PENDIENTE</span>
        </a>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">⚙️</span>
            <span>Activos</span>
            <span>PENDIENTE</span>
        </a>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">📦</span>
            <span>Contenido de Equipos</span>
            <span>PENDIENTE</span>
        </a>

        {{-- 04. IMPORTACIONES --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">04</span>
            Importaciones
        </div>

        <a href="{{ route('instituciones.index') }}">
            <span aria-hidden="true">📥</span>
            <span>Importar Instituciones</span>
        </a>

        <a href="{{ route('productos.importar') }}">
            <span aria-hidden="true">📥</span>
            <span>Importar Productos</span>
        </a>

        {{-- 05. SINCRONIZACIONES --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">05</span>
            Sincronizaciones
        </div>

        @if(auth()->user()->tienePermiso('productos.ver'))
            <a href="{{ route('sincronizaciones.productos') }}">
                <span aria-hidden="true">🔄</span>
                <span>Productos Contífico</span>
            </a>
        @endif

        @if(auth()->user()->tienePermiso('bodegas.ver'))
            <a href="{{ route('sincronizaciones.bodegas') }}">
                <span aria-hidden="true">🔄</span>
                <span>Bodegas Contífico</span>
            </a>
        @endif

        {{-- 06. OPERACIÓN --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">06</span>
            Operación
        </div>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">📋</span>
            <span>Inventario</span>
            <span>PENDIENTE</span>
        </a>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">🔄</span>
            <span>Movimientos</span>
            <span>PENDIENTE</span>
        </a>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">🏥</span>
            <span>Cirugías</span>
            <span>PENDIENTE</span>
        </a>

        @can('viewAny', App\Models\NotaEntrega::class)
            <a href="{{ route('notas_entrega.index') }}">
                <span aria-hidden="true">📄</span>
                <span>Notas de Entrega</span>
            </a>
        @endcan

        @can('viewAny', App\Models\TipoCirugia::class)
            <a href="{{ route('tipos_cirugia.index') }}">
                <span aria-hidden="true">🩻</span>
                <span>Tipos de cirugía</span>
            </a>
        @endcan

        @can('viewAny', App\Models\Medico::class)
            <a href="{{ route('medicos.index') }}">
                <span aria-hidden="true">🩺</span>
                <span>Médicos</span>
            </a>
        @endcan

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">♻️</span>
            <span>Consumos</span>
            <span>PENDIENTE</span>
        </a>

        {{-- 07. REPORTES --}}
        <div class="ortomax-menu-title">
            <span class="menu-section-number">07</span>
            Reportes
        </div>

        <a href="#" class="ortomax-menu-pendiente" onclick="return false;">
            <span aria-hidden="true">📊</span>
            <span>Reportes</span>
            <span>PENDIENTE</span>
        </a>

    </nav>

</aside>
