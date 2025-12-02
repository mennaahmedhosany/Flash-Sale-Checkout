<?php

namespace App\traits;

use App\Enums\OrderStatus;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Log;



trait HandlesPayment
{
    /**
     * Process a successful payment for an order (moving stock from reserved to consumed).
     *
     * @param \App\Models\Order $order
     * @param \App\Models\Product $product
     * @param string $idempotencyKey
     */
    protected function handlePaymentSuccess(Order $order, Product $product, string $idempotencyKey): void
    {
        if ($product->stock_reserved >= $order->qty) {
            $product->decrement('stock_reserved', $order->qty);
            $product->decrement('stock_available', $order->qty);
            $product->save();
        } else {
            Log::warning('Stock reserved deficit on payment success.', ['order_id' => $order->id]);
        }

        $order->update([
            'status' => OrderStatus::paid->value,
            'payment_idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Process a failed payment for an order (releasing stock back to available).
     *
     * @param \App\Models\Order $order
     * @param \App\Models\Product $product
     * @param \App\Models\Hold|null $hold
     * @param string $idempotencyKey
     */

    protected function handlePaymentFailure(Order $order, Product $product, ?Hold $hold, string $idempotencyKey): void
    {
        if ($product->stock_reserved >= $order->qty) {
            $product->decrement('stock_reserved', $order->qty);
            $product->save();
        } else {
            Log::info('Attempted to release stock, but reserved quantity was insufficient.', ['order_id' => $order->id]);
        }

        if ($hold) {
            $hold->update(['is_redeemed' => false]);
        }

        $order->update([
            'status' => OrderStatus::cancelled->value,
            'payment_idempotency_key' => $idempotencyKey,
        ]);
    }
}
