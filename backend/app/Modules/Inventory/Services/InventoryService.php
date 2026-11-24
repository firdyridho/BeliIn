<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Atomically reserve stock for a variant with row locking.
     */
    public function reserve(
        int $variantId,
        int $quantity,
        string $checkoutRef,
        int $durationMinutes = 15
    ): InventoryReservation {
        return DB::transaction(function () use ($variantId, $quantity, $checkoutRef, $durationMinutes) {
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = Inventory::create([
                    'product_variant_id' => $variantId,
                    'available_quantity' => 0,
                    'reserved_quantity' => 0,
                    'sold_quantity' => 0,
                ]);
                $inventory = Inventory::where('id', $inventory->id)->lockForUpdate()->first();
            }

            if ($inventory->available_quantity < $quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock for variant ID {$variantId}. Requested: {$quantity}, Available: {$inventory->available_quantity}"
                );
            }

            $inventory->available_quantity -= $quantity;
            $inventory->reserved_quantity += $quantity;
            $inventory->save();

            $reservation = InventoryReservation::create([
                'inventory_id' => $inventory->id,
                'checkout_reference' => $checkoutRef,
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => now()->addMinutes($durationMinutes),
            ]);

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'movement_type' => 'reserved',
                'quantity_delta' => -$quantity,
                'reference_type' => 'checkout',
                'reference_id' => $checkoutRef,
                'note' => "Reserved {$quantity} items for checkout {$checkoutRef}",
            ]);

            return $reservation;
        });
    }

    /**
     * Commit active reservation into sold stock after successful payment.
     */
    public function commitReservation(InventoryReservation $reservation, ?int $orderItemId = null): void
    {
        DB::transaction(function () use ($reservation, $orderItemId) {
            if ($reservation->status !== 'active') {
                return;
            }

            $inventory = Inventory::where('id', $reservation->inventory_id)
                ->lockForUpdate()
                ->firstOrFail();

            $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $reservation->quantity);
            $inventory->sold_quantity += $reservation->quantity;
            $inventory->save();

            $reservation->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'order_item_id' => $orderItemId,
                'movement_type' => 'sold',
                'quantity_delta' => -$reservation->quantity,
                'reference_type' => 'order',
                'reference_id' => $reservation->checkout_reference,
                'note' => "Committed reservation to sold quantity for checkout {$reservation->checkout_reference}",
            ]);
        });
    }

    /**
     * Release active reservation back to available stock.
     */
    public function releaseReservation(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            if ($reservation->status !== 'active') {
                return;
            }

            $inventory = Inventory::where('id', $reservation->inventory_id)
                ->lockForUpdate()
                ->firstOrFail();

            $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $reservation->quantity);
            $inventory->available_quantity += $reservation->quantity;
            $inventory->save();

            $reservation->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'movement_type' => 'released',
                'quantity_delta' => $reservation->quantity,
                'reference_type' => 'checkout',
                'reference_id' => $reservation->checkout_reference,
                'note' => "Released reservation back to available stock for checkout {$reservation->checkout_reference}",
            ]);
        });
    }

    /**
     * Release all expired reservations.
     */
    public function releaseExpiredReservations(): int
    {
        $expired = InventoryReservation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $reservation) {
            $this->releaseReservation($reservation);
            $reservation->update(['status' => 'expired']);
        }

        return $expired->count();
    }

    /**
     * Adjust stock quantity manually (e.g. by seller or audit).
     */
    public function adjustStock(int $variantId, int $quantityDelta, string $reason, ?int $actorId = null): Inventory
    {
        return DB::transaction(function () use ($variantId, $quantityDelta, $reason, $actorId) {
            $inventory = Inventory::firstOrCreate(
                ['product_variant_id' => $variantId],
                ['available_quantity' => 0, 'reserved_quantity' => 0, 'sold_quantity' => 0]
            );

            $inventory = Inventory::where('id', $inventory->id)->lockForUpdate()->first();

            if ($inventory->available_quantity + $quantityDelta < 0) {
                throw new InsufficientStockException('Stock cannot be adjusted below zero');
            }

            $inventory->available_quantity += $quantityDelta;
            $inventory->save();

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'movement_type' => 'adjustment',
                'quantity_delta' => $quantityDelta,
                'note' => $reason,
                'actor_id' => $actorId,
            ]);

            return $inventory;
        });
    }
}
