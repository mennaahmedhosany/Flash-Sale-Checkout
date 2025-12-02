<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\orderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Traits\HasHoldValidation;

class OrderController extends Controller
{


    public function store(orderRequest $request)
    {

        $holdId = $request->hold_id;
        $order = DB::transaction(function () use ($holdId) {
            $hold = Hold::where('id', $holdId)->lockForUpdate()->firstOrFail();

            $hold->validateUsable();

            $hold->is_redeemed = true;
            $hold->save();

            $product = $hold->product;

            $amountCents = (int) round($hold->quantity * ($product->price * 100));

            $order = Order::create([
                'product_id' => $product->id,
                'hold_id' => $hold->id,
                'payment_idempotency_key' => null,
                'status' =>  OrderStatus::pending_payment->value,
                'qty' => $hold->quantity,
                'amount_cents' => $amountCents,
            ]);

            return $order;
        });

        return new OrderResource($order);
    }
}
