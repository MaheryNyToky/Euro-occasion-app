<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
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

        // Check user active status
        if ($user->status !== 'active') {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Votre compte utilisateur est inactif ou désactivé.',
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 403);
        }

        // Check tenant status
        $tenant = $user->tenant;
        if (!$tenant || $tenant->status !== 'active') {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Le compte entreprise (tenant) est inactif ou suspendu.',
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 403);
        }

        // Set global tenant context for all domain operations in this request
        TenantContext::setTenantId($user->tenant_id);

        // Verify device revocation status if X-Device-Id is supplied
        $deviceIdentifier = $request->header('X-Device-Id');
        if ($deviceIdentifier) {
            $device = Device::withoutGlobalScopes()
                ->where('tenant_id', $user->tenant_id)
                ->where('device_identifier', $deviceIdentifier)
                ->first();

            if ($device && $device->is_revoked) {
                return response()->json([
                    'error' => [
                        'code' => 403,
                        'message' => 'Cet appareil a été révoqué par la direction.',
                        'request_id' => $request->attributes->get('request_id'),
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}
