<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DocenteAprobado
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user()?->rol === 'docente' && ! $request->user()->aprobado_at, 403, 'Tu cuenta docente está pendiente de aprobación por el director.');

        return $next($request);
    }
}
