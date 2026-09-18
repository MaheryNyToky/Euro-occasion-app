<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Non authentifié.',
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        }

        // Check if scope constraints are present in the request query or route
        $scopeType = $request->route('scope_type') ?? $request->input('scope_type');
        $scopeId = $request->route('scope_id') ?? $request->input('scope_id');

        if (!$user->hasPermissionTo($permission, $scopeType, $scopeId)) {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => "Accès refusé : la permission '{$permission}' est requise pour cette opération.",
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 403);
        }

        return $next($request);
    }
}
