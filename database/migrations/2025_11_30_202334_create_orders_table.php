<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->uuid('hold_id')->unique();
            $table->foreign('hold_id')->references('id')->on('holds')->cascadeOnDelete();
            $table->string('payment_idempotency_key')->nullable()->unique();
            $table->enum('status', ['pending_payment', 'paid', 'cancelled', 'failed'])->default('pending_payment');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('amount_cents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
