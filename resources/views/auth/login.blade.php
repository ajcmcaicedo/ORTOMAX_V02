<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>ORTOMAX - Iniciar sesión</title>

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #eaf7ff 0%,
                    #f7fbfd 50%,
                    #dff2fc 100%
                );

            padding: 20px;
        }


        /* ---------------------------------------------------------
           CONTENEDOR PRINCIPAL
        --------------------------------------------------------- */

        .login-container {

            width: 100%;
            max-width: 430px;

            background: #ffffff;

            border-radius: 18px;

            box-shadow:
                0 15px 45px rgba(0, 0, 0, 0.12);

            padding: 38px 35px;

            text-align: center;
        }


        /* ---------------------------------------------------------
           LOGO
        --------------------------------------------------------- */

        .login-logo {

            width: 105px;
            height: 105px;

            object-fit: contain;

            margin: 0 auto 18px;

            display: block;
        }


        .login-company {

            font-size: 28px;

            font-weight: 700;

            color: #0878bd;

            margin-bottom: 5px;
        }


        .login-subtitle {

            font-size: 14px;

            color: #6b7280;

            margin-bottom: 30px;
        }


        /* ---------------------------------------------------------
           TITULO
        --------------------------------------------------------- */

        .login-title {

            font-size: 23px;

            font-weight: 600;

            color: #1f2937;

            margin-bottom: 25px;
        }


        /* ---------------------------------------------------------
           CAMPOS
        --------------------------------------------------------- */

        .form-group {

            text-align: left;

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: 600;

            color: #374151;
        }


        .input-wrapper {

            position: relative;
        }


        .form-control {

            width: 100%;

            height: 48px;

            padding: 0 14px;

            border: 1px solid #cbd5e1;

            border-radius: 9px;

            background: #ffffff;

            color: #1f2937;

            font-size: 15px;

            outline: none;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }


        .form-control:focus {

            border-color: #0d8fd1;

            box-shadow:
                0 0 0 3px rgba(13, 143, 209, 0.12);
        }


        .password-input {

            padding-right: 48px;
        }


        /* ---------------------------------------------------------
           MOSTRAR CONTRASEÑA
        --------------------------------------------------------- */

        .toggle-password {

            position: absolute;

            right: 12px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            cursor: pointer;

            font-size: 18px;

            color: #64748b;

            padding: 5px;
        }


        .toggle-password:hover {

            color: #0878bd;
        }


        /* ---------------------------------------------------------
           ERRORES
        --------------------------------------------------------- */

        .login-error {

            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            border-radius: 8px;

            padding: 11px 12px;

            margin-bottom: 18px;

            font-size: 14px;

            text-align: left;
        }


        /* ---------------------------------------------------------
           BOTÓN
        --------------------------------------------------------- */

        .login-button {

            width: 100%;

            height: 48px;

            border: none;

            border-radius: 9px;

            background: #0788ca;

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s,
                transform 0.1s;
        }


        .login-button:hover {

            background: #056fa5;
        }


        .login-button:active {

            transform: scale(0.99);
        }


        /* ---------------------------------------------------------
           OLVIDÓ CONTRASEÑA
        --------------------------------------------------------- */

        .forgot-password {

            display: block;

            margin-top: 18px;

            color: #0878bd;

            font-size: 14px;

            text-decoration: none;
        }


        .forgot-password:hover {

            text-decoration: underline;
        }


        /* ---------------------------------------------------------
           PIE
        --------------------------------------------------------- */

        .login-footer {

            margin-top: 30px;

            padding-top: 18px;

            border-top: 1px solid #e5e7eb;

            color: #94a3b8;

            font-size: 12px;
        }


        /* ---------------------------------------------------------
           CELULARES
        --------------------------------------------------------- */

        @media (max-width: 480px) {

            body {

                padding: 12px;
            }


            .login-container {

                padding: 30px 22px;

                border-radius: 15px;
            }


            .login-logo {

                width: 90px;

                height: 90px;
            }


            .login-company {

                font-size: 25px;
            }


            .login-title {

                font-size: 21px;
            }


            .form-control,
            .login-button {

                height: 50px;

                font-size: 16px;
            }

        }

    </style>

</head>


<body>


<div class="login-container">


    {{-- LOGO --}}

    <img
        src="{{ asset(config('ortomax.logo')) }}"
        alt="{{ config('ortomax.empresa') }}"
        class="login-logo"
    >


    {{-- EMPRESA --}}

    <div class="login-company">
        ORTOMAX
    </div>


    <div class="login-subtitle">
        {{ config('ortomax.nombre') }}
    </div>


    {{-- TITULO --}}

    <div class="login-title">
        Iniciar sesión
    </div>


    {{-- ERRORES --}}

    @if($errors->any())

        <div class="login-error">

            {{ $errors->first() }}

        </div>

    @endif


    {{-- FORMULARIO --}}

    <form
        method="POST"
        action="{{ route('login') }}"
    >

        @csrf


        {{-- CORREO --}}

        <div class="form-group">

            <label for="email">
                Correo electrónico
            </label>

            <input
                id="email"
                type="email"
                name="email"
                class="form-control"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
            >

        </div>


        {{-- CONTRASEÑA --}}

        <div class="form-group">

            <label for="password">
                Contraseña
            </label>


            <div class="input-wrapper">

                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control password-input"
                    autocomplete="current-password"
                    required
                >


                <button
                    type="button"
                    class="toggle-password"
                    id="togglePassword"
                    aria-label="Mostrar contraseña"
                >
                    👁
                </button>

            </div>

        </div>


        {{-- BOTÓN --}}

        <button
            type="submit"
            class="login-button"
        >
            Iniciar sesión
        </button>


        {{-- OLVIDÓ CONTRASEÑA --}}

        <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>

    </form>


    {{-- PIE --}}

    <div class="login-footer">

        ORTOMAX<br>

        Sistema de Gestión de Implantes Ortopédicos

    </div>


</div>


<script>

    const togglePassword =
        document.getElementById('togglePassword');

    const password =
        document.getElementById('password');


    togglePassword.addEventListener(
        'click',
        function () {

            const visible =
                password.type === 'text';


            password.type =
                visible ? 'password' : 'text';


            this.textContent =
                visible ? '👁' : '🙈';

        }
    );

</script>


</body>

</html>