<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerfilMiddleware
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $perfilUsuario = auth()->user()->perfil;

        if (!in_array($perfilUsuario, $perfis)) {
            return abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}