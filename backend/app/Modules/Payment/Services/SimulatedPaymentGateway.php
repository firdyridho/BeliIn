<?php

namespace App\Modules\Payment\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SimulatedPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(Order $order, string $method): array
    {
        $providerPaymentId = 'PAY-SIM-'.strtoupper(Str::random(10));
        $expiresAt = now()->addMinutes(30);

        return [
            'provider' => 'simulated',
            'provider_payment_id' => $providerPaymentId,
            'payment_method' => $method,
            'payment_url' => "https://payment.beliin.test/checkout/{$providerPaymentId}",
            'qr_string' => "00020101021226590014ID.LINKAJA.WWW0118{$providerPaymentId}5802ID5303360540{$order->total_amount}",
            'expires_at' => $expiresAt,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->input('signature');
        $paymentId = $request->input('provider_payment_id');
        $status = $request->input('status');
        $amount = $request->input('amount');

        $computed = hash_hmac('sha256', "{$paymentId}:{$status}:{$amount}", config('app.key'));

        return hash_equals($computed, (string) $signature);
    }
}
