<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredInventoryReservations extends Command
{
    protected $signature = 'inventory:release-expired';

    protected $description = 'Release all expired stock reservations back to available inventory';

    public function handle(InventoryService $inventoryService): int
    {
        $this->info('Scanning for expired inventory reservations...');

        $releasedCount = $inventoryService->releaseExpiredReservations();

        $this->info("Successfully released {$releasedCount} expired reservation(s).");

        return self::SUCCESS;
    }
}
