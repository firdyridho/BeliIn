<?php

namespace App\Modules\Payment\Services;

use App\Modules\Inventory\Models\InventoryReservation;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Models\FinancialLedgerEntry;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayInterface $gateway,
        protected InventoryService $inventoryService
    ) {}

    public function initiatePayment(Order $order, string $method = 'qris'): Payment
    {
        return DB::transaction(function () use ($order, $method) {
            $paymentData = $this->gateway->createPayment($order, $method);

            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'provider' => $paymentData['provider'],
                    'provider_payment_id' => $paymentData['provider_payment_id'],
                    'payment_method' => $method,
                    'amount' => $order->total_amount,
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'expires_at' => $paymentData['expires_at'],
                ]
            );

            return $payment;
        });
    }

    public function handleWebhook(Request $request): array
    {
        if (! $this->gateway->verifyWebhookSignature($request)) {
            throw new Exception('Invalid webhook signature', 403);
        }

        $providerPaymentId = $request->input('provider_payment_id');
        $providerTransactionId = $request->input('provider_transaction_id');
        $status = $request->input('status'); // paid, failed, expired
        $amount = (int) $request->input('amount');

        return DB::transaction(function () use ($providerPaymentId, $providerTransactionId, $status, $amount, $request) {
            // Idempotency check: if transaction already processed, return early
            $existingTxn = PaymentTransaction::where('provider_transaction_id', $providerTransactionId)->first();
            if ($existingTxn) {
                return ['duplicate' => true, 'message' => 'Transaction already processed'];
            }

            $payment = Payment::where('provider_payment_id', $providerPaymentId)->firstOrFail();
            $order = $payment->order;

            // Record transaction log
            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'provider_transaction_id' => $providerTransactionId,
                'event_type' => "payment.{$status}",
                'amount' => $amount,
                'status' => $status,
                'payload_json' => $request->all(),
                'processed_at' => now(),
            ]);

            if ($status === 'paid') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Transition Order status: pending_payment -> processing
                $order->update(['order_status' => 'processing']);

                // Transition Seller Orders: pending -> accepted
                $order->sellerOrders()->update(['seller_status' => 'accepted', 'accepted_at' => now()]);

                // Commit stock reservations to sold
                $reservations = InventoryReservation::where('checkout_reference', $order->order_number)
                    ->where('status', 'active')
                    ->get();

                foreach ($reservations as $reservation) {
                    $this->inventoryService->commitReservation($reservation);
                }

                // Append-only Ledger Entry: Credit Platform account
                FinancialLedgerEntry::create([
                    'order_id' => $order->id,
                    'account_owner_type' => 'platform',
                    'account_owner_id' => 1,
                    'entry_type' => 'payment_received',
                    'direction' => 'credit',
                    'amount' => $amount,
                    'currency' => 'IDR',
                    'idempotency_key' => "LEDGER-PAY-{$providerTransactionId}",
                ]);
            } elseif (in_array($status, ['failed', 'expired'])) {
                $payment->update(['status' => $status]);
                $order->update(['order_status' => 'cancelled', 'cancelled_at' => now()]);
                $order->sellerOrders()->update(['seller_status' => 'cancelled', 'cancelled_at' => now()]);

                // Release stock reservations back to available
                $reservations = InventoryReservation::where('checkout_reference', $order->order_number)
                    ->where('status', 'active')
                    ->get();

                foreach ($reservations as $reservation) {
                    $this->inventoryService->releaseReservation($reservation);
                }
            }

            return ['duplicate' => false, 'payment_status' => $status];
        });
    }
}
