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

    public function export(Request $request)
    {
        $stockOuts = StockOut::with([
            'warehouse:id,name',
            'buyer:id,name',
            'items.product:id,name,sku',
            'items.productUnits:id,stock_out_item_id,unit_code'
        ])
        ->when($request->warehouse_id, fn ($q) =>
            $q->where('warehouse_id', $request->warehouse_id)
        )
        ->when($request->buyer_id, fn ($q) =>
            $q->where('buyer_id', $request->buyer_id)
        )
        ->when($request->date_from, fn ($q) =>
            $q->whereDate('date_out', '>=', $request->date_from)
        )
        ->when($request->date_to, fn ($q) =>
            $q->whereDate('date_out', '<=', $request->date_to)
        )
        ->orderBy('date_out', 'desc')
        ->get();

        $rows = [];

        foreach ($stockOuts as $stockOut) {
            foreach ($stockOut->items as $item) {
                foreach ($item->productUnits as $unit) {
                    $rows[] = [
                        'Tanggal'      => $stockOut->date_out->format('Y-m-d'),
                        'No Referensi' => $stockOut->reference,
                        'Gudang'       => $stockOut->warehouse?->name,
                        'Buyer'        => $stockOut->buyer?->name,
                        'Produk'       => $item->product?->name,
                        'SKU'          => $item->product?->sku,
                        'Unit Code'    => $unit->unit_code,
                        'Catatan'      => $stockOut->note,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $rows
        ]);
    }

    public function exportStockOutUnits(Request $request)
    {
        $rows = DB::table('product_units as pu')
            ->join('products as p', 'p.id', '=', 'pu.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pu.warehouse_id')
            ->leftJoin('stock_out_items as soi', 'soi.id', '=', 'pu.stock_out_item_id')
            ->leftJoin('stock_outs as so', 'so.id', '=', 'soi.stock_out_id')
            ->leftJoin('buyers as b', 'b.id', '=', 'so.buyer_id')
            ->where('pu.status', 'SOLD')
            ->whereNotNull('pu.stock_out_item_id')
            ->select(
                'pu.unit_code as unit_code',
                'p.name as produk',
                'p.sku as sku',
                'w.name as gudang',
                DB::raw('COALESCE(b.name, "-") as buyer'),
                DB::raw('COALESCE(so.reference, "-") as no_referensi'),
                DB::raw('COALESCE(so.note, "-") as catatan'),
                DB::raw('COALESCE(so.created_at, pu.updated_at) as tanggal_transaksi')
            )
            ->orderBy('tanggal_transaksi', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $rows->count(),
            'data'    => $rows,
        ]);
    }
}
