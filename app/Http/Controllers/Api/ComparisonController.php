<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ComparisonController extends Controller
{
    public function compare(Request $request): JsonResponse
    {
        $store1Id = $request->get('store1', 51628);
        $store2Id = $request->get('store2', 6997);

        $store1 = Store::where('code', $store1Id)->firstOrFail();
        $store2 = Store::where('code', $store2Id)->firstOrFail();

        $products = StoreProduct::select(
                'p.id', 'p.name', 'p.image_url',
                'sp1.price as price1', 'sp1.ok_price as ok_price1', 'sp1.discount_percent as discount1',
                'sp1.quantity as qty1', 'sp1.supply_status as status1',
                'sp2.price as price2', 'sp2.ok_price as ok_price2', 'sp2.discount_percent as discount2',
                'sp2.quantity as qty2', 'sp2.supply_status as status2'
            )
            ->join('products as p', 'p.id', '=', 'store_products.product_id')
            ->leftJoin('store_products as sp1', function($q) use ($store1) {
                $q->on('sp1.product_id', '=', 'p.id')
                  ->where('sp1.store_id', $store1->id);
            })
            ->leftJoin('store_products as sp2', function($q) use ($store2) {
                $q->on('sp2.product_id', '=', 'p.id')
                  ->where('sp2.store_id', $store2->id);
            })
            ->where(function($q) {
                $q->whereNotNull('sp1.id')->orWhereNotNull('sp2.id');
            })
            ->get();

        $onlyInStore1 = $onlyInStore2 = $priceDiff = $statusDiff = [];

        foreach ($products as $p) {
            $has1 = $p->price1 !== null;
            $has2 = $p->price2 !== null;

            if ($has1 && !$has2) {
                $onlyInStore1[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'image_url' => $p->image_url,
                    'price' => $p->ok_price1,
                    'discount' => $p->discount1,
                ];
            } elseif ($has2 && !$has1) {
                $onlyInStore2[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'image_url' => $p->image_url,
                    'price' => $p->ok_price2,
                    'discount' => $p->discount2,
                ];
            } elseif ($has1 && $has2) {
                if ($p->ok_price1 != $p->ok_price2) {
                    $priceDiff[] = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'image_url' => $p->image_url,
                        'store1' => [
                            'price' => $p->ok_price1,
                            'discount' => $p->discount1,
                            'quantity' => $p->qty1,
                        ],
                        'store2' => [
                            'price' => $p->ok_price2,
                            'discount' => $p->discount2,
                            'quantity' => $p->qty2,
                        ],
                        'diff' => abs($p->ok_price1 - $p->ok_price2),
                        'diff_percent' => round(abs($p->ok_price1 - $p->ok_price2) / max($p->ok_price1, $p->ok_price2) * 100, 2),
                        'cheaper_in' => $p->ok_price1 < $p->ok_price2 ? $store1->code : $store2->code,
                    ];
                }
                if ($p->status1 != $p->status2) {
                    $statusDiff[] = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'store1' => ['status' => $p->status1, 'qty' => $p->qty1],
                        'store2' => ['status' => $p->status2, 'qty' => $p->qty2],
                    ];
                }
            }
        }

        return response()->json([
            'store1' => ['id' => $store1->id, 'code' => $store1->code, 'name' => $store1->name],
            'store2' => ['id' => $store2->id, 'code' => $store2->code, 'name' => $store2->name],
            'summary' => [
                'only_in_store1' => count($onlyInStore1),
                'only_in_store2' => count($onlyInStore2),
                'price_differences' => count($priceDiff),
                'status_differences' => count($statusDiff),
                'total_compared' => count($priceDiff) + count($onlyInStore1) + count($onlyInStore2),
            ],
            'only_in_store1' => $onlyInStore1,
            'only_in_store2' => $onlyInStore2,
            'price_differences' => $priceDiff,
            'status_differences' => $statusDiff,
        ]);
    }
}