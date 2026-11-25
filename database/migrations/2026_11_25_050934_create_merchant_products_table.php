<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_product_id')->constrained()->cascadeOnDelete()->comment('لینک به store_products');
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('custom_price', 15, 2)->nullable();
            $table->decimal('custom_ok_price', 15, 2)->nullable();
            $table->unsignedSmallInteger('custom_discount')->nullable();
            $table->decimal('margin_percent', 5, 2)->nullable()->comment('حاشیه سود مرچنت');
            $table->timestamps();
            $table->unique(['merchant_id', 'store_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_products');
    }
};