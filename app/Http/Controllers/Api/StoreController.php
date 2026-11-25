<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Category;
use App\Jobs\SyncCategoryProducts;
use App\Jobs\SyncAllStoresProducts;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function index(): JsonResponse
    {
        $stores = Store::active()->withCount('products')->get();
        return response()->json($stores);
    }

    public function show(Store $store): JsonResponse
    {
        $store->load(['products.product']);
        return response()->json($store);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:stores,code',
            'url' => 'nullable|url|max:500',
            'meta' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $store = Store::create($request->only([
            'name', 'code', 'url', 'meta', 'is_active'
        ]));

        return response()->json($store, 201);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:stores,code,' . $store->id,
            'url' => 'nullable|url|max:500',
            'meta' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $store->update($request->only([
            'name', 'code', 'url', 'meta', 'is_active'
        ]));

        return response()->json($store);
    }

    public function destroy(Store $store): JsonResponse
    {
        $store->delete();
        return response()->json(['message' => 'Store deleted']);
    }

    public function syncProducts(Request $request, Store $store): JsonResponse
    {
        $categories = Category::where('level', 2)->get();

        foreach ($categories as $category) {
            SyncCategoryProducts::dispatch($store->id, $category->id)
                ->onQueue('sync')
                ->delay(now()->addSeconds(rand(1, 10)));
        }

        return response()->json([
            'message' => 'Sync jobs dispatched',
            'store_id' => $store->id,
            'categories_count' => $categories->count()
        ]);
    }

    public function syncAll(): JsonResponse
    {
        SyncAllStoresProducts::dispatch()->onQueue('sync');

        return response()->json([
            'message' => 'Full sync job dispatched'
        ]);
    }

    public function productsCount(Store $store): JsonResponse
    {
        return response()->json([
            'store_id' => $store->id,
            'count' => $store->products()->count()
        ]);
    }
}
