<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Verifica que el usuario tenga el permiso solicitado.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $permiso
    ): Response {
        if (!$request->user()) {
            abort(401);
        }

        if (!$request->user()->tienePermiso($permiso)) {
            abort(403, 'No tiene permiso para realizar esta operacion.');
        }

        return $next($request);
    }
}