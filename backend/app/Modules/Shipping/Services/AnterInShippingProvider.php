<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Order\Models\SellerOrder;
use App\Modules\Shipping\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Models\ShippingService;
use Illuminate\Support\Str;

class AnterInShippingProvider implements ShippingProviderInterface
{
    public function calculateRate(string $originCity, string $destinationCity, int $weightGram, ShippingService $service): array
    {
        // AnterIn calculation formula: base IDR 10,000 + IDR 5,000 per extra kg
        $kg = max(1, (int) ceil($weightGram / 1000));
        $baseRate = match ($service->service_code) {
            'sameday' => 25000,
            'instant' => 35000,
            default => 10000,
        };

        $totalCost = $baseRate + (($kg - 1) * 5000);

        return [
            'provider_code' => 'anterin',
            'provider_name' => 'AnterIn Express',
            'service_code' => $service->service_code,
            'service_name' => $service->service_name,
            'cost_amount' => $totalCost,
            'estimated_days' => "{$service->estimated_min_days}-{$service->estimated_max_days} hari",
        ];
    }

    public function createShipment(SellerOrder $sellerOrder, ShippingService $service): array
    {
        $trackingNumber = 'ANT-'.date('Ymd').'-'.strtoupper(Str::random(8));

        return [
            'tracking_number' => $trackingNumber,
            'provider_cost' => $sellerOrder->shipping_amount,
        ];
    }
}
