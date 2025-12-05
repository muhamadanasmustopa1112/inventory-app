<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;


class StockOutItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'stock_out_id',
        'product_id',
        'qty',
        'sell_price',
        'subtotal',
    ];

    public function stockOut()
    {
        return $this->belongsTo(StockOut::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }
}
