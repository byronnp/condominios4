<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return response()->json(['message' => 'Tenant no resuelto.'], 500);
        }

        if (! $request->user() || ! $request->user()->hasRole($role, $tenant)) {
            return response()->json(['message' => 'No tienes permiso para acceder a este recurso.'], 403);
        }

        return $next($request);
    }
}
