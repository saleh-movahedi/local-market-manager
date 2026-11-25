<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\Category;
use App\Jobs\SyncCategoryProducts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncAllStoresProducts implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 3600; // 1 hour max

    public function handle(): void
    {
        $stores = Store::active()->get();

        foreach ($stores as $store) {
            $categories = Category::where('level', 2)->get(); // زیرمجموعه‌ها

            foreach ($categories as $category) {
                SyncCategoryProducts::dispatch($store->id, $category->id)
                    ->onQueue('sync')
                    ->delay(now()->addSeconds(rand(1, 30))); // Stagger requests
            }
        }

        Log::info("Dispatched sync jobs for all stores", [
            'stores_count' => $stores->count(),
            'categories_count' => Category::where('level', 2)->count(),
        ]);
    }
}