<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{

    use HasFactory;

    protected $fillable = [
        'product_id',
        'hold_id',
        'payment_idempotency_key',
        'status',
        'qty',
        'amount_cents',
    ];
    protected $casts = [
        'status' => OrderStatus::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function hold()
    {
        return $this->belongsTo(Hold::class, 'hold_id');
    }
}
