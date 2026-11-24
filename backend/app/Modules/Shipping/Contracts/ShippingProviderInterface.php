<?php

namespace App\Modules\Shipping\Contracts;

use App\Modules\Order\Models\SellerOrder;
use App\Modules\Shipping\Models\ShippingService;

interface ShippingProviderInterface
{
    /**
     * Calculate rate for specified origin, destination, and package weight.
     */
    public function calculateRate(string $originCity, string $destinationCity, int $weightGram, ShippingService $service): array;

    /**
     * Create shipment and generate tracking number.
     */
    public function createShipment(SellerOrder $sellerOrder, ShippingService $service): array;
}
