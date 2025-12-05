<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;


class StockIn extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'warehouse_id',
        'date_in',
        'reference',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date_in' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(StockInItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function units()
    {
        return $this->hasManyThrough(
            \App\Models\ProductUnit::class,
            \App\Models\StockInItem::class,
            'stock_in_id',     
            'stock_in_item_id' 
        );
    }

}
