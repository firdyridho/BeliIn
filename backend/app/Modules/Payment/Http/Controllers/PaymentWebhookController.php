<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function handle(Request $request, string $provider): JsonResponse
    {
        $result = $this->paymentService->handleWebhook($request);

        return $this->successResponse($result, 'Webhook processed successfully');
    }
}
