<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductUnit;
use App\Models\StockOut;
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

    public function exportOut(Request $request)
    {
       $rows = DB::table('product_units as pu')
            ->leftJoin('stock_out_items as soi', 'soi.id', '=', 'pu.stock_out_item_id')
            ->leftJoin('stock_outs as so', 'so.id', '=', 'soi.stock_out_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'pu.warehouse_id')
            ->leftJoin('buyers as b', 'b.id', '=', 'so.buyer_id')
            ->leftJoin('products as p', 'p.id', '=', 'pu.product_id')
            ->where('pu.status', 'SOLD')

            // optional filter (aman, tidak bikin data hilang)
            ->when($request->warehouse_id, fn ($q) =>
                $q->where('pu.warehouse_id', $request->warehouse_id)
            )
            ->when($request->buyer_id, fn ($q) =>
                $q->where('so.buyer_id', $request->buyer_id)
            )
            ->when($request->date_from, fn ($q) =>
                $q->whereDate('so.date_out', '>=', $request->date_from)
            )
            ->when($request->date_to, fn ($q) =>
                $q->whereDate('so.date_out', '<=', $request->date_to)
            )

            ->select([
                DB::raw("DATE(so.date_out) as Tanggal"),
                'so.reference as No Referensi',
                'w.name as Gudang',
                'b.name as Buyer',
                'p.name as Produk',
                'p.sku as SKU',
                'pu.unit_code as Unit Code',
                'so.note as Catatan',
            ])
            ->orderBy('pu.id')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $rows->count(),
            'data' => $rows,
        ]);
    }

    public function exportUnits(Request $request)
    {
        $query = ProductUnit::query()
            ->with([
                'product:id,sku,name',
                'warehouse:id,name',
                'stockInItem.stockIn:id,date_in,reference,created_by',
                'stockInItem.stockIn.user:id,name',
            ])
            ->whereNotNull('stock_in_item_id');

        if ($request->filled('date_from')) {
            $query->whereHas('stockInItem.stockIn', function ($q) use ($request) {
                $q->whereDate('date_in', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->whereHas('stockInItem.stockIn', function ($q) use ($request) {
                $q->whereDate('date_in', '<=', $request->date_to);
            });
        }

        $units = $query
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($unit) {
                return [
                    'tanggal_masuk' => optional($unit->stockInItem?->stockIn?->date_in)?->format('Y-m-d'),
                    'reference' => $unit->stockInItem?->stockIn?->reference,
                    'sku' => $unit->product?->sku,
                    'product_name' => $unit->product?->name,
                    'unit_code' => $unit->unit_code,
                    'warehouse' => $unit->warehouse?->name,
                    'status' => $unit->status,
                    'created_by' => $unit->stockInItem?->stockIn?->user?->name,
                ];
            });

        return response()->json([
            'data' => $units,
        ]);
    }
}
