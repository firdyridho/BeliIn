<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seller\Models\Store;
use App\Modules\Shipping\Services\ShippingManager;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingRateController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ShippingManager $shippingManager
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $originCity = $request->input('origin_city');
        if (! $originCity && $request->filled('store_id')) {
            $store = Store::find($request->input('store_id'));
            $originCity = $store?->city;
        }
        $originCity = $originCity ?: 'Jakarta Selatan';

        $destinationCity = $request->input('destination_city') ?: 'Jakarta Selatan';
        $weightGram = (int) ($request->input('weight_gram') ?: ($request->input('total_weight') ?: 500));

        $rates = $this->shippingManager->calculateAllRates(
            originCity: $originCity,
            destinationCity: $destinationCity,
            weightGram: max(100, $weightGram)
        );

        // Normalize rate objects for frontend
        $normalized = array_map(function ($r) {
            return [
                'provider_code' => $r['provider_code'] ?? 'anterin',
                'provider_name' => $r['provider_name'] ?? 'AnterIn Express',
                'service_code' => $r['service_code'] ?? 'reg',
                'service_name' => $r['service_name'] ?? 'AnterIn Regular',
                'rate' => (int) ($r['cost_amount'] ?? $r['rate'] ?? 10000),
                'cost_amount' => (int) ($r['cost_amount'] ?? $r['rate'] ?? 10000),
                'etd' => $r['estimated_days'] ?? '1-2 hari',
                'estimated_days' => $r['estimated_days'] ?? '1-2 hari',
            ];
        }, $rates);

        return $this->successResponse($normalized, 'Shipping rates calculated successfully');
    }
}
