<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'sale', 'commission', 'refund', 'adjustment', 'payout', 'fee'
            ]);
            $table->decimal('amount', 15, 2)->comment('مثبت = بدهکار، منفی = بستانکار');
            $table->decimal('balance_after', 15, 2)->comment('مانده حساب بعد از تراکنش');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['merchant_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};