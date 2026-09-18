<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user with tenant, credentials and device tracking.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $tenantCode = strtolower(trim($request->input('tenant_code')));
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $tenant = Tenant::whereRaw('LOWER(code) = ?', [$tenantCode])->first();

        if (!$tenant || $tenant->status !== 'active') {
            RateLimiter::hit($request->throttleKey());
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Identifiants ou entreprise invalides.',
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        }

        // Set tenant context to query users of this tenant
        TenantContext::setTenantId($tenant->id);

        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($request->throttleKey());
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Identifiants ou entreprise invalides.',
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        }

        if ($user->status !== 'active') {
            RateLimiter::hit($request->throttleKey());
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Ce compte utilisateur a été désactivé.',
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 403);
        }

        RateLimiter::clear($request->throttleKey());

        // Register or update device if provided
        $device = null;
        $deviceIdentifier = $request->input('device_identifier');
        if (!empty($deviceIdentifier)) {
            $device = Device::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
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

            if (!$device) {
                $device = Device::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'device_identifier' => $deviceIdentifier,
                    'name' => $request->input('device_name', 'Terminal inconnu'),
                    'platform' => $request->input('platform', 'web'),
                    'app_version' => $request->input('app_version'),
                    'last_synced_at' => now(),
                    'last_ip' => $request->ip(),
                ]);
            } else {
                $device->update([
                    'user_id' => $user->id,
                    'name' => $request->input('device_name', $device->name),
                    'platform' => $request->input('platform', $device->platform),
                    'app_version' => $request->input('app_version', $device->app_version),
                    'last_synced_at' => now(),
                    'last_ip' => $request->ip(),
                ]);
            }
        }

        // Create Sanctum personal access token
        $tokenName = $device ? "device:{$device->name}" : 'api_token';
        $tokenResult = $user->createToken($tokenName);

        // Record audit event
        AuditService::log(
            action: 'auth.login',
            auditable: $user,
            after: [
                'device_id' => $device?->id,
                'ip' => $request->ip(),
            ],
            reason: 'Connexion utilisateur réussie',
            tenantId: $tenant->id,
            userId: $user->id,
            deviceId: $device?->id
        );

        return response()->json([
            'data' => [
                'token' => $tokenResult->plainTextToken,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'permissions' => $user->getAllPermissions(),
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'code' => $tenant->code,
                    'name' => $tenant->name,
                    'accounting_currency' => $tenant->accounting_currency,
                    'timezone' => $tenant->timezone,
                ],
                'device' => $device ? [
                    'id' => $device->id,
                    'device_identifier' => $device->device_identifier,
                    'name' => $device->name,
                    'platform' => $device->platform,
                ] : null,
            ],
        ]);
    }

    /**
     * Refresh personal access token: revoke the current one and issue a new one.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $tokenName = $currentToken ? $currentToken->name : 'refreshed_token';

        // Revoke the old token
        $currentToken?->delete();

        // Issue new token
        $newToken = $user->createToken($tokenName);

        return response()->json([
            'data' => [
                'token' => $newToken->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Log out current session/device by revoking the active token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke token
        $request->user()->currentAccessToken()?->delete();

        AuditService::log(
            action: 'auth.logout',
            auditable: $user,
            reason: 'Déconnexion utilisateur volontaire'
        );

        return response()->json([
            'data' => [
                'message' => 'Déconnexion réussie.',
            ],
        ]);
    }

    /**
     * Return authenticated user profile and permissions.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'tenant' => [
                    'id' => $tenant->id,
                    'code' => $tenant->code,
                    'name' => $tenant->name,
                    'accounting_currency' => $tenant->accounting_currency,
                    'timezone' => $tenant->timezone,
                ],
                'permissions' => $user->getAllPermissions(),
            ],
        ]);
    }
}
