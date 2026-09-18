<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * List registered devices for the active tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $devices = Device::with('user:id,tenant_id,name,email')
            ->orderBy('last_synced_at', 'desc')
            ->get();

        return response()->json([
            'data' => $devices,
        ]);
    }

    /**
     * Revoke an active device.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $device = Device::findOrFail($id);

        $beforeState = $device->toArray();

        $device->update([
            'is_revoked' => true,
        ]);

        // Revoke any personal access tokens named after this device
        if ($device->user) {
            $device->user->tokens()
                ->where('name', "device:{$device->name}")
                ->delete();
        }

        AuditService::log(
            action: 'device.revoked',
            auditable: $device,
            before: $beforeState,
            after: $device->toArray(),
            reason: $request->input('reason', 'Révocation demandée par administrateur/utilisateur')
        );

        return response()->json([
            'data' => [
                'message' => 'Appareil révoqué avec succès.',
                'device' => $device,
            ],
        ]);
    }
}
