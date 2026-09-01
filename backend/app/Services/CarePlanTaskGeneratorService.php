<?php

namespace App\Services;

use App\Models\NurseTask;
use App\Models\ResidentCarePlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CarePlanTaskGeneratorService
{
    /**
     * Generate routine-care tasks for one target date.
     *
     * This method is intentionally date-scoped:
     * - It does not generate historical ranges.
     * - It does not generate future ranges.
     * - PRN plans are not automatically generated.
     * - Duplicate generation is prevented using
     *   care_plan_id + occurrence_key.
     */
    public function generateForDate($date = null): array
    {
        $targetDate = $date
            ? Carbon::parse($date)->startOfDay()
            : now()->startOfDay();

        $plans = ResidentCarePlan::with('resident')
            ->where('is_active', true)
            ->whereHas('resident', function ($query) {
                $query->where('status', 'Active');
            })
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $targetDate->toDateString());
            })
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $targetDate->toDateString());
            })
            ->get();

        $created = [];
        $existing = [];
        $skipped = [];

        foreach ($plans as $plan) {
            $result = $this->generateForPlan($plan, $targetDate);

            foreach ($result['created'] as $task) {
                $created[] = $task;
            }

            foreach ($result['existing'] as $task) {
                $existing[] = $task;
            }

            foreach ($result['skipped'] as $item) {
                $skipped[] = $item;
            }
        }

        return [
            'date' => $targetDate->toDateString(),
            'plans_checked' => $plans->count(),
            'created_count' => count($created),
            'existing_count' => count($existing),
            'skipped_count' => count($skipped),
            'created' => $created,
            'existing' => $existing,
            'skipped' => $skipped,
        ];
    }

    /**
     * Generate task occurrence(s) for one care plan.
     */
    protected function generateForPlan(
        ResidentCarePlan $plan,
        Carbon $targetDate
    ): array {
        $frequency = strtoupper((string) $plan->frequency);

        if ($frequency === 'PRN') {
            return $this->skip(
                $plan,
                'PRN care plans are not automatically scheduled.'
            );
        }

        if ($frequency === 'DAILY') {
            return $this->generateDaily($plan, $targetDate);
        }

        if ($frequency === 'WEEKLY') {
            return $this->generateWeekly($plan, $targetDate);
        }

        if ($frequency === 'EVERY_SHIFT') {
            return $this->generateEveryShift($plan, $targetDate);
        }

        return $this->skip(
            $plan,
            'Unsupported care-plan frequency.'
        );
    }

    /**
     * DAILY
     */
    protected function generateDaily(
        ResidentCarePlan $plan,
        Carbon $targetDate
    ): array {
        $scheduledTime = $this->buildScheduledDateTime(
            $targetDate,
            $plan->scheduled_time,
            $this->defaultTimeForSlot($plan->time_slot)
        );

        $occurrenceKey = $targetDate->toDateString()
            . ':'
            . $scheduledTime->format('H:i');

        return $this->createOccurrence(
            $plan,
            $scheduledTime,
            $occurrenceKey
        );
    }

    /**
     * WEEKLY
     *
     * days_of_week example:
     * ["MON", "WED", "FRI"]
     */
    protected function generateWeekly(
        ResidentCarePlan $plan,
        Carbon $targetDate
    ): array {
        $days = collect($plan->days_of_week ?? [])
            ->map(fn ($day) => strtoupper((string) $day))
            ->values()
            ->all();

        $today = strtoupper($targetDate->format('D'));

        if (empty($days)) {
            return $this->skip(
                $plan,
                'Weekly care plan has no days_of_week configured.'
            );
        }

        if (!in_array($today, $days, true)) {
            return $this->skip(
                $plan,
                'Care plan is not scheduled for this weekday.'
            );
        }

        $scheduledTime = $this->buildScheduledDateTime(
            $targetDate,
            $plan->scheduled_time,
            $this->defaultTimeForSlot($plan->time_slot)
        );

        $occurrenceKey = $targetDate->toDateString()
            . ':'
            . $scheduledTime->format('H:i');

        return $this->createOccurrence(
            $plan,
            $scheduledTime,
            $occurrenceKey
        );
    }

    /**
     * EVERY_SHIFT
     *
     * Creates AM, PM and NIGHT occurrences.
     */
    protected function generateEveryShift(
        ResidentCarePlan $plan,
        Carbon $targetDate
    ): array {
        $results = [
            'created' => [],
            'existing' => [],
            'skipped' => [],
        ];

        $shifts = [
            'AM' => '08:00',
            'PM' => '14:00',
            'NIGHT' => '20:00',
        ];

        foreach ($shifts as $shift => $time) {
            $scheduledTime = $this->buildScheduledDateTime(
                $targetDate,
                $time,
                $time
            );

            $occurrenceKey = $targetDate->toDateString()
                . ':'
                . $shift;

            $result = $this->createOccurrence(
                $plan,
                $scheduledTime,
                $occurrenceKey,
                $shift
            );

            $results['created'] = array_merge(
                $results['created'],
                $result['created']
            );

            $results['existing'] = array_merge(
                $results['existing'],
                $result['existing']
            );

            $results['skipped'] = array_merge(
                $results['skipped'],
                $result['skipped']
            );
        }

        return $results;
    }

    /**
     * Create one duplicate-safe NurseTask occurrence.
     */
    protected function createOccurrence(
        ResidentCarePlan $plan,
        Carbon $scheduledTime,
        string $occurrenceKey,
        ?string $shift = null
    ): array {
        $existing = NurseTask::where('care_plan_id', $plan->id)
            ->where('occurrence_key', $occurrenceKey)
            ->first();

        if ($existing) {
            return [
                'created' => [],
                'existing' => [
                    $this->taskSummary($existing),
                ],
                'skipped' => [],
            ];
        }

        $task = DB::transaction(function () use (
            $plan,
            $scheduledTime,
            $occurrenceKey,
            $shift
        ) {
            /*
             * Recheck inside the transaction.
             * The database unique index remains the final duplicate guard.
             */
            $existing = NurseTask::where(
                'care_plan_id',
                $plan->id
            )
                ->where(
                    'occurrence_key',
                    $occurrenceKey
                )
                ->first();

            if ($existing) {
                return $existing;
            }

            $taskName = $plan->title;

            if ($shift) {
                $taskName .= ' - ' . $shift;
            }

            return NurseTask::create([
                'resident_id' => $plan->resident_id,
                'care_plan_id' => $plan->id,
                'occurrence_key' => $occurrenceKey,

                'source_alert_id' => null,
                'ai_generated' => false,

                'task_type' => 'ROUTINE_CARE',
                'source_type' => 'CARE_PLAN',

                'assigned_to' => null,

                'task_name' => $taskName,
                'description' => $plan->instructions,

                'scheduled_time' => $scheduledTime,

                'status' => 'Pending',
                'priority' => $plan->priority ?: 'NORMAL',
            ]);
        });

        /*
         * If another process created it between checks,
         * treat it as existing rather than newly created.
         */
        if (
            $task->wasRecentlyCreated === false
            && $task->care_plan_id === $plan->id
            && $task->occurrence_key === $occurrenceKey
        ) {
            return [
                'created' => [],
                'existing' => [
                    $this->taskSummary($task),
                ],
                'skipped' => [],
            ];
        }

        return [
            'created' => [
                $this->taskSummary($task),
            ],
            'existing' => [],
            'skipped' => [],
        ];
    }

    /**
     * Build the occurrence datetime.
     */
    protected function buildScheduledDateTime(
        Carbon $targetDate,
        $configuredTime,
        string $fallbackTime
    ): Carbon {
        $time = $configuredTime
            ? Carbon::parse($configuredTime)->format('H:i')
            : $fallbackTime;

        return Carbon::parse(
            $targetDate->toDateString() . ' ' . $time
        );
    }

    /**
     * Default times when a care plan has a slot but no exact time.
     */
    protected function defaultTimeForSlot($slot): string
    {
        return match (strtoupper((string) $slot)) {
            'AM' => '08:00',
            'PM' => '14:00',
            'NIGHT' => '20:00',
            default => '09:00',
        };
    }

    protected function skip(
        ResidentCarePlan $plan,
        string $reason
    ): array {
        return [
            'created' => [],
            'existing' => [],
            'skipped' => [
                [
                    'care_plan_id' => $plan->id,
                    'resident_id' => $plan->resident_id,
                    'title' => $plan->title,
                    'reason' => $reason,
                ],
            ],
        ];
    }

    protected function taskSummary(NurseTask $task): array
    {
        return [
            'id' => $task->id,
            'resident_id' => $task->resident_id,
            'care_plan_id' => $task->care_plan_id,
            'occurrence_key' => $task->occurrence_key,
            'task_name' => $task->task_name,
            'scheduled_time' => optional(
                $task->scheduled_time
            )->toDateTimeString(),
            'status' => $task->status,
            'priority' => $task->priority,
            'task_type' => $task->task_type,
            'source_type' => $task->source_type,
        ];
    }
}