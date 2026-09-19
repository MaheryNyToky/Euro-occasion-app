<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockBalanceController extends Controller
{
    /**
     * List stock balances with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockBalance::with([
            'product:id,tenant_id,sku,name',
            'variant:id,tenant_id,product_id,sku,name',
            'warehouse:id,tenant_id,code,name',
            'location:id,tenant_id,warehouse_id,code,name',
        ])->orderBy('updated_at', 'desc');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('variant_id')) {
            $query->where('variant_id', $request->input('variant_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if (filter_var($request->input('available_only', false), FILTER_VALIDATE_BOOLEAN)) {
            // available = on_hand - reserved - damaged > 0
            $query->whereRaw('(on_hand - reserved - damaged) > 0');
        }

        $limit = min((int) $request->input('limit', 25), 100);
        $balances = $query->paginate($limit);

        $items = collect($balances->items())->map(function (StockBalance $balance) {
            $arr = $balance->toArray();
            $arr['available'] = $balance->available;
            return $arr;
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $balances->currentPage(),
                'per_page'     => $balances->perPage(),
                'total'        => $balances->total(),
                'last_page'    => $balances->lastPage(),
            ],
        ]);
    }
}
