<?php

namespace App\Console\Commands;

use App\Models\MedicineInventory;
use App\Services\InventoryExpiryAlertService;
use Illuminate\Console\Command;

class ReconcileMedicineExpiryAlerts extends Command
{
    protected $signature = 'inventory:reconcile-expiry';

    protected $description =
        'Create, update, or resolve medicine expiry alerts from current inventory expiry dates.';

    public function handle(
        InventoryExpiryAlertService $inventoryExpiryAlertService
    ): int {
        $processed = 0;
        $expiringSoon = 0;
        $expired = 0;

        MedicineInventory::with('medication')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($inventories) use (
                    $inventoryExpiryAlertService,
                    &$processed,
                    &$expiringSoon,
                    &$expired
                ) {
                    foreach ($inventories as $inventory) {
                        $result =
                            $inventoryExpiryAlertService->reconcile(
                                $inventory
                            );

                        $processed++;

                        if (
                            $result['status']
                            === MedicineInventory::STATUS_EXPIRING_SOON
                        ) {
                            $expiringSoon++;
                        }

                        if (
                            $result['status']
                            === MedicineInventory::STATUS_EXPIRED
                        ) {
                            $expired++;
                        }
                    }
                }
            );

        $this->info(
            'Medicine expiry reconciliation complete. '
            . 'Processed: ' . $processed
            . ', expiring soon: ' . $expiringSoon
            . ', expired: ' . $expired
            . '.'
        );

        return self::SUCCESS;
    }
}
