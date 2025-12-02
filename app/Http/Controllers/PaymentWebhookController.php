<?php

namespace App\Http\Controllers;


use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use App\Http\Requests\PaymentWebhookRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\traits\HandlesPayment;
use App\traits\ManagesOrderWebhookTransaction;


use Throwable;

class PaymentWebhookController extends Controller
{
    /**
     * Handles incoming payment webhooks for state transitions.
     * This function is designed to be Idempotent and Out-of-Order Safe.
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    use HandlesPayment, ManagesOrderWebhookTransaction;

    public function handlePaymentWebhook(PaymentWebhookRequest $request)
    {

        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');
        if (!$idempotencyKey) {
            return response()->json(['error' => 'Missing idempotency key'], 400);
        }
        $orderId = $request->input('data.order_id');
        $status = $request->input('data.status');

        try {
            DB::transaction(function () use ($orderId, $status, $idempotencyKey) {

                $order = Order::lockForUpdate()->find($orderId);

                if (!$this->canProcessWebhook($order, $idempotencyKey)) {
                    Log::warning("Webhook for non-existent order: {$orderId}");
                    return response()->json(['success' => true], 200);
                }

                $hold = $order->hold()->lockForUpdate()->first();
                $product = Product::lockForUpdate()->find($order->product_id);

                if (!$product) {
                    Log::error('Product not found for order', ['order_id' => $orderId, 'product_id' => $order->product_id]);
                    throw new \Exception("Product not found.");
                }

                if ($status === 'success') {

                    $this->handlePaymentSuccess($order, $product, $hold, $status, $idempotencyKey);
                } else {
                    $this->handlePaymentFailure($order, $product, $hold, $idempotencyKey);
                }

                Cache::tags(['product:' . $order->product_id])->forget('product:' . $order->product_id);

                Log::info('Webhook processed', ['order_id' => $order->id, 'final_status' => $order->status->value]);
            });

            return response()->json(['success' => true], 200);
        } catch (ModelNotFoundException $e) {


            Log::warning('Webhook lookup failed: ' . $e->getMessage(), ['order_id' => $orderId]);
            return response()->json(['error' => 'Order/Resource not found'], 404);
        } catch (Throwable $e) {

            Log::error('Webhook processing failed (Transactional Error)', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
