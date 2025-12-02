<?php

namespace App\Http\Controllers;

use App\Http\Requests\HoldRequest;
use App\Http\Resources\HoldResource;
use App\Jobs\ReleaseExpiredHold;
use Illuminate\Validation\ValidationException;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class HoldController extends Controller
{

    public function store(HoldRequest $request)
    {
        $productId = $request->product_id;
        $qty = $request->qty;


        $hold = DB::transaction(function () use ($productId, $qty) {

            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            $available = $product->stock_available - $product->stock_reserved;
            if ($qty > $available) {
                throw ValidationException::withMessages([
                    'stock' => [
                        "Sorry, we only have $available unit(s) of '{$product->name}' available."
                    ],
                ]);
            }

            $hold = Hold::create([
                'product_id' => $productId,
                'quantity'   => $qty,
                'expires_at' => now()->addMinutes(2),
            ]);
            $product->increment('stock_reserved', $qty);

            return $hold;
        });
        ReleaseExpiredHold::dispatch($hold->id)
            ->delay($hold->expires_at);
        return new HoldResource($hold);
    }
}
