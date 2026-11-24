<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('provider_type', 30)->default('internal'); // internal (AnterIn), external (JNE/etc)
            $table->boolean('is_active')->default(true);
            $table->json('config_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers')->cascadeOnDelete();
            $table->string('service_code', 50);
            $table->string('service_name', 100);
            $table->unsignedInteger('estimated_min_days')->default(1);
            $table->unsignedInteger('estimated_max_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shipping_provider_id', 'service_code']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_order_id')->unique()->constrained('seller_orders')->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers');
            $table->foreignId('shipping_service_id')->constrained('shipping_services');
            $table->string('tracking_number', 100)->unique();
            $table->string('status', 30)->default('pending_pickup'); // pending_pickup, picked_up, in_transit, out_for_delivery, delivered, failed, returned
            $table->json('origin_snapshot_json');
            $table->json('destination_snapshot_json');
            $table->unsignedBigInteger('shipping_cost_amount');
            $table->unsignedBigInteger('buyer_shipping_amount');
            $table->unsignedBigInteger('provider_cost_amount')->default(0);
            $table->unsignedInteger('weight_gram')->default(100);
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('shipment_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('event_code', 50);
            $table->string('event_description');
            $table->string('location')->nullable();
            $table->timestamp('event_at');
            $table->string('source', 30)->default('provider');
            $table->timestamps();

            $table->index(['shipment_id', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_tracking_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_services');
        Schema::dropIfExists('shipping_providers');
    }
};
