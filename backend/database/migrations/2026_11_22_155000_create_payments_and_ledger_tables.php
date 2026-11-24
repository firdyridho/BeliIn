<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 50)->default('simulated');
            $table->string('provider_payment_id', 100)->nullable()->index();
            $table->string('payment_method', 50)->default('qris');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('IDR');
            $table->string('status', 30)->default('pending'); // pending, paid, failed, expired, refunded
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('provider_transaction_id', 100)->unique();
            $table->string('event_type', 50);
            $table->unsignedBigInteger('amount');
            $table->string('status', 30);
            $table->json('payload_json')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('financial_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('seller_order_id')->nullable()->constrained('seller_orders')->nullOnDelete();
            $table->string('account_owner_type', 50)->default('platform'); // platform, seller, buyer
            $table->unsignedBigInteger('account_owner_id')->default(1);
            $table->string('entry_type', 50); // payment_received, commission_fee, seller_payout, refund
            $table->string('direction', 10); // credit, debit
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('IDR');
            $table->string('idempotency_key', 150)->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_ledger_entries');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
