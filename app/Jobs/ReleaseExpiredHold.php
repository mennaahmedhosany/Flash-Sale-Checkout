<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredHold implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $holdId;



    /**
     * Create a new job instance.
     */
    public function __construct(int $holdId)
    {
        $this->holdId = $holdId;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $hold = Hold::where('id', $this->holdId)
                ->lockForUpdate()
                ->first();

            if (!$hold || $hold->released_at || $hold->payment_intent_id || $hold->expires_at->isFuture()) {
                return;
            }

            $product = Product::where('id', $hold->product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->stock_reserved -= $hold->quantity;
                $product->version += 1;
                $product->save();
            }

            $hold->update(['released_at' => now()]);
        });
    }
}
