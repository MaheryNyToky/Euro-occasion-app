<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * List categories for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ?? TenantContext::getTenantId();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'code')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::create($validated);

        AuditService::log(
            action: 'catalog.category_created',
            auditable: $category,
            after: $category->toArray(),
            reason: 'Création d\'une catégorie'
        );

        return response()->json([
            'data' => $category,
        ], 201);
    }
}
