<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $user = $request->user();

        if (! $user || ! $user->rol) {
            abort(403, 'Acceso denegado.');
        }

        if (! in_array($user->rol->codigo, $rolesPermitidos, true)) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
