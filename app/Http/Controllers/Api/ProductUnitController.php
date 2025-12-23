<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
 
    public function index(Request $request)
    {
        $query = ProductUnit::with(['product', 'warehouse', 'stockInItem.stockIn']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unit_code', 'LIKE', "%$search%")
                  ->orWhereHas('product', function ($qq) use ($search) {
                      $qq->where('name', 'LIKE', "%$search%")
                         ->orWhere('sku', 'LIKE', "%$search%");
                  });
            });
        }

        $units = $query->orderBy('id', 'asc')->paginate(10000);

        return response()->json($units);
    }
}
