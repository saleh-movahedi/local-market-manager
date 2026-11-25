<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['merchant', 'store', 'items.storeProduct.product']);

        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(20);
        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['merchant', 'store', 'items.storeProduct.product']);
        return response()->json($order);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'store_id' => 'required|exists:stores,id',
            'items' => 'required|array|min:1',
            'items.*.store_product_id' => 'required|exists:store_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'nullable|array',
        ]);

        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $subtotal = 0;

            $order = Order::create([
                'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                'merchant_id' => $request->merchant_id,
                'store_id' => $request->store_id,
                'status' => 'pending',
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'total_amount' => 0,
                'shipping_address' => $request->shipping_address ?? [],
            ]);

            foreach ($request->items as $item) {
                $storeProduct = \App\Models\StoreProduct::findOrFail($item['store_product_id']);

                $unitOkPrice = $storeProduct->ok_price;
                $discount = $storeProduct->discount_percent;
                $lineTotal = round($unitOkPrice * $item['quantity'], 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'store_product_id' => $item['store_product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $storeProduct->price,
                    'unit_ok_price' => $unitOkPrice,
                    'discount_percent' => $discount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
            }

            $taxAmount = round($subtotal * 0.09, 2);
            $totalAmount = round($subtotal + $taxAmount, 2);

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            return $order;
        });

        return response()->json($order->load(['items.storeProduct.product', 'merchant', 'store']), 201);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'shipping_address' => 'sometimes|array',
        ]);

        $order->update($request->only(['shipping_address']));
        return response()->json($order->load(['items.storeProduct.product', 'merchant', 'store']));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,packed,shipped,delivered,cancelled,returned',
        ]);

        $order->update([
            'status' => $request->status,
            'confirmed_at' => $request->status === 'confirmed' ? now() : $order->confirmed_at,
            'delivered_at' => $request->status === 'delivered' ? now() : $order->delivered_at,
        ]);

        return response()->json($order->load(['merchant', 'store', 'items.storeProduct.product']));
    }

    public function destroy(Order $order): JsonResponse
    {
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            return response()->json(['message' => 'Only pending or cancelled orders can be deleted'], 422);
        }

        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }
}
