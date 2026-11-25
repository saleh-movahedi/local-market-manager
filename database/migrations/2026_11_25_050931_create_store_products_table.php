<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 2)->comment('قیمت اصلی');
            $table->decimal('ok_price', 15, 2)->comment('قیمت با تخفیف (قیمت نهایی)');
            $table->unsignedSmallInteger('discount_percent')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->enum('supply_status', ['available', 'unavailable', 'low'])->default('available');
            $table->unsignedInteger('max_order_limit')->default(5);
            $table->decimal('cost_price', 15, 2)->nullable()->comment('بهای تمام‌شده (برای محاسبه سود)');
            $table->decimal('vat_rate', 5, 2)->default(9)->comment('نرخ مالیات بر ارزش افزوده');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_products');
    }
};