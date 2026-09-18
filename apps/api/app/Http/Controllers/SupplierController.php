<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * List suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = '%' . strtolower($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(company_name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(code) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(contact_name) LIKE ?', [$search]);
            });
        }

        $suppliers = $query->orderBy('company_name', 'asc')->paginate(25);

        return response()->json([
            'data' => $suppliers->items(),
            'meta' => [
                'total' => $suppliers->total(),
            ],
        ]);
    }

    /**
     * Create a supplier.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? TenantContext::getTenantId();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('suppliers', 'code')->where('tenant_id', $tenantId),
            ],
            'company_name' => ['required', 'string', 'max:200'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $supplier = Supplier::create($validated);

        AuditService::log(
            action: 'partners.supplier_created',
            auditable: $supplier,
            after: $supplier->toArray(),
            reason: 'Création fiche fournisseur'
        );

        return response()->json([
            'data' => $supplier,
        ], 201);
    }
}
