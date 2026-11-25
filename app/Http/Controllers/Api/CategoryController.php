<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Jobs\SyncCategoryProducts;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        $categories = $query->get();
        return response()->json($categories);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category);
    }

    public function products(Category $category): JsonResponse
    {
        $products = $category->products()->paginate(20);
        return response()->json($products);
    }

    public function syncProducts(Request $request, Category $category): JsonResponse
    {
        $stores = \App\Models\Store::active()->get();

        foreach ($stores as $store) {
            SyncCategoryProducts::dispatch($store->id, $category->id)
                ->onQueue('sync')
                ->delay(now()->addSeconds(rand(1, 10)));
        }

        return response()->json([
            'message' => 'Sync jobs dispatched',
            'category_id' => $category->id,
            'stores_count' => $stores->count()
        ]);
    }

    public function syncAll(): JsonResponse
    {
        $stores = \App\Models\Store::active()->get();
        $categories = Category::where('level', 2)->get();

        foreach ($stores as $store) {
            foreach ($categories as $category) {
                SyncCategoryProducts::dispatch($store->id, $category->id)
                    ->onQueue('sync')
                    ->delay(now()->addSeconds(rand(1, 30)));
            }
        }

        return response()->json([
            'message' => 'Full sync dispatched for all stores and categories'
        ]);
    }
}
