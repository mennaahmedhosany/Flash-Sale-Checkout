<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show($id)
    {

        return Cache::remember("product:$id", 2, function () use ($id) {
            $product = Product::findOrFail($id);
            return new ProductResource($product);
        });
    }
}
