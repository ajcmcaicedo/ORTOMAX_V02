<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva contraseña - {{ config('ortomax.empresa') }}</title>

    <link rel="icon" type="image/png" href="{{ asset(config('ortomax.logo')) }}">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #eef7fc, #f8fbfd);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 390px;
        }

        .reset-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 38px 32px 28px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.10);
        }

        .logo {
            text-align: center;
            margin-bottom: 18px;
        }

        .logo img {
            width: 94px;
            height: 86px;
            object-fit: contain;
        }

        .company-name {
            text-align: center;
            color: #0879c9;
            font-size: 27px;
            font-weight: bold;
            margin-top: 4px;
        }

        .company-description {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 2px;
            margin-bottom: 28px;
        }

        h1 {
            text-align: center;
            color: #222;
            font-size: 21px;
            margin: 0 0 10px;
        }

        .description {
            text-align: center;
            color: #777;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            height: 45px;
            border: 1px solid #cfd8df;
            border-radius: 8px;
            padding: 0 13px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: #0d9bd7;
            box-shadow: 0 0 0 3px rgba(13, 155, 215, 0.12);
        }

        .btn-reset {
            width: 100%;
            height: 44px;
            border: none;
            border-radius: 8px;
            background: #078dcc;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-reset:hover {
            background: #067db4;
        }

        .back-login {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #078dcc;
            text-decoration: none;
            font-size: 13px;
        }

        .back-login:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 11px 13px;
            border-radius: 7px;
            font-size: 13px;
            margin-bottom: 18px;
            line-height: 1.4;
        }

        .alert-error {
            background: #fff1f1;
            border: 1px solid #f0b7b7;
            color: #a33;
        }

        .footer {
            margin-top: 28px;
            padding-top: 17px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
            color: #9a9a9a;
            font-size: 11px;
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .reset-card {
                padding: 30px 22px 24px;
            }

            .company-name {
                font-size: 24px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<div class="reset-container">

    <div class="reset-card">

        <div class="logo">
            <img src="{{ asset(config('ortomax.logo')) }}" alt="Logo ORTOMAX">
        </div>

        <div class="company-name">
            {{ config('ortomax.empresa') }}
        </div>

        <div class="company-description">
            {{ config('ortomax.nombre') }}
        </div>

        <h1>Crear nueva contraseña</h1>

        <div class="description">
            Ingresa tu nueva contraseña para recuperar el acceso a tu cuenta.
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Correo electrónico</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ $email }}"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Nueva contraseña</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar contraseña</label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-reset">
                Cambiar contraseña
            </button>
        </form>

        <a href="{{ route('login') }}" class="back-login">
            ← Volver al inicio de sesión
        </a>

        <div class="footer">
            {{ config('ortomax.empresa') }}<br>
            {{ config('ortomax.descripcion') }}
        </div>

    </div>

</div>

</body>
</html>