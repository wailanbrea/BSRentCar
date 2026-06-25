<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que el usuario autenticado (sesión web) tenga acceso al panel.
 * Solo admin y staff. Ver docs/08_ADMIN_PANEL.md y docs/11_SECURITY.md.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['admin', 'staff'])) {
            abort(403, 'No tienes acceso al panel administrativo.');
        }

        return $next($request);
    }
}
