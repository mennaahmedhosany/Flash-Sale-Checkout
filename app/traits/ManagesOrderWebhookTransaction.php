<?php

namespace App\traits;

use App\Enums\OrderStatus;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Log;



trait ManagesOrderWebhookTransaction
{

    protected function canProcessWebhook(Order $order, string $idempotencyKey): bool
    {
        if (!$order) {
            Log::warning('Webhook received for non-existing order', ['order_id' => $order->id]);
            return false;
        }

        if ($order->payment_idempotency_key === $idempotencyKey) {

            Log::info('Webhook ignored: duplicate idempotency key', ['order_id' => $order->id, 'key' => $idempotencyKey]);
            return false;
        }

        if (in_array($order->status->value, [OrderStatus::paid->value, OrderStatus::cancelled->value])) {

            Log::info('Webhook ignored: order already finalized', [
                'order_id' => $order->id,
                'current_status' => $order->status->value
            ]);
            return false;
        }
        return true;
    }
}
