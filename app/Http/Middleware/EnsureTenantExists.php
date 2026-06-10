<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class EnsureTenantExists
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['message' => 'ID de tenant requerido en la cabecera X-Tenant-ID o tenant_id.'], 422);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant no encontrado.'], 404);
        }

        if ($request->user() && ! $request->user()->belongsToTenant($tenant)) {
            return response()->json(['message' => 'Usuario no autorizado para este tenant.'], 403);
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
