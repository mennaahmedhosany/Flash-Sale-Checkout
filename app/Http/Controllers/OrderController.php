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



    // public function handlePaymentWebhook(Request $request)
    // {
    //     $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');
    //     if (!$idempotencyKey) {
    //         return response()->json(['error' => 'Missing idempotency key'], 400);
    //     }

    //     $orderId = $request->input('data.order_id');
    //     $status  = $request->input('data.status');

    //     if (!$orderId || !$status) {
    //         return response()->json(['error' => 'Invalid payload'], 400);
    //     }


    //     try {
    //         DB::transaction(function () use ($orderId, $status, $idempotencyKey) {

    //             $order = Order::lockForUpdate()->find($orderId);
    //             if (!$order) {
    //                 Log::warning('Webhook received for non-existing order', ['order_id' => $orderId]);
    //                 return;
    //             }

    //             if ($order->payment_idempotency_key === $idempotencyKey) {
    //                 return;
    //             }

    //             if (in_array($order->status, ['paid', 'cancelled'])) {
    //                 Log::info('Webhook ignored: order already finalized', ['order_id' => $orderId, 'status' => $order->status]);
    //                 return;
    //             }

    //             if ($status === 'success') {
    //                 $order->update([
    //                     'status' => 'paid',
    //                     'payment_idempotency_key' => $idempotencyKey,
    //                 ]);
    //             } else {
    //                 $order->update([
    //                     'status' => 'cancelled',
    //                     'payment_idempotency_key' => $idempotencyKey,
    //                 ]);

    //                 $hold = $order->hold()->lockForUpdate()->first();
    //                 if ($hold && !$hold->is_redeemed) {
    //                     DB::table('products')
    //                         ->where('id', $order->product_id)
    //                         ->increment('stock_available', $order->qty);
    //                     DB::table('products')
    //                         ->where('id', $order->product_id)
    //                         ->decrement('stock_reserved', $order->qty);

    //                     $hold->update(['is_redeemed' => false]);
    //                 }
    //             }

    //             Cache::tags(['product:' . $order->product_id])->forget('product:' . $order->product_id);

    //             Log::info('Webhook processed', ['order_id' => $order->id, 'status' => $order->status]);
    //         });

    //         return response()->json(['success' => true], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'internal error'], 500);
    //     }
    // }
}
