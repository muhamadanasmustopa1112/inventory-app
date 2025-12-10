<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockIn;
use App\Models\StockInItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockInController extends Controller
{
    /**
     * List data stock in (bisa difilter per gudang).
     */
    public function index(Request $request)
    {
        $query = StockIn::with(['warehouse', 'items.product'])
            ->orderByDesc('date_in');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        return $query->paginate(20);
    }

    /**
     * Simpan transaksi barang masuk.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id'         => ['required', 'exists:warehouses,id'],
            'date_in'              => ['required', 'date'],
            'reference'            => ['nullable', 'string'],
            'note'                 => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'exists:products,id'],
            'items.*.qty'          => ['required', 'integer', 'min:1'],
            'items.*.sell_price'   => ['nullable', 'numeric', 'min:0'],
            'items.*.buy_price'    => ['nullable', 'numeric', 'min:0'],
        ]);

        $userId = Auth::id();

        $stockIn = null;
        $totalUnits = 0;

        DB::transaction(function () use ($data, $userId, &$stockIn, &$totalUnits) {
            // 1) Simpan header stock_in
            $stockIn = StockIn::create([
                'warehouse_id' => $data['warehouse_id'],
                'date_in'      => $data['date_in'],
                'reference'    => $data['reference'] ?? null,
                'note'         => $data['note'] ?? null,
                'created_by'   => $userId,
            ]);

            // 2) Loop items
            foreach ($data['items'] as $itemData) {
                $stockInItem = StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id'  => $itemData['product_id'],
                    'qty'         => $itemData['qty'],
                    'sell_price'  => $itemData['sell_price'],
                    'buy_price'   => $itemData['buy_price'] ?? null,
                ]);

                // 3) Generate product units untuk item ini
                $unitsCreated = $this->generateProductUnitsForItem($stockInItem, $data['warehouse_id']);
                $totalUnits += $unitsCreated;
            }
        });

        // eager load untuk respon
        $stockIn->load(['warehouse', 'items.product']);

        return response()->json([
            'message'      => 'Stock in created successfully',
            'stock_in'     => $stockIn,
            'total_units'  => $totalUnits,
        ], 201);
    }

    /**
     * Generate unit stok per 1 pcs untuk 1 baris stock_in_item.
     *
     * @return int jumlah unit yang dibuat
     */
    protected function generateProductUnitsForItem(StockInItem $stockInItem, int $warehouseId): int
    {
        $product = Product::findOrFail($stockInItem->product_id);

        // Hitung sudah ada berapa unit untuk produk ini
        $existingCount = ProductUnit::where('product_id', $product->id)->count();

        $unitsToCreate = [];

        for ($i = 1; $i <= $stockInItem->qty; $i++) {
            $sequenceNumber = $existingCount + $i; // lanjut nomor sebelumnya
            $suffix = str_pad($sequenceNumber, 6, '0', STR_PAD_LEFT); // 000001, 000002, ...
            $unitCode = $product->sku . '-' . $suffix;

            $unitsToCreate[] = [
                'product_id'       => $product->id,
                'warehouse_id'     => $warehouseId,
                'stock_in_item_id' => $stockInItem->id,
                'unit_code'        => $unitCode,
                'qr_value'         => $unitCode, // nanti bisa diganti URL kalau mau
                'status'           => 'IN_STOCK',
                'stock_out_item_id'=> null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        ProductUnit::insert($unitsToCreate);

        return count($unitsToCreate);
    }

    /**
 * List semua unit (QR) untuk 1 stock_in (transaksi barang masuk).
 */
    public function units(StockIn $stockIn)
    {
        $units = $stockIn->units()
            ->with(['product', 'warehouse'])
            ->orderBy('unit_code')
            ->get();

        return response()->json([
            'stock_in_id'   => $stockIn->id,
            'reference'     => $stockIn->reference,
            'warehouse'     => $stockIn->warehouse, 
            'date_in'       => $stockIn->date_in,
            'total_units'   => $units->count(),
            'units'         => $units,
        ]);
    }

}
