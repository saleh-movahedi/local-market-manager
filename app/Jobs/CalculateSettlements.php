<?php

namespace App\Jobs;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\LedgerEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculateSettlements implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 300;

    public function handle(): void
    {
        $endDate = Carbon::yesterday();
        $startDate = Carbon::yesterday()->subDays(6); // 7-day period

        $merchants = Merchant::where('is_active', true)->get();

        foreach ($merchants as $merchant) {
            try {
                DB::transaction(function () use ($merchant, $startDate, $endDate) {
                    // Check if settlement already exists
                    $existing = Settlement::where('merchant_id', $merchant->id)
                        ->where('period_start', $startDate->toDateString())
                        ->where('period_end', $endDate->toDateString())
                        ->first();

                    if ($existing && $existing->status !== 'draft') {
                        Log::info("Settlement already processed for merchant {$merchant->code}", [
                            'merchant_id' => $merchant->id,
                            'period_start' => $startDate,
                        ]);
                        return;
                    }

                    // Get orders in period
                    $orders = Order::where('merchant_id', $merchant->id)
                        ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                        ->where('status', '!=', 'cancelled')
                        ->get();

                    $totalSales = $orders->sum('total_amount');
                    $totalRefunds = Order::where('merchant_id', $merchant->id)
                        ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                        ->where('status', 'returned')
                        ->sum('total_amount');

                    // Commission calculation (example: 10% of sales)
                    $commissionRate = $merchant->settings['commission_rate'] ?? 0.10;
                    $totalCommission = round($totalSales * $commissionRate, 2);

                    // Net payable = sales - commission - refunds
                    $netPayable = round($totalSales - $totalCommission - $totalRefunds, 2);

                    $settlement = Settlement::updateOrCreate(
                        [
                            'merchant_id' => $merchant->id,
                            'period_start' => $startDate->toDateString(),
                            'period_end' => $endDate->toDateString(),
                        ],
                        [
                            'total_sales' => $totalSales,
                            'total_commission' => $totalCommission,
                            'total_refunds' => $totalRefunds,
                            'net_payable' => $netPayable,
                            'status' => 'draft',
                        ]
                    );

                    // Create ledger entry for commission
                    if ($totalCommission > 0) {
                        $lastBalance = LedgerEntry::where('merchant_id', $merchant->id)
                            ->latest('occurred_at')
                            ->first()?->balance_after ?? 0;

                        LedgerEntry::create([
                            'merchant_id' => $merchant->id,
                            'order_id' => null,
                            'type' => 'commission',
                            'amount' => -$totalCommission, // negative = credit to platform
                            'balance_after' => $lastBalance - $totalCommission,
                            'description' => "کمیسیون هفته {$startDate->format('Y-m-d')} تا {$endDate->format('Y-m-d')}",
                            'meta' => ['settlement_id' => $settlement->id],
                            'occurred_at' => $endDate->endOfDay(),
                        ]);
                    }

                    // Create ledger entry for net payable
                    if ($netPayable > 0) {
                        $lastBalance = LedgerEntry::where('merchant_id', $merchant->id)
                            ->latest('occurred_at')
                            ->first()?->balance_after ?? 0;

                        LedgerEntry::create([
                            'merchant_id' => $merchant->id,
                            'order_id' => null,
                            'type' => 'payout',
                            'amount' => $netPayable, // positive = debit to merchant
                            'balance_after' => $lastBalance + $netPayable,
                            'description' => "تسویه هفته {$startDate->format('Y-m-d')} تا {$endDate->format('Y-m-d')}",
                            'meta' => ['settlement_id' => $settlement->id],
                            'occurred_at' => $endDate->endOfDay(),
                        ]);
                    }

                    Log::info("Calculated settlement for merchant {$merchant->code}", [
                        'merchant_id' => $merchant->id,
                        'settlement_id' => $settlement->id,
                        'total_sales' => $totalSales,
                        'total_commission' => $totalCommission,
                        'net_payable' => $netPayable,
                    ]);
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to calculate settlement for merchant {$merchant->code}: {$e->getMessage()}");
            }
        }
    }
}