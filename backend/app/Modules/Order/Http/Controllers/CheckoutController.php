<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Http\Requests\CheckoutRequest;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Http\Resources\SellerOrderResource;
use App\Modules\Order\Services\CheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $order = $this->checkoutService->checkout(
            buyer: $request->user(),
            data: $request->validated()
        );

        $payment = app(\App\Modules\Payment\Services\PaymentService::class)->initiatePayment($order, 'qris');
        $order->setRelation('payment', $payment);

        return $this->successResponse([
            'order' => new OrderResource($order),
            'seller_orders' => SellerOrderResource::collection($order->sellerOrders),
            'payment' => [
                'id' => $payment->id,
                'payment_reference' => $payment->provider_payment_id,
                'channel' => $payment->payment_method,
                'amount' => (int) $payment->amount,
                'status' => $payment->status,
                'expires_at' => $payment->expires_at?->toISOString(),
            ],
        ], 'Checkout successful, order placed', 201);
    }
}
