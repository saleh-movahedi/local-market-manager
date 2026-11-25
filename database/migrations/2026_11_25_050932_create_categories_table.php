<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('دسته‌بندی اوکالا/هایپرمارکت ID');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedTinyInteger('level')->default(1)->comment('سطح دسته‌بندی: 1=اصلی، 2=زیرمجموعه');
            $table->json('parent_ids')->nullable()->comment('آی‌دی والدها: [1462, 3001]');
            $table->string('color')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};