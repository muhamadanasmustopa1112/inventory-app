<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'sku',
        'name',
        'default_sell_price',
        'category',
        'description',
        'is_active',
    ];

    public function stockInItems()
    {
        return $this->hasMany(StockInItem::class);
    }

    public function stockOutItems()
    {
        return $this->hasMany(StockOutItem::class);
    }

    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }
}
