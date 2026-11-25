<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Merchant;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Settlement::with('merchant');

        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $settlements = $query->latest('period_start')->paginate(20);
        return response()->json($settlements);
    }

    public function show(Settlement $settlement): JsonResponse
    {
        $settlement->load(['merchant', 'ledgerEntries']);
        return response()->json($settlement);
    }

    public function confirm(Settlement $settlement): JsonResponse
    {
        if ($settlement->status !== 'draft') {
            return response()->json(['message' => 'Only draft settlements can be confirmed'], 422);
        }

        $settlement->update(['status' => 'confirmed']);

        return response()->json($settlement);
    }

    public function markAsPaid(Request $request, Settlement $settlement): JsonResponse
    {
        if ($settlement->status !== 'confirmed') {
            return response()->json(['message' => 'Only confirmed settlements can be marked as paid'], 422);
        }

        $request->validate([
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $settlement->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $request->payment_reference,
        ]);

        return response()->json($settlement);
    }
}
