<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductUnit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock_in_item_id',
        'unit_code',
        'qr_value',
        'status',
        'stock_out_item_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockInItem()
    {
        return $this->belongsTo(StockInItem::class);
    }

    public function stockOutItem()
    {
        return $this->belongsTo(StockOutItem::class);
    }
}
