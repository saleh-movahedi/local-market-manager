<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    public function index(): JsonResponse
    {
        $merchants = Merchant::withCount(['products', 'orders'])->get();
        return response()->json($merchants);
    }

    public function show(Merchant $merchant): JsonResponse
    {
        $merchant->load(['products.storeProduct.product']);
        return response()->json($merchant);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'code' => 'required|string|unique:merchants,code',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $merchant = Merchant::create($request->only([
            'name', 'address', 'phone', 'code', 'settings', 'is_active'
        ]));

        return response()->json($merchant, 201);
    }

    public function update(Request $request, Merchant $merchant): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'phone' => 'nullable|string|max:20',
            'code' => 'sometimes|string|unique:merchants,code,' . $merchant->id,
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $merchant->update($request->only([
            'name', 'address', 'phone', 'code', 'settings', 'is_active'
        ]));

        return response()->json($merchant);
    }

    public function destroy(Merchant $merchant): JsonResponse
    {
        $merchant->delete();
        return response()->json(['message' => 'Merchant deleted']);
    }

    public function orders(Merchant $merchant): JsonResponse
    {
        $orders = $merchant->orders()->with('store')->latest()->paginate(20);
        return response()->json($orders);
    }

    public function ledger(Merchant $merchant): JsonResponse
    {
        $ledger = $merchant->ledger()->latest('occurred_at')->paginate(50);
        return response()->json($ledger);
    }

    public function settlements(Merchant $merchant): JsonResponse
    {
        $settlements = $merchant->settlements()->latest('period_start')->paginate(20);
        return response()->json($settlements);
    }

    public function balance(Merchant $merchant): JsonResponse
    {
        return response()->json([
            'merchant_id' => $merchant->id,
            'balance' => $merchant->currentBalance(),
        ]);
    }
}
