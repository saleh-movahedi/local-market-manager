<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreProduct;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCategoryProducts implements ShouldQueue
{
    use Queueable;

    public int $storeId;
    public int $categoryId;
    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(int $storeId, int $categoryId)
    {
        $this->storeId = $storeId;
        $this->categoryId = $categoryId;
    }

    public function handle(): void
    {
        $store = Store::findOrFail($this->storeId);
        $category = Category::findOrFail($this->categoryId);

        if (!$store->is_active) {
            Log::warning("Store {$store->code} is not active, skipping sync");
            return;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Accept' => 'application/json',
            'Accept-Language' => 'fa-IR,fa;q=0.9,en;q=0.8',
        ])->timeout(30)->get("https://apigateway.okala.com/api/unicorn/v2/products/store/{$store->code}", [
            'pC_Id' => $category->id,
            'slug' => $category->slug,
        ]);

        if (!$response->successful()) {
            Log::error("Failed to fetch products for store {$store->code} category {$category->name}: {$response->status()}");
            return;
        }

        $products = $response->json('data') ?? [];
        $synced = 0;
        $errors = 0;

        foreach ($products as $p) {
            try {
                DB::transaction(function () use ($p, $store, &$synced) {
                    // Master product
                    $product = Product::updateOrCreate(
                        ['id' => $p['id']],
                        [
                            'name' => $p['name'],
                            'slug' => $p['slug'] ?? null,
                            'description' => null,
                            'image_url' => $p['imageUrl'] ?? null,
                            'master_product_id' => $p['masterProductId'] ?? null,
                            'doc_rank_t' => $p['docRankT'] ?? null,
                            'is_bundle' => $p['isBundle'] ?? false,
                            'attributes' => $p['attributes'] ?? null,
                        ]
                    );

                    // Store product (financial data)
                    StoreProduct::updateOrCreate(
                        ['store_id' => $store->id, 'product_id' => $product->id],
                        [
                            'price' => $p['price'] ?? 0,
                            'ok_price' => $p['okPrice'] ?? $p['price'] ?? 0,
                            'discount_percent' => $p['discountPercent'] ?? 0,
                            'quantity' => $p['quantity'] ?? 0,
                            'supply_status' => match($p['supplyStatus'] ?? 1) {
                                1 => 'available', 2 => 'unavailable', default => 'low'
                            },
                            'max_order_limit' => $p['maxOrderLimit'] ?? 5,
                            'cost_price' => null,
                            'vat_rate' => 9,
                            'last_synced_at' => now(),
                        ]
                    );
                    $synced++;
                });
            } catch (\Exception $e) {
                $errors++;
                Log::error("Failed to sync product {$p['id']}: {$e->getMessage()}");
            }
        }

        $category->update(['last_synced_at' => now()]);

        Log::info("Synced {$synced} products for store {$store->code} category {$category->name}", [
            'store_id' => $store->id,
            'category_id' => $category->id,
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }
}