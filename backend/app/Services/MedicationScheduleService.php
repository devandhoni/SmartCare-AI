<?php

namespace App\Services;

use App\Models\MedicationAdministrationRecord;
use App\Models\NurseTask;
use App\Models\ResidentMedication;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MedicationScheduleService
{
    /*
    |--------------------------------------------------------------------------
    | Check Due Medications
    |--------------------------------------------------------------------------
    |
    | This method is intentionally side-effectful: it may create a NurseTask
    | and staff notifications. It must be called only by the explicit medication
    | due-check workflow, never simply to render Today.
    |
    | Current F6.1 rule:
    | - only Active residents
    | - only prescriptions active on today's date
    | - scheduled medication is considered due from its scheduled time until
    |   30 minutes afterwards
    | - one reminder task per resident-medication/date occurrence
    | - completed medication is not reminded
    |
    */

    public function checkDueMedications(): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $medications = ResidentMedication::with([
                'resident',
                'medication',
            ])
            ->whereNotNull('scheduled_time')
            ->whereHas('resident', function ($query) {
                $query->where('status', 'Active');
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->get();

        $dueMedications = [];

        foreach ($medications as $medication) {
            if (!$medication->resident || !$medication->medication) {
                continue;
            }

            /*
            | Build today's scheduled occurrence using the application
            | timezone. ResidentMedication::scheduled_time may be returned as
            | either a time string or a Carbon value depending on the model.
            */
            $scheduledTime = $medication->scheduled_time instanceof Carbon
                ? $medication->scheduled_time->format('H:i:s')
                : Carbon::parse($medication->scheduled_time)->format('H:i:s');

            $scheduledAt = Carbon::parse(
                $today . ' ' . $scheduledTime
            );

            /*
            | Due window: scheduled time through 30 minutes afterwards.
            */
            if (
                $now->lt($scheduledAt)
                || $now->gt($scheduledAt->copy()->addMinutes(30))
            ) {
                continue;
            }

            $completed =
                MedicationAdministrationRecord::where(
                    'resident_medication_id',
                    $medication->id
                )
                ->whereDate('administered_date', $today)
                ->where('status', 'COMPLETED')
                ->exists();

            if ($completed) {
                continue;
            }

            /*
            | NurseTask has no resident_medication_id column. occurrence_key
            | therefore provides a stable medication occurrence identity and
            | prevents one resident's different medicines from suppressing
            | each other's reminders.
            */
            $occurrenceKey =
                'MED:'
                . $medication->id
                . ':'
                . $today;

            DB::transaction(function () use (
                $medication,
                $scheduledAt,
                $occurrenceKey
            ) {
                $existingTask =
                    NurseTask::where(
                        'occurrence_key',
                        $occurrenceKey
                    )
                    ->lockForUpdate()
                    ->first();

                if ($existingTask) {
                    return;
                }

                $task = NurseTask::create([
                    'resident_id' =>
                        $medication->resident_id,

                    'occurrence_key' =>
                        $occurrenceKey,

                    'source_type' =>
                        'MEDICATION',

                    'task_name' =>
                        'Administer Medication',

                    'description' =>
                        $medication->medication->medicine_name
                        . ' for '
                        . $medication->resident->full_name
                        . ' is due at '
                        . $scheduledAt->format('H:i'),

                    'scheduled_time' =>
                        $scheduledAt,

                    'status' =>
                        'Pending',

                    'priority' =>
                        'NORMAL',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Staff Notifications
                |--------------------------------------------------------------------------
                */

                app(StaffNotificationService::class)
                    ->notifyOperationalStaff(
                        'Medication Reminder',

                        $medication->medication->medicine_name
                        . ' for '
                        . $medication->resident->full_name
                        . ' is due at '
                        . $scheduledAt->format('H:i'),

                        'MEDICATION'
                    );
            });

            $dueMedications[] = [
                'resident_medication_id' =>
                    $medication->id,

                'resident_id' =>
                    $medication->resident_id,

                'resident' =>
                    $medication->resident->full_name,

                'medicine' =>
                    $medication->medication->medicine_name,

                'time_slot' =>
                    $medication->time_slot,

                'scheduled_time' =>
                    $scheduledAt->toDateTimeString(),

                'minutes_difference' =>
                    $scheduledAt->diffInMinutes($now),
            ];
        }

        return $dueMedications;
    }
}