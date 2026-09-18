<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * List customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = '%' . strtolower($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(company_name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(code) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(first_name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(last_name) LIKE ?', [$search]);
            });
        }

        $customers = $query->orderBy('code', 'asc')->paginate(25);

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Create a customer.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? TenantContext::getTenantId();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'code')->where('tenant_id', $tenantId),
            ],
            'company_name' => ['nullable', 'string', 'max:200'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $customer = Customer::create($validated);

        AuditService::log(
            action: 'partners.customer_created',
            auditable: $customer,
            after: $customer->toArray(),
            reason: 'Création fiche client'
        );

        return response()->json([
            'data' => $customer,
        ], 201);
    }
}
