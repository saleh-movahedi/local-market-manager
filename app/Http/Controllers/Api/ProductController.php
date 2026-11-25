<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['storeProducts.store']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('store_id')) {
            $query->whereHas('storeProducts', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        $products = $query->paginate($request->get('per_page', 20));
        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['storeProducts.store']);
        return response()->json($product);
    }

    public function showInStore(Request $request, $storeId, Product $product): JsonResponse
    {
        $storeProduct = StoreProduct::where('store_id', $storeId)
            ->where('product_id', $product->id)
            ->with('product')
            ->firstOrFail();

        return response()->json($storeProduct);
    }
}
