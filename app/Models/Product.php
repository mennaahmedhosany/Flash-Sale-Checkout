<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
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
}
