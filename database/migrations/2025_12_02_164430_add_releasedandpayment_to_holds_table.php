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
        Schema::table('holds', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()
                ->comment('Timestamp when the hold was released back to stock due to expiry.')
                ->after('expires_at');
            $table->string('payment_intent_id')->nullable()->unique()
                ->after('released_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holds', function (Blueprint $table) {
            $table->dropUnique(['payment_intent_id']);
            $table->dropColumn('payment_intent_id');
            $table->dropColumn('released_at');
        });
    }
};
