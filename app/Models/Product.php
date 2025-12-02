<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_available',
        'stock_reserved',
        'version',
    ];

    public function holds()
    {
        return $this->hasMany(Hold::class);
    }
    public function orders()
    {
        return $this->hasManyThrough(Order::class, Hold::class);
    }
}
