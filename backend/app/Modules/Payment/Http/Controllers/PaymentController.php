<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function initiate(Request $request, int $orderId): JsonResponse
    {
        $order = Order::where('buyer_id', $request->user()->id)->findOrFail($orderId);
        $method = $request->input('payment_method', 'qris');

        $payment = $this->paymentService->initiatePayment($order, $method);

        return $this->successResponse($payment, 'Payment initiated');
    }

    public function show(Request $request, string $idOrRef): JsonResponse
    {
        $query = Payment::with('order');
        if (is_numeric($idOrRef)) {
            $payment = $query->where('order_id', (int) $idOrRef)->orWhere('id', (int) $idOrRef)->first();
        } else {
            $payment = $query->where('provider_payment_id', $idOrRef)->first();
        }

        if (! $payment) {
            return $this->errorResponse('Data pembayaran tidak ditemukan', 404);
        }

        return $this->successResponse([
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'payment_reference' => $payment->provider_payment_id,
            'payment_method' => $payment->payment_method,
            'channel' => strtoupper($payment->payment_method),
            'amount' => (int) $payment->amount,
            'status' => $payment->status === 'paid' ? 'settled' : $payment->status,
            'qr_code' => "00020101021226590014ID.LINKAJA.WWW0118{$payment->provider_payment_id}5802ID5303360540{$payment->amount}",
            'va_number' => '8808'.str_pad((string) $payment->order_id, 10, '0', STR_PAD_LEFT),
            'expires_at' => $payment->expires_at?->toISOString(),
            'paid_at' => $payment->paid_at?->toISOString(),
        ], 'Payment status retrieved');
    }

    public function simulatePay(Request $request, string $idOrRef): JsonResponse
    {
        $payment = is_numeric($idOrRef)
            ? Payment::where('order_id', (int) $idOrRef)->orWhere('id', (int) $idOrRef)->first()
            : Payment::where('provider_payment_id', $idOrRef)->first();

        if (! $payment) {
            return $this->errorResponse('Data pembayaran tidak ditemukan', 404);
        }

        $sig = hash_hmac('sha256', "{$payment->provider_payment_id}:paid:{$payment->amount}", config('app.key'));
        $webhookRequest = Request::create('/api/v1/webhooks/payment/simulated', 'POST', [
            'provider_payment_id' => $payment->provider_payment_id,
            'provider_transaction_id' => 'TXN-'.strtoupper(Str::random(12)),
            'status' => 'paid',
            'amount' => $payment->amount,
            'signature' => $sig,
        ]);

        $this->paymentService->handleWebhook($webhookRequest);

        return $this->show($request, $idOrRef);
    }
}
