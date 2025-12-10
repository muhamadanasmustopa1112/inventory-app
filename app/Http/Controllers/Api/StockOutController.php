<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockOut;
use App\Models\StockOutItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockOutController extends Controller
{
   
    public function index(Request $request)
    {
        $query = StockOut::with(['warehouse', 'buyer', 'items.product'])
            ->orderByDesc('date_out');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('buyer_id')) {
            $query->where('buyer_id', $request->buyer_id);
        }

        return $query->paginate(20);
    }



    public function scanQr(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = $data['code'];

        $unit = ProductUnit::with(['product', 'warehouse'])
            ->where('unit_code', $code)
            ->orWhere('qr_value', $code)
            ->first();

        if (! $unit) {
            return response()->json([
                'message' => 'Unit not found',
            ], 404);
        }

        if ($unit->status !== 'IN_STOCK') {
            return response()->json([
                'message' => 'Unit is not available (status: ' . $unit->status . ')',
            ], 409);
        }

        
        return response()->json([
            'message' => 'Unit is available',
            'unit'    => $unit,
        ]);
    }

    public function storeFromUnits(Request $request)
    {
        $data = $request->validate([
            'warehouse_id'  => ['required', 'exists:warehouses,id'],
            'buyer'         => ['required', 'array'],
            'buyer.name'    => ['required', 'string'],
            'buyer.phone'   => ['nullable', 'string'],
            'buyer.address' => ['nullable', 'string'],

            'date_out'      => ['required', 'date'],
            'reference'     => ['required', 'string', 'max:191', Rule::unique('stock_outs', 'reference')],
            'note'          => ['nullable', 'string'],

            'units'         => ['required', 'array', 'min:1'],
            'units.*'       => ['required', 'integer', 'exists:product_units,id'],
        ]);

        $userId = Auth::id();

        try {
            $stockOut = null;

            $buyer = Buyer::create([
                'name'    => $data['buyer']['name'],
                'phone'   => $data['buyer']['phone'] ?? null,
                'address' => $data['buyer']['address'] ?? null,
            ]);

            DB::transaction(function () use ($data, $userId, &$stockOut, $buyer) {
                $units = ProductUnit::with('product')
                    ->whereIn('id', $data['units'])
                    ->lockForUpdate()
                    ->get();

                if ($units->isEmpty()) {
                    throw new \RuntimeException('Tidak ada unit yang valid.');
                }

                foreach ($units as $unit) {
                    if ($unit->status !== 'IN_STOCK') {
                        throw new \RuntimeException(
                            'Ada unit yang tidak tersedia (status: ' . $unit->status . ', unit ID: ' . $unit->id . ')'
                        );
                    }
                }

                $warehouseId = $data['warehouse_id'];

                $differentWarehouse = $units->firstWhere('warehouse_id', '!=', $warehouseId);
                if ($differentWarehouse) {
                    throw new \RuntimeException(
                        'Ada unit yang bukan dari gudang yang dipilih (unit ID: ' . $differentWarehouse->id . ').'
                    );
                }

                $stockOut = StockOut::create([
                    'warehouse_id' => $warehouseId,
                    'buyer_id'     => $buyer->id,
                    'date_out'     => $data['date_out'],
                    'reference'     => $data['reference'],
                    'total_price'  => 0,
                    'note'         => $data['note'] ?? null,
                    'created_by'   => $userId,
                ]);

                $total = 0;

                $grouped = $units->groupBy('product_id');

                foreach ($grouped as $productId => $groupUnits) {
                    $qty = $groupUnits->count();

                    $product = $groupUnits->first()->product
                        ?? Product::find($productId);

                    $sellPrice = (float) ($product->default_sell_price ?? 0);
                    $subtotal  = $qty * $sellPrice;

                    $stockOutItem = StockOutItem::create([
                        'stock_out_id' => $stockOut->id,
                        'product_id'   => $productId,
                        'qty'          => $qty,
                        'sell_price'   => $sellPrice,
                        'subtotal'     => $subtotal,
                    ]);

                    ProductUnit::whereIn('id', $groupUnits->pluck('id'))
                        ->update([
                            'status'            => 'SOLD',
                            'stock_out_item_id' => $stockOutItem->id,
                        ]);

                    $total += $subtotal;
                }

                $stockOut->update([
                    'total_price' => $total,
                ]);
            });

            $stockOut->load(['warehouse', 'buyer', 'items.product']);

            return response()->json([
                'message'   => 'Stock out created successfully from scanned units',
                'stock_out' => $stockOut,
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create stock out',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
