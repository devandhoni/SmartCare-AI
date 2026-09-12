<?php

namespace App\Services;

use App\Models\AiAlert;
use App\Models\MedicineInventory;

class InventoryExpiryAlertService
{
    public const TYPE_EXPIRING_SOON = 'MEDICINE EXPIRING SOON';
    public const TYPE_EXPIRED = 'MEDICINE EXPIRED';

    public function reconcile(MedicineInventory $inventory): array
    {
        $inventory->loadMissing('medication');

        $medicineName =
            $inventory->medication?->medicine_name
            ?? ('Medication #' . $inventory->medication_id);

        if ($inventory->isExpired()) {
            $resolved = $this->resolveAlerts(
                $medicineName,
                [self::TYPE_EXPIRING_SOON]
            );

            $alert = $this->upsertOpenAlert(
                $medicineName,
                self::TYPE_EXPIRED,
                'HIGH',
                $medicineName
                    . ' stock expired on '
                    . $inventory->expiry_date->format('Y-m-d')
                    . '. Do not administer from this stock.'
            );

            return [
                'status' => MedicineInventory::STATUS_EXPIRED,
                'alert_id' => $alert->id,
                'resolved_count' => $resolved,
            ];
        }

        if ($inventory->isExpiringSoon()) {
            $resolved = $this->resolveAlerts(
                $medicineName,
                [self::TYPE_EXPIRED]
            );

            $days = $inventory->daysToExpiry();

            $alert = $this->upsertOpenAlert(
                $medicineName,
                self::TYPE_EXPIRING_SOON,
                'WARNING',
                $medicineName
                    . ' stock expires on '
                    . $inventory->expiry_date->format('Y-m-d')
                    . ' ('
                    . $days
                    . ' day'
                    . ($days === 1 ? '' : 's')
                    . ' remaining).'
            );

            return [
                'status' => MedicineInventory::STATUS_EXPIRING_SOON,
                'alert_id' => $alert->id,
                'resolved_count' => $resolved,
            ];
        }

        $resolved = $this->resolveAlerts(
            $medicineName,
            [
                self::TYPE_EXPIRING_SOON,
                self::TYPE_EXPIRED,
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
                    'Automatically resolved after medicine expiry status changed.',
            ]);
        }

        return $alerts->count();
    }
}
