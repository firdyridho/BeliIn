<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Models\UserAddress;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\SellerOrder;
use App\Modules\Payment\Models\FinancialLedgerEntry;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(): array
    {
        $buyer = User::factory()->create();
        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create(['seller_id' => $seller->id, 'name' => 'Store Pay', 'slug' => 'store-pay']);
        $category = Category::create(['name' => 'Kategori', 'slug' => 'kategori']);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk Pay',
            'slug' => 'produk-pay-'.uniqid(),
            'weight_gram' => 200,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-PAY-1',
            'variant_name' => 'Default',
            'price_amount' => 100000,
        ]);

        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        $orderNumber = 'ORD-TEST-'.uniqid();

        // Reserve stock
        $reservation = app(InventoryService::class)->reserve($variant->id, 2, $orderNumber);

        $order = Order::create([
            'order_number' => $orderNumber,
            'buyer_id' => $buyer->id,
            'shipping_address_snapshot_json' => ['city' => 'Jakarta'],
            'subtotal_amount' => 200000,
            'shipping_amount' => 15000,
            'service_fee_amount' => 1000,
            'total_amount' => 216000,
            'order_status' => 'pending_payment',
        ]);

        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'seller_order_number' => 'SO-TEST-'.uniqid(),
            'subtotal_amount' => 200000,
            'shipping_amount' => 15000,
            'seller_status' => 'pending',
        ]);

        $orderItem = OrderItem::create([
            'seller_order_id' => $sellerOrder->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->variant_name,
            'sku_snapshot' => $variant->sku,
            'unit_price_amount' => 100000,
            'quantity' => 2,
            'line_total_amount' => 200000,
        ]);

        return [$order, $sellerOrder, $variant, $reservation];
    }

    public function test_can_initiate_payment_for_order(): void
    {
        [$order] = $this->createPendingOrder();

        $response = $this->actingAs($order->buyer, 'sanctum')->postJson("/api/v1/payments/{$order->id}/initiate", [
            'payment_method' => 'qris',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment initiated',
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount', 216000);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending',
            'amount' => 216000,
        ]);
    }

    public function test_payment_webhook_success_advances_order_and_commits_stock(): void
    {
        [$order, $sellerOrder, $variant, $reservation] = $this->createPendingOrder();

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'simulated',
            'provider_payment_id' => 'PAY-SIM-12345',
            'payment_method' => 'qris',
            'amount' => 216000,
            'status' => 'pending',
        ]);

        $webhookPayload = [
            'provider_payment_id' => 'PAY-SIM-12345',
            'provider_transaction_id' => 'TXN-999888',
            'status' => 'paid',
            'amount' => 216000,
            'signature' => hash_hmac('sha256', 'PAY-SIM-12345:paid:216000', config('app.key')),
        ];

        $response = $this->postJson('/api/v1/webhooks/payment/simulated', $webhookPayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);

        // Assert payment status is paid
        $this->assertEquals('paid', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);

        // Assert order status is processing
        $this->assertEquals('processing', $order->fresh()->order_status);

        // Assert seller order status is accepted
        $this->assertEquals('accepted', $sellerOrder->fresh()->seller_status);

        // Assert inventory was committed to sold
        $inventory = $variant->fresh()->inventory;
        $this->assertEquals(8, $inventory->available_quantity);
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals(2, $inventory->sold_quantity);

        // Assert financial ledger record created
        $this->assertDatabaseHas('financial_ledger_entries', [
            'order_id' => $order->id,
            'entry_type' => 'payment_received',
            'amount' => 216000,
            'direction' => 'credit',
        ]);
    }

    public function test_payment_webhook_is_idempotent(): void
    {
        [$order] = $this->createPendingOrder();

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'simulated',
            'provider_payment_id' => 'PAY-SIM-IDEMP',
            'payment_method' => 'qris',
            'amount' => 216000,
            'status' => 'pending',
        ]);

        $webhookPayload = [
            'provider_payment_id' => 'PAY-SIM-IDEMP',
            'provider_transaction_id' => 'TXN-SAME-ID',
            'status' => 'paid',
            'amount' => 216000,
            'signature' => hash_hmac('sha256', 'PAY-SIM-IDEMP:paid:216000', config('app.key')),
        ];

        // Send 1st webhook
        $this->postJson('/api/v1/webhooks/payment/simulated', $webhookPayload)->assertStatus(200);

        // Send duplicate webhook
        $dupResponse = $this->postJson('/api/v1/webhooks/payment/simulated', $webhookPayload);
        $dupResponse->assertStatus(200)
            ->assertJsonPath('data.duplicate', true);

        // Verify only 1 ledger entry exists
        $ledgerCount = FinancialLedgerEntry::where('order_id', $order->id)->count();
        $this->assertEquals(1, $ledgerCount);
    }
}
