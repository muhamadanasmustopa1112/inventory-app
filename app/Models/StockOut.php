<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;


class StockOut extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'warehouse_id',
        'buyer_id',
        'date_out',
        'reference',
        'total_price',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date_out' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function items()
    {
        return $this->hasMany(StockOutItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
