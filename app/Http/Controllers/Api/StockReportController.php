<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    /**
     * Summary stok per gudang & per produk.
     * Optional filter: ?warehouse_id=1
     */
    public function summary(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');

        $query = DB::table('product_units')
            ->join('warehouses', 'product_units.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'product_units.product_id', '=', 'products.id')
            ->select(
                'product_units.warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouses.code as warehouse_code',
                'product_units.product_id',
                'products.sku as product_sku',
                'products.name as product_name',
                DB::raw('COUNT(*) as qty_in_stock')
            )
            ->where('product_units.status', 'IN_STOCK')
            ->groupBy(
                'product_units.warehouse_id',
                'warehouses.name',
                'warehouses.code',
                'product_units.product_id',
                'products.sku',
                'products.name'
            )
            ->orderBy('warehouses.name')
            ->orderBy('products.name');

        if ($warehouseId) {
            $query->where('product_units.warehouse_id', $warehouseId);
        }

        $rows = $query->get();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function stockOutSummary(Request $request)
    {
        $from        = $request->query('from');         
        $to          = $request->query('to');           
        $warehouseId = $request->query('warehouse_id'); 

        $query = DB::table('stock_out_items')
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->join('warehouses', 'stock_outs.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'stock_out_items.product_id', '=', 'products.id')
            ->select(
                'stock_outs.warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouses.code as warehouse_code',
                'stock_out_items.product_id',
                'products.sku as product_sku',
                'products.name as product_name',
                DB::raw('SUM(stock_out_items.qty) as qty_out'),
                DB::raw('SUM(stock_out_items.subtotal) as total_sales')
            )
            ->groupBy(
                'stock_outs.warehouse_id',
                'warehouses.name',
                'warehouses.code',
                'stock_out_items.product_id',
                'products.sku',
                'products.name'
            )
            ->orderBy('warehouses.name')
            ->orderBy('products.name');

        if ($from) {
            $query->whereDate('stock_outs.date_out', '>=', $from);
        }

        if ($to) {
            $query->whereDate('stock_outs.date_out', '<=', $to);
        }

        if ($warehouseId) {
            $query->where('stock_outs.warehouse_id', $warehouseId);
        }

        $rows = $query->get();

        return response()->json([
            'data' => $rows,
        ]);
    }
}
