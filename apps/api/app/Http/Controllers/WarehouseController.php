<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Warehouse::query()
                ->where('is_active', true)
                ->with('site:id,tenant_id,code,name')
                ->orderBy('name')
                ->get(['id', 'tenant_id', 'site_id', 'code', 'name', 'allow_negative_stock']),
        ]);
    }
}
