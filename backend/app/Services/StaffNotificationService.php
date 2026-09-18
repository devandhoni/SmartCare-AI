<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class StaffNotificationService
{
    /**
     * Create an individual notification for each active
     * Administrator and Nurse.
     *
     * Returns the number of notifications created.
     */
    public function notifyOperationalStaff(
        string $title,
        string $message,
        string $type
    ): int {
        $userIds = User::query()
            ->where('status', 'Active')
            ->whereHas('role', function ($query) {
                $query->whereIn('role_name', [
                    'Administrator',
                    'Nurse',
                ]);
            })
            ->pluck('id');

        $created = 0;

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'read_status' => 0,
            ]);

            $created++;
        }

        return $created;
    }
}