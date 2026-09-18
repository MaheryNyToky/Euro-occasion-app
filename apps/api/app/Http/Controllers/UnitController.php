<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * List all units and their conversions.
     */
    public function index(Request $request): JsonResponse
    {
        $units = Unit::with(['conversionsFrom.toUnit', 'conversionsTo.fromUnit'])
            ->where('is_active', true)
            ->orderBy('code', 'asc')
            ->get();

        return response()->json([
            'data' => $units,
        ]);
    }

    /**
     * Store a new measurement unit.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? TenantContext::getTenantId();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'precision' => ['nullable', 'integer', 'min:0', 'max:6'],
            'is_base' => ['boolean'],
        ]);

        $unit = Unit::create($validated);

        AuditService::log(
            action: 'catalog.unit_created',
            auditable: $unit,
            after: $unit->toArray(),
            reason: 'Création d\'une unité de mesure'
        );

        return response()->json([
            'data' => $unit,
        ], 201);
    }

    /**
     * Store a conversion rate between two units.
     */
    public function storeConversion(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? TenantContext::getTenantId();

        $validated = $request->validate([
            'from_unit_id' => ['required', 'uuid', 'exists:units,id'],
            'to_unit_id' => ['required', 'uuid', 'exists:units,id', 'different:from_unit_id'],
            'factor' => ['required', 'numeric', 'gt:0'],
        ]);

        // Ensure unique conversion pair for this tenant
        $conversion = UnitConversion::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'from_unit_id' => $validated['from_unit_id'],
                'to_unit_id' => $validated['to_unit_id'],
            ],
            [
                'factor' => $validated['factor'],
                'is_active' => true,
            ]
        );

        AuditService::log(
            action: 'catalog.unit_conversion_saved',
            auditable: $conversion,
            after: $conversion->toArray(),
            reason: 'Enregistrement facteur de conversion d\'unités'
        );

        return response()->json([
            'data' => $conversion->load(['fromUnit', 'toUnit']),
        ], 201);
    }
}
