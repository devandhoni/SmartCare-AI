<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AIExecutiveIntelligenceSnapshot;
use App\Services\AIExecutiveIntelligenceSnapshotEngine;

class CaptureAIExecutiveIntelligenceSnapshot extends Command
{
    /*
    |--------------------------------------------------------------------------
    | Command Signature
    |--------------------------------------------------------------------------
    |
    | --force allows us to manually create another snapshot on the same day
    | for testing or administrative purposes.
    |
    */

    protected $signature =
        'ai:capture-executive-snapshot {--force : Capture a snapshot even if one already exists today}';


    /*
    |--------------------------------------------------------------------------
    | Command Description
    |--------------------------------------------------------------------------
    */

    protected $description =
        'Capture the daily SmartCare AI executive intelligence snapshot.';


    /*
    |--------------------------------------------------------------------------
    | Execute Command
    |--------------------------------------------------------------------------
    */

    public function handle(
        AIExecutiveIntelligenceSnapshotEngine $snapshotEngine
    ): int {
        /*
        |--------------------------------------------------------------------------
        | 1. Determine Daily Snapshot Window
        |--------------------------------------------------------------------------
        */

        $dayStart =
            now()
                ->copy()
                ->startOfDay();

        $dayEnd =
            now()
                ->copy()
                ->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | 2. Check Existing Snapshot
        |--------------------------------------------------------------------------
        |
        | By default we keep one scheduled snapshot per calendar day.
        |
        | Historical manually-created snapshots already in your database
        | are not a problem. This guardrail applies when this command runs.
        |
        */

        $existingSnapshot =
            AIExecutiveIntelligenceSnapshot::query()
                ->whereBetween(
                    'captured_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->latest(
                    'captured_at'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | 3. Duplicate Protection
        |--------------------------------------------------------------------------
        */

        if (
            $existingSnapshot
            &&
            !$this->option('force')
        ) {
            $this->warn(
                'Executive intelligence snapshot already exists for today.'
            );

            $this->line(
                'Snapshot ID: '
                . $existingSnapshot->id
            );

            $this->line(
                'Captured At: '
                . $existingSnapshot->captured_at
            );

            $this->line(
                'Use --force only when an additional same-day snapshot is intentionally required.'
            );

            return self::SUCCESS;
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Capture Snapshot
        |--------------------------------------------------------------------------
        */

        try {

            $result =
                $snapshotEngine->capture();

        } catch (\Throwable $e) {

            $this->error(
                'Executive intelligence snapshot capture failed.'
            );

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Validate Capture Result
        |--------------------------------------------------------------------------
        */

        $snapshotCreated =
            (bool) (
                $result[
                    'snapshot_created'
                ]
                ?? false
            );

        if (!$snapshotCreated) {

            $this->error(
                'Snapshot engine did not confirm successful snapshot creation.'
            );

            return self::FAILURE;
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Success Output
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Executive AI intelligence snapshot captured successfully.'
        );

        $this->table(
            [
                'Metric',
                'Value',
            ],
            [
                [
                    'Snapshot ID',
                    $result[
                        'snapshot_id'
                    ]
                    ?? 'UNKNOWN',
                ],
                [
                    'Captured At',
                    $result[
                        'captured_at'
                    ]
                    ?? 'UNKNOWN',
                ],
                [
                    'Report Status',
                    $result[
                        'report_status'
                    ]
                    ?? 'UNKNOWN',
                ],
                [
                    'Active Care Residents',
                    $result[
                        'snapshot_summary'
                    ]['active_care_residents']
                    ?? 0,
                ],
                [
                    'Active Critical Cases',
                    $result[
                        'snapshot_summary'
                    ]['active_critical_cases']
                    ?? 0,
                ],
                [
                    'Active Care Alerts',
                    $result[
                        'snapshot_summary'
                    ]['active_care_alerts']
                    ?? 0,
                ],
                [
                    'Predictive Priority Residents',
                    $result[
                        'snapshot_summary'
                    ]['predictive_priority_residents']
                    ?? 0,
                ],
                [
                    'Execution Ready Actions',
                    $result[
                        'snapshot_summary'
                    ]['execution_ready_actions']
                    ?? 0,
                ],
                [
                    'Workflow Success Rate',
                    (
                        $result[
                            'snapshot_summary'
                        ]['workflow_success_rate']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'AI Accuracy',
                    (
                        $result[
                            'snapshot_summary'
                        ]['average_ai_accuracy']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'SLA Compliance',
                    (
                        $result[
                            'snapshot_summary'
                        ]['sla_compliance_percentage']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'Task Completion Rate',
                    (
                        $result[
                            'snapshot_summary'
                        ]['task_completion_rate']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'Learning Maturity',
                    $result[
                        'snapshot_summary'
                    ]['learning_maturity']
                    ?? 'UNKNOWN',
                ],
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 7. Forced Capture Notice
        |--------------------------------------------------------------------------
        */

        if ($this->option('force')) {

            $this->warn(
                'This snapshot was created using --force. Multiple snapshots may now exist for the same day.'
            );
        }


        return self::SUCCESS;
    }
}