<?php

namespace App\Http\Controllers;

use App\Http\Requests\Catalog\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * List products with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with([
            'baseUnit:id,tenant_id,code,name,precision',
            'category:id,tenant_id,code,name',
            'variants:id,tenant_id,product_id,sku,name,is_active',
        ]);

        if ($request->filled('search')) {
            $search = '%' . strtolower($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(reference) LIKE ?', [$search]);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('state')) {
            $query->where('state', $request->input('state'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $limit = min((int) $request->input('limit', 25), 100);
        $products = $query->orderBy('created_at', 'desc')->paginate($limit);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new product and optional variants transactionally.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $productData = $request->safe()->except('variants');
            $product = Product::create($productData);

            if ($request->filled('variants') && is_array($request->input('variants'))) {
                foreach ($request->input('variants') as $variantInput) {
                    $product->variants()->create([
                        'tenant_id' => $product->tenant_id,
                        'sku' => $variantInput['sku'],
                        'barcode' => $variantInput['barcode'] ?? null,
                        'qr_code' => $variantInput['qr_code'] ?? null,
                        'name' => $variantInput['name'] ?? null,
                        'attribute_values' => $variantInput['attribute_values'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }

            return $product->load(['baseUnit', 'category', 'variants']);
        });

        AuditService::log(
            action: 'catalog.product_created',
            auditable: $product,
            after: $product->toArray(),
            reason: 'Création produit dans le catalogue'
        );

        return response()->json([
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with([
            'baseUnit:id,tenant_id,code,name,precision',
            'category:id,tenant_id,code,name',
            'variants',
        ])->findOrFail($id);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $beforeState = $product->toArray();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'state' => ['sometimes', 'string', 'in:new,used,refurbished,damaged'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($validated);

        AuditService::log(
            action: 'catalog.product_updated',
            auditable: $product,
            before: $beforeState,
            after: $product->toArray(),
            reason: 'Modification fiche produit'
        );

        return response()->json([
            'data' => $product->fresh(['baseUnit', 'category', 'variants']),
        ]);
    }

    /**
     * Archive product without destructive deletion.
     */
    public function archive(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $beforeState = $product->toArray();

        $product->archive();

        AuditService::log(
            action: 'catalog.product_archived',
            auditable: $product,
            before: $beforeState,
            reason: $request->input('reason', 'Archivage produit demandé')
        );

        return response()->json([
            'data' => [
                'message' => 'Produit archivé avec succès.',
            ],
        ]);
    }
}
