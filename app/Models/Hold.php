<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\traits\HasHoldValidation;


class Hold extends Model
{
    use HasFactory, HasHoldValidation;



    protected $fillable = [
        'product_id',
        'quantity',
        'expires_at',
        'is_redeemed',
        'released_at',
        'payment_intent_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'released_at'  => 'datetime',
        'is_redeemed' => 'boolean',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
