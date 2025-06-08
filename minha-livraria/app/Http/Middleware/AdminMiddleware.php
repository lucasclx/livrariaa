<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se o usuário está autenticado
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Você precisa estar logado para acessar esta área.');
        }

        // Verificar se o usuário é admin
        // Por enquanto, vamos usar um campo 'is_admin' na tabela users
        // ou você pode usar um sistema de roles mais complexo
        if (!auth()->user()->is_admin) {
            abort(403, 'Acesso negado. Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}