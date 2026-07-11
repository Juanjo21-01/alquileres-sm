<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsuarioActivoMiddleware
{
    /**
     * Cierra la sesión de un usuario que fue desactivado mientras estaba dentro.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->activo) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Tu cuenta está inactiva. Contacta al administrador.'),
            ]);
        }

        return $next($request);
    }
}
