<?php

namespace App\Services;

use App\Models\AiAlert;
use App\Models\MedicineInventory;

class InventoryAlertService
{
    public const TYPE_LOW_STOCK = 'MEDICINE LOW STOCK';
    public const TYPE_OUT_OF_STOCK = 'MEDICINE OUT OF STOCK';

    public function reconcile(MedicineInventory $inventory): array
    {
        $inventory->loadMissing('medication');

        $medicineName =
            $inventory->medication?->medicine_name
            ?? ('Medication #' . $inventory->medication_id);

        if ($inventory->isOutOfStock()) {
            $resolved = $this->resolveAlerts(
                $medicineName,
                [self::TYPE_LOW_STOCK]
            );

            $alert = $this->upsertOpenAlert(
                $medicineName,
                self::TYPE_OUT_OF_STOCK,
                'HIGH',
                $medicineName . ' is out of stock. Current stock: 0 units.'
            );

            return [
                'status' => MedicineInventory::STATUS_OUT_OF_STOCK,
                'alert_id' => $alert->id,
                'resolved_count' => $resolved,
            ];
        }

        if ($inventory->isLowStock()) {
            $resolved = $this->resolveAlerts(
                $medicineName,
                [self::TYPE_OUT_OF_STOCK]
            );

            $alert = $this->upsertOpenAlert(
                $medicineName,
                self::TYPE_LOW_STOCK,
                'WARNING',
                $medicineName
                    . ' stock is low. Current stock: '
                    . (int) $inventory->quantity
                    . ' units. Minimum stock: '
                    . (int) $inventory->minimum_stock
                    . ' units.'
            );

            return [
                'status' => MedicineInventory::STATUS_LOW_STOCK,
                'alert_id' => $alert->id,
                'resolved_count' => $resolved,
            ];
        }

        $resolved = $this->resolveAlerts(
            $medicineName,
            [
                self::TYPE_LOW_STOCK,
                self::TYPE_OUT_OF_STOCK,
            ]
        );

        return [
            'status' => $inventory->stock_status,
            'alert_id' => null,
            'resolved_count' => $resolved,
        ];
    }

    private function upsertOpenAlert(
        string $medicineName,
        string $alertType,
        string $severity,
        string $message
    ): AiAlert {
        /*
        | New F8 inventory alerts are facility-level, so resident_id is null.
        | We still match legacy resident-scoped alerts by medicine name so an
        | already-open legacy warning can be reused instead of duplicated.
        */
        $existing = AiAlert::where(
                'alert_type',
                $alertType
            )
            ->where('status', 'OPEN')
            ->where(
                'message',
                'like',
                $medicineName . '%'
            )
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->update([
                'resident_id' => null,
                'severity' => $severity,
                'message' => $message,
                'ai_confidence' => 100,
            ]);

            return $existing->fresh();
        }

        return AiAlert::create([
            'resident_id' => null,
            'alert_type' => $alertType,
            'severity' => $severity,
            'message' => $message,
            'ai_confidence' => 100,
            'status' => 'OPEN',
        ]);
    }

    private function resolveAlerts(
        string $medicineName,
        array $alertTypes
    ): int {
        $alerts = AiAlert::whereIn(
                'alert_type',
                $alertTypes
            )
            ->where('status', 'OPEN')
            ->where(
                'message',
                'like',
                $medicineName . '%'
            )
            ->get();

        foreach ($alerts as $alert) {
            $alert->update([
                'status' => 'RESOLVED',
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
                'resolution_note' =>
                    'Automatically resolved after medicine stock changed.',
            ]);
        }

        return $alerts->count();
    }
}
