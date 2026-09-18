<?php

namespace App\Http\Controllers;

use App\Http\Requests\Catalog\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
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
        ])->withSum('stockBalances as total_on_hand', 'on_hand');

        if ($request->filled('search')) {
            $search = '%' . strtolower($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(manufacturer) LIKE ?', [$search])
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
            $productData = $request->safe()->except(['variants', 'quantity', 'category_name']);

            // Auto-resolve or create category by name if category_id is omitted
            if (empty($productData['category_id']) && $request->filled('category_name')) {
                $catName = trim($request->input('category_name'));
                $category = Category::whereRaw('LOWER(name) = ?', [strtolower($catName)])->first();
                if (!$category) {
                    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $catName), 0, 6));
                    if (empty($prefix)) {
                        $prefix = 'CAT';
                    }
                    $code = $prefix . '-' . mt_rand(100, 999);
                    $category = Category::create([
                        'code' => $code,
                        'name' => $catName,
                        'is_active' => true,
                    ]);
                }
                $productData['category_id'] = $category->id;
            }

            // Auto-generate unique SKU if omitted
            if (empty($productData['sku'])) {
                $skuPrefix = 'SKU';
                if (!empty($productData['manufacturer'])) {
                    $cleanMfr = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productData['manufacturer']), 0, 3));
                    if (!empty($cleanMfr)) {
                        $skuPrefix .= "-{$cleanMfr}";
                    }
                }
                do {
                    $generatedSku = $skuPrefix . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
                } while (Product::where('sku', $generatedSku)->exists());

                $productData['sku'] = $generatedSku;
            }

            $product = Product::create($productData);

            // Optional variants
            if ($request->filled('variants') && is_array($request->input('variants'))) {
                foreach ($request->input('variants') as $variantInput) {
                    $variantSku = $variantInput['sku'] ?? ($product->sku . '-V' . mt_rand(10, 99));
                    $product->variants()->create([
                        'tenant_id' => $product->tenant_id,
                        'sku' => $variantSku,
                        'barcode' => $variantInput['barcode'] ?? null,
                        'qr_code' => $variantInput['qr_code'] ?? null,
                        'name' => $variantInput['name'] ?? null,
                        'attribute_values' => $variantInput['attribute_values'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }

            // Initial stock quantity reception if quantity > 0
            $initialQty = (float) $request->input('quantity', 0);
            if ($initialQty > 0) {
                $site = Site::firstOrCreate(
                    ['tenant_id' => $product->tenant_id, 'code' => 'SITE-MAIN'],
                    ['name' => 'Site Principal', 'is_active' => true]
                );

                $warehouse = Warehouse::firstOrCreate(
                    ['tenant_id' => $product->tenant_id, 'code' => 'WH-MAIN'],
                    ['site_id' => $site->id, 'name' => 'Magasin Principal', 'is_active' => true]
                );

                StockBalance::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'on_hand' => $initialQty,
                ]);

                StockMovement::create([
                    'tenant_id' => $product->tenant_id,
                    'type' => 'receipt',
                    'product_id' => $product->id,
                    'quantity' => $initialQty,
                    'base_unit_id' => $product->base_unit_id,
                    'destination_warehouse_id' => $warehouse->id,
                    'reason' => 'Stock initial à la création du produit',
                    'actor_id' => auth()->id(),
                ]);
            }

            return $product->load(['baseUnit', 'category', 'variants'])->loadSum('stockBalances as total_on_hand', 'on_hand');
        });

        AuditService::log(
            action: 'catalog.product_created',
            auditable: $product,
            after: $product->toArray(),
            reason: 'Création produit et entrée en stock'
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
        ])->withSum('stockBalances as total_on_hand', 'on_hand')->findOrFail($id);

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
            'manufacturer' => ['nullable', 'string', 'max:100'],
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
            'data' => $product->fresh(['baseUnit', 'category', 'variants'])->loadSum('stockBalances as total_on_hand', 'on_hand'),
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
