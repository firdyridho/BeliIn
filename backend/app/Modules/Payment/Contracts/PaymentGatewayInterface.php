<?php

namespace App\Modules\Payment\Contracts;

use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create payment session or QR/VA on payment provider.
     */
    public function createPayment(Order $order, string $method): array;

    /**
     * Verify authenticity of provider webhook.
     */
    public function verifyWebhookSignature(Request $request): bool;
}
