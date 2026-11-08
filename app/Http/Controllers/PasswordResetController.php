<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function mostrarFormulario()
    {
        return view('auth.forgot-password');
    }

    public function enviarEnlace(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with(
                'status',
                'Hemos enviado un enlace para restablecer tu contraseña a tu correo electrónico.'
            );
        }
        
        return back()->withErrors([
            'email' => match ($status) {
                Password::INVALID_USER =>
                    'No encontramos una cuenta asociada a ese correo electrónico.',
        
                Password::RESET_THROTTLED =>
                    'Por favor, espera un momento antes de volver a solicitar el enlace.',
        
                default =>
                    'No fue posible procesar la solicitud. Por favor, inténtalo nuevamente.',
            },
        ]);
    }

    public function mostrarFormularioReset(string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function actualizarPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Tu contraseña ha sido actualizada correctamente.');
        }

        return back()->withErrors([
            'email' => __($status),
        ]);
    }
}