<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('کالای اوکالا/هایپرمارکت ID');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->bigInteger('doc_rank_t')->nullable();
            $table->boolean('is_bundle')->default(false);
            $table->json('attributes')->nullable()->comment('مشخصات تکمیلی: برند، وزن، ابعاد، رنگ و...');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};