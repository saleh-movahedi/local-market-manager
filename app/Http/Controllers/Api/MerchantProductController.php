<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantProduct;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MerchantProductController extends Controller
{
    public function index(Request $request, $merchantId): JsonResponse
    {
        $query = MerchantProduct::where('merchant_id', $merchantId)
            ->with(['storeProduct.product', 'storeProduct.store']);

        if ($request->has('store_id')) {
            $query->whereHas('storeProduct', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        $products = $query->paginate(20);
        return response()->json($products);
    }

    public function show($merchantId, MerchantProduct $product): JsonResponse
    {
        $product->load(['storeProduct.product', 'storeProduct.store', 'merchant']);
        return response()->json($product);
    }

    public function store(Request $request, $merchantId): JsonResponse
    {
        $request->validate([
            'store_product_id' => 'required|exists:store_products,id',
            'quantity' => 'required|integer|min:0',
            'custom_price' => 'nullable|numeric|min:0',
            'custom_ok_price' => 'nullable|numeric|min:0',
            'custom_discount' => 'nullable|integer|min:0|max:100',
            'margin_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $product = MerchantProduct::create([
            'merchant_id' => $merchantId,
            'store_product_id' => $request->store_product_id,
            'quantity' => $request->quantity,
            'custom_price' => $request->custom_price,
            'custom_ok_price' => $request->custom_ok_price,
            'custom_discount' => $request->custom_discount,
            'margin_percent' => $request->margin_percent,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, $merchantId, MerchantProduct $product): JsonResponse
    {
        $request->validate([
            'quantity' => 'sometimes|integer|min:0',
            'custom_price' => 'nullable|numeric|min:0',
            'custom_ok_price' => 'nullable|numeric|min:0',
            'custom_discount' => 'nullable|integer|min:0|max:100',
            'margin_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $product->update($request->only([
            'quantity', 'custom_price', 'custom_ok_price', 'custom_discount', 'margin_percent'
        ]));

        return response()->json($product);
    }

    public function destroy($merchantId, MerchantProduct $product): JsonResponse
    {
        $product->delete();
        return response()->json(['message' => 'Merchant product deleted']);
    }
}
