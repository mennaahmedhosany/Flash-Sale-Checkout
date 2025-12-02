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


        $stockData = Product::select('stock_available', 'stock_reserved')
            ->findOrFail($id);

        $product = Cache::remember("product_details:$id", 300, function () use ($id) {
            return Product::select('id', 'name', 'description', 'price', 'created_at', 'updated_at')
                ->findOrFail($id);
        });
        $product->stock_available = $stockData->stock_available;
        $product->stock_reserved = $stockData->stock_reserved;
        return new ProductResource($product);
    }
}
