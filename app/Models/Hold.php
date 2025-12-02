<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;


class Hold extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'quantity',
        'expires_at',
        'is_redeemed',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_redeemed' => 'boolean',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
