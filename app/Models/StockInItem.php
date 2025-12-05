<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;


class StockInItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'stock_in_id',
        'product_id',
        'qty',
        'sell_price',
        'buy_price',
    ];

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class);
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
