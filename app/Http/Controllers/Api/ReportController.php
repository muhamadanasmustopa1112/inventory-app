<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockOut;
use App\Models\StockIn;
use App\Models\ProductUnit;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $data = $request->validate([
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'warehouse_id'=> ['nullable', 'integer', 'exists:warehouses,id'],
            'buyer_id'    => ['nullable', 'integer', 'exists:buyers,id'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $data['per_page'] ?? 20;

        $query = StockOut::with(['warehouse', 'buyer', 'items.product'])
            ->orderByDesc('date_out');

        if (! empty($data['date_from'])) {
            $query->whereDate('date_out', '>=', $data['date_from']);
        }

        if (! empty($data['date_to'])) {
            $query->whereDate('date_out', '<=', $data['date_to']);
        }

        if (! empty($data['warehouse_id'])) {
            $query->where('warehouse_id', $data['warehouse_id']);
        }

        if (! empty($data['buyer_id'])) {
            $query->where('buyer_id', $data['buyer_id']);
        }

        // kalau mau batasi berdasarkan user gudang:
        // $user = Auth::user();
        // if ($user && $user->warehouse_id) {
        //     $query->where('warehouse_id', $user->warehouse_id);
        // }

        // paginate data
        $paginator = $query->paginate($perPage);

        // total omset untuk seluruh periode (bukan cuma halaman ini)
        $totalRevenue = (clone $query)->sum('total_price');

        return response()->json([
            'summary' => [
                'total_revenue' => $totalRevenue,
                'date_from'     => $data['date_from'] ?? null,
                'date_to'       => $data['date_to'] ?? null,
                'warehouse_id'  => $data['warehouse_id'] ?? null,
                'buyer_id'      => $data['buyer_id'] ?? null,
            ],
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }


    public function stockIn(Request $request)
    {
        $data = $request->validate([
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'warehouse_id'=> ['nullable', 'integer', 'exists:warehouses,id'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $data['per_page'] ?? 20;

        $query = StockIn::with(['warehouse', 'items.product'])
            ->orderByDesc('date_in');

        if (! empty($data['date_from'])) {
            $query->whereDate('date_in', '>=', $data['date_from']);
        }

        if (! empty($data['date_to'])) {
            $query->whereDate('date_in', '<=', $data['date_to']);
        }

        if (! empty($data['warehouse_id'])) {
            $query->where('warehouse_id', $data['warehouse_id']);
        }

        $paginator = $query->paginate($perPage);

        // hitung total unit yang masuk di periode ini
        $stockInIds = collect($paginator->items())->pluck('id');

        $totalUnitsIn = 0;
        if ($stockInIds->isNotEmpty()) {
            $totalUnitsIn = DB::table('stock_in_items')
                ->whereIn('stock_in_id', $stockInIds)
                ->sum('qty');
        }

        return response()->json([
            'summary' => [
                'total_transactions' => $paginator->total(),
                'total_units_in_page'=> $totalUnitsIn, // total unit di halaman ini (bukan seluruh periode)
                'date_from'          => $data['date_from'] ?? null,
                'date_to'            => $data['date_to'] ?? null,
                'warehouse_id'       => $data['warehouse_id'] ?? null,
            ],
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function stockBalance(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['nullable', 'integer', 'exists:products,id'],
            'category'     => ['nullable', 'string'],
            'search'       => ['nullable', 'string'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $data['per_page'] ?? 20;

        $query = DB::table('product_units')
            ->join('products', 'product_units.product_id', '=', 'products.id')
            ->join('warehouses', 'product_units.warehouse_id', '=', 'warehouses.id')
            ->selectRaw('
                product_units.product_id,
                product_units.warehouse_id,
                COUNT(*) as qty,
                products.sku,
                products.name as product_name,
                products.category,
                warehouses.name as warehouse_name,
                warehouses.city as warehouse_city
            ')
            ->where('product_units.status', 'IN_STOCK')
            ->groupBy(
                'product_units.product_id',
                'product_units.warehouse_id',
                'products.sku',
                'products.name',
                'products.category',
                'warehouses.name',
                'warehouses.city'
            )
            ->orderBy('products.name');

        if (! empty($data['warehouse_id'])) {
            $query->where('product_units.warehouse_id', $data['warehouse_id']);
        }

        if (! empty($data['product_id'])) {
            $query->where('product_units.product_id', $data['product_id']);
        }

        if (! empty($data['category'])) {
            $query->where('products.category', $data['category']);
        }

        if (! empty($data['search'])) {
            $search = '%' . $data['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'LIKE', $search)
                  ->orWhere('products.sku', 'LIKE', $search);
            });
        }

        $paginator = $query->paginate($perPage);

        $rows = $paginator->items();

        $totalQtyAllRows = collect($rows)->sum('qty');

        return response()->json([
            'summary' => [
                'total_rows'   => $paginator->total(),
                'total_qty'    => $totalQtyAllRows,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'product_id'   => $data['product_id'] ?? null,
                'category'     => $data['category'] ?? null,
                'search'       => $data['search'] ?? null,
            ],
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
