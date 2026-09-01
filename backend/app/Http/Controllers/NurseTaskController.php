<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\NurseTask;
use App\Models\AiAlert;
use App\Models\ActivityLog;
use App\Models\AlertEscalationLog;
use App\Models\ClinicalTimeline;
use App\Models\AiClinicalOutcome;
use App\Models\HealthPrediction;
use App\Models\Resident;
use App\Models\CareRecord;
use App\Models\ResidentCarePlan;

use App\Helpers\ApiResponse;



class NurseTaskController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | View All Nurse Tasks
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $tasks =

            NurseTask::with([

                'resident',

                'assignedUser',

                'completedBy',

                'careRecord',

                'alert'

            ])
            ->orderBy(
                'created_on',
                'desc'
            )
            ->get();




        return ApiResponse::success(

            'Nurse tasks retrieved successfully',

            $tasks

        );


    }








    /*
    |--------------------------------------------------------------------------
    | Create Routine Care Task
    |--------------------------------------------------------------------------
    */

    public function storeRoutineCare(Request $request)
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'assigned_to' => 'nullable|exists:users,id',
            'task_name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'scheduled_time' => 'nullable|date',
            'priority' => 'nullable|in:LOW,NORMAL,HIGH,URGENT,CRITICAL',
        ]);

        $resident = Resident::findOrFail($validated['resident_id']);

        if ($resident->status !== 'Active') {
            return response()->json([
                'message' => 'Routine care tasks can only be created for active residents.',
            ], 422);
        }

        $task = NurseTask::create([
            'resident_id' => $resident->id,
            'source_alert_id' => null,
            'ai_generated' => false,
            'task_type' => 'ROUTINE_CARE',
            'source_type' => 'CARE',
            'assigned_to' => $validated['assigned_to'] ?? null,
            'task_name' => $validated['task_name'],
            'description' => $validated['description'] ?? null,
            'clinical_action_plan' => null,
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'status' => 'Pending',
            'priority' => $validated['priority'] ?? 'NORMAL',
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'resident_id' => $task->resident_id,
            'module' => 'Nurse Task',
            'action' => 'CREATE_ROUTINE_CARE',
            'description' => 'Routine care task created.',
        ]);

        return ApiResponse::success(
            'Routine care task created successfully',
            [
                'task' => $task->load([
                    'resident',
                    'assignedUser',
                ]),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | View Single Task
    |--------------------------------------------------------------------------
    */


    public function show($id)
    {


        $task =

            NurseTask::with([

                'resident',

                'assignedUser',

                'completedBy',

                'careRecord',

                'alert'

            ])
            ->findOrFail($id);




        return ApiResponse::success(

            'Nurse task retrieved successfully',

            $task

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Assign Task
    |--------------------------------------------------------------------------
    */


    public function assign(
        Request $request,
        $id
    )
    {


        $request->validate([

            'assigned_to'=>'required|exists:users,id'

        ]);




        $task =

            NurseTask::findOrFail($id);




        $task->update([

            'assigned_to'=>
                $request->assigned_to

        ]);






        ActivityLog::create([

            'user_id'=>
                auth()->id(),

            'resident_id'=>
                $task->resident_id,

            'module'=>
                'Nurse Task',

            'action'=>
                'ASSIGN',

            'description'=>
                'Nurse task assigned.'

        ]);





        return ApiResponse::success(

            'Task assigned successfully',

            [

                'task'=>$task

            ]

        );


    }









    /*
    |--------------------------------------------------------------------------
    | Nurse Accept Task
    |--------------------------------------------------------------------------
    */

    public function accept($id)
    {
        $task = NurseTask::findOrFail($id);

        if (in_array($task->status, ['Completed', 'Cancelled'], true)) {
            return response()->json([
                'message' => 'Completed or cancelled tasks cannot be accepted.',
            ], 422);
        }

        $task->update([
            'assigned_to' => auth()->id(),
            'status' => 'ACKNOWLEDGED',
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        if ($task->source_alert_id) {
            $alert = AiAlert::find($task->source_alert_id);

            if ($alert) {
                $alert->update([
                    'acknowledged_by' => auth()->id(),
                    'acknowledged_at' => now(),
                ]);
            }
        }

        ClinicalTimeline::create([
            'resident_id' => $task->resident_id,
            'event_type' => 'NURSE_ACTION',
            'event_title' => $task->ai_generated
                ? 'Nurse Accepted AI Task'
                : 'Nurse Accepted Task',
            'event_description' => $task->ai_generated
                ? 'Nurse reviewed and accepted AI generated clinical intervention.'
                : 'Nurse accepted the assigned care task.',
            'source_type' => $task->ai_generated
                ? 'NurseTaskAIReviewed'
                : 'NurseTaskAccepted',
            'source_id' => $task->id,
            'event_date' => now(),
            'decision_status' => $task->ai_generated ? 'REVIEWED' : null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_action' => $task->ai_generated
                ? 'AI recommendation accepted by nurse.'
                : 'Task accepted by nurse.',
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'resident_id' => $task->resident_id,
            'module' => 'Nurse Task',
            'action' => 'ACCEPT',
            'description' => 'Nurse accepted task.',
        ]);

        return ApiResponse::success(
            'Task accepted successfully',
            [
                'task' => $task->fresh([
                    'resident',
                    'assignedUser',
                    'alert',
                ]),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Complete Nurse Task
    |--------------------------------------------------------------------------
    */

    public function complete(Request $request, $id)
    {
        $validated = $request->validate([
            'completion_notes' => 'nullable|string|max:2000',
            'care_status' => 'nullable|in:COMPLETED,OBSERVED,NEEDS_ATTENTION',
            'care_type' => 'nullable|string|max:100',
            'care_title' => 'nullable|string|max:255',
        ]);

        $task = NurseTask::with('resident')->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Read-only protection for finished tasks
        |--------------------------------------------------------------------------
        */

        if ($task->status === 'Completed') {
            return ApiResponse::success(
                'Task already completed.',
                [
                    'task' => $task->fresh([
                        'resident',
                        'assignedUser',
                        'completedBy',
                        'careRecord',
                        'alert',
                    ]),
                    'care_record' => $task->care_record_id
                        ? CareRecord::find($task->care_record_id)
                        : null,
                    'alert' => $task->source_alert_id
                        ? AiAlert::find($task->source_alert_id)
                        : null,
                ]
            );
        }

        if ($task->status === 'Cancelled') {
            return response()->json([
                'message' => 'Cancelled tasks cannot be completed.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Workflow protection
        |--------------------------------------------------------------------------
        */

        if ($task->status !== 'ACKNOWLEDGED') {
            return response()->json([
                'message' => 'The task must be accepted before it can be completed.',
            ], 422);
        }

        if (
            $task->task_type === 'ROUTINE_CARE'
            && $task->resident
            && $task->resident->status !== 'Active'
        ) {
            return response()->json([
                'message' => 'Routine care tasks can only be completed for active residents.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | F5.6 Completion evidence requirement
        |--------------------------------------------------------------------------
        |
        | Routine care completion becomes part of the resident's formal care
        | history, so a meaningful completion note is required.
        |
        */

        $completionNotes = trim((string) ($validated['completion_notes'] ?? ''));

        if (
            $task->task_type === 'ROUTINE_CARE'
            && $completionNotes === ''
        ) {
            return response()->json([
                'message' => 'Completion notes are required for routine care tasks.',
                'errors' => [
                    'completion_notes' => [
                        'Please record the care provided and any relevant observations.',
                    ],
                ],
            ], 422);
        }

        $result = DB::transaction(function () use ($id, $validated, $completionNotes) {

            /*
            |--------------------------------------------------------------------------
            | Lock task row during completion
            |--------------------------------------------------------------------------
            |
            | This serializes competing completion requests for the same task.
            | The second request will see the completed state and will not create
            | another CareRecord, timeline event, or activity log.
            |
            */

            $task = NurseTask::with('resident')
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($task->status === 'Completed') {
                return [
                    'already_completed' => true,
                    'task' => $task->fresh([
                        'resident',
                        'assignedUser',
                        'completedBy',
                        'careRecord',
                        'alert',
                    ]),
                    'care_record' => $task->care_record_id
                        ? CareRecord::find($task->care_record_id)
                        : null,
                    'alert' => $task->source_alert_id
                        ? AiAlert::find($task->source_alert_id)
                        : null,
                ];
            }

            if ($task->status === 'Cancelled') {
                abort(
                    response()->json([
                        'message' => 'Cancelled tasks cannot be completed.',
                    ], 422)
                );
            }

            if ($task->status !== 'ACKNOWLEDGED') {
                abort(
                    response()->json([
                        'message' => 'The task must be accepted before it can be completed.',
                    ], 422)
                );
            }

            if (
                $task->task_type === 'ROUTINE_CARE'
                && $task->resident
                && $task->resident->status !== 'Active'
            ) {
                abort(
                    response()->json([
                        'message' => 'Routine care tasks can only be completed for active residents.',
                    ], 422)
                );
            }

            if (
                $task->task_type === 'ROUTINE_CARE'
                && $completionNotes === ''
            ) {
                abort(
                    response()->json([
                        'message' => 'Completion notes are required for routine care tasks.',
                        'errors' => [
                            'completion_notes' => [
                                'Please record the care provided and any relevant observations.',
                            ],
                        ],
                    ], 422)
                );
            }

            $completedAt = now();
            $completedBy = auth()->id();

            $task->update([
                'status' => 'Completed',
                'completed_time' => $completedAt,
                'completed_by' => $completedBy,
                'completion_notes' => $completionNotes !== ''
                    ? $completionNotes
                    : null,
            ]);

            $careRecord = null;
            $alert = null;

            /*
            |--------------------------------------------------------------------------
            | Routine Care -> Care Record + Timeline evidence
            |--------------------------------------------------------------------------
            */

            if ($task->task_type === 'ROUTINE_CARE') {
                if ($task->care_record_id) {
                    $careRecord = CareRecord::find($task->care_record_id);
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve meaningful care evidence from the care plan
                |--------------------------------------------------------------------------
                |
                | For CARE_PLAN tasks, the resident care plan is the authoritative
                | source for care_type. This prevents a generic frontend label such
                | as "Care Plan" from replacing the actual clinical/operational care
                | category (for example Personal Care or Skin / Wound).
                |
                */

                $carePlan = null;

                if (
                    $task->source_type === 'CARE_PLAN'
                    && $task->care_plan_id
                ) {
                    $carePlan = ResidentCarePlan::find($task->care_plan_id);
                }

                $resolvedCareType =
                    $carePlan?->care_type
                    ?: ($validated['care_type'] ?? 'Routine Care');

                $resolvedCareTitle =
                    $carePlan?->title
                    ?: ($validated['care_title']
                        ?? $task->task_name
                        ?? 'Routine Care Completed');

                if (!$careRecord) {
                    $careRecord = CareRecord::create([
                        'resident_id' => $task->resident_id,
                        'care_type' => $resolvedCareType,
                        'title' => $resolvedCareTitle,
                        'notes' => $completionNotes !== ''
                            ? $completionNotes
                            : ($task->description ?? null),
                        'care_status' => $validated['care_status'] ?? 'COMPLETED',
                        'recorded_at' => $completedAt,
                        'recorded_by' => $completedBy,
                    ]);

                    $task->update([
                        'care_record_id' => $careRecord->id,
                    ]);
                }

                ClinicalTimeline::firstOrCreate(
                    [
                        'resident_id' => $task->resident_id,
                        'source_type' => 'NurseTaskRoutineCareCompleted',
                        'source_id' => $task->id,
                    ],
                    [
                        'event_type' => 'NURSE_ACTION',
                        'event_title' => 'Routine Care Task Completed',
                        'event_description' => $completionNotes !== ''
                            ? $completionNotes
                            : (($task->task_name ?: 'Routine care task') . ' completed by nurse.'),
                        'event_date' => $completedAt,
                        'reviewed_by' => $completedBy,
                        'reviewed_at' => $completedAt,
                        'review_action' => 'Routine care task completed and care record captured.',
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | AI-generated task completion
            |--------------------------------------------------------------------------
            */

            elseif ($task->ai_generated) {
                if ($task->source_alert_id) {
                    $alert = AiAlert::find($task->source_alert_id);
                }

                if ($alert) {
                    $alert->update([
                        'status' => 'RESOLVED',
                        'resolved_by' => $completedBy,
                        'resolved_at' => $completedAt,
                        'resolution_note' =>
                            'Resolved through nurse task completion. Task ID: '
                            .$task->id,
                    ]);

                    AlertEscalationLog::where(
                        'alert_id',
                        $alert->id
                    )
                    ->latest()
                    ->first()
                    ?->update([
                        'status' => 'RESOLVED',
                        'resolved_at' => $completedAt,
                    ]);
                }

                ClinicalTimeline::where(
                    'resident_id',
                    $task->resident_id
                )
                ->where(
                    'event_type',
                    'AI_DECISION'
                )
                ->latest('event_date')
                ->first()
                ?->update([
                    'decision_status' => 'RESOLVED',
                    'reviewed_by' => $completedBy,
                    'reviewed_at' => $completedAt,
                    'review_action' =>
                        'AI recommendation reviewed, intervention completed, and clinical task resolved.',
                ]);

                ClinicalTimeline::firstOrCreate(
                    [
                        'resident_id' => $task->resident_id,
                        'source_type' => 'NurseTaskAICompleted',
                        'source_id' => $task->id,
                    ],
                    [
                        'event_type' => 'NURSE_ACTION',
                        'event_title' => 'Nurse Task Completed',
                        'event_description' => $completionNotes !== ''
                            ? $completionNotes
                            : 'AI generated nurse task completed.',
                        'event_date' => $completedAt,
                        'reviewed_by' => $completedBy,
                        'reviewed_at' => $completedAt,
                        'review_action' => 'Nurse completed AI clinical intervention.',
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | General task completion
            |--------------------------------------------------------------------------
            */

            else {
                ClinicalTimeline::firstOrCreate(
                    [
                        'resident_id' => $task->resident_id,
                        'source_type' => 'NurseTaskCompleted',
                        'source_id' => $task->id,
                    ],
                    [
                        'event_type' => 'NURSE_ACTION',
                        'event_title' => 'Nurse Task Completed',
                        'event_description' => $completionNotes !== ''
                            ? $completionNotes
                            : (($task->task_name ?: 'Nurse task') . ' completed.'),
                        'event_date' => $completedAt,
                        'reviewed_by' => $completedBy,
                        'reviewed_at' => $completedAt,
                        'review_action' => 'Nurse completed task.',
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Activity audit
            |--------------------------------------------------------------------------
            */

            ActivityLog::create([
                'user_id' => $completedBy,
                'resident_id' => $task->resident_id,
                'module' => 'Nurse Task',
                'action' => 'COMPLETE',
                'description' => $task->ai_generated
                    ? 'Nurse completed AI clinical task.'
                    : ($task->task_type === 'ROUTINE_CARE'
                        ? 'Nurse completed routine care task.'
                        : 'Nurse completed task.'),
            ]);

            return [
                'already_completed' => false,
                'task' => $task->fresh([
                    'resident',
                    'assignedUser',
                    'completedBy',
                    'careRecord',
                    'alert',
                ]),
                'care_record' => $careRecord,
                'alert' => $alert,
            ];
        });

        return ApiResponse::success(
            !empty($result['already_completed'])
                ? 'Task already completed.'
                : 'Task completed successfully',
            $result
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Acknowledge Nurse Task
    |--------------------------------------------------------------------------
    */


    public function acknowledge($id)
    {


        $task =

            NurseTask::findOrFail($id);






        if($task->acknowledged_at)
        {


            return ApiResponse::success(


                'Task already acknowledged.',


                [

                    'task'=>$task

                ]


            );


        }







        $task->update([


            'status'=>
                'ACKNOWLEDGED',


            'acknowledged_by'=>
                auth()->id(),


            'acknowledged_at'=>
                now()


        ]);








        /*
        |--------------------------------------------------------------------------
        | Update Alert Escalation
        |--------------------------------------------------------------------------
        */


        $escalationLog = null;



        if($task->source_alert_id)
        {


            $escalationLog =

                AlertEscalationLog::where(

                    'alert_id',

                    $task->source_alert_id

                )
                ->latest()
                ->first();





            if($escalationLog)
            {


                $escalationLog->update([


                    'status'=>
                        'ACKNOWLEDGED',


                    'acknowledged_at'=>
                        now()


                ]);


            }









            /*
            |--------------------------------------------------------------------------
            | Update AI Alert Acknowledgement
            |--------------------------------------------------------------------------
            */


            $alert = AiAlert::find(
    $task->source_alert_id
);


if($alert)
{

    \Log::info('ACKNOWLEDGE ALERT FOUND', [

        'alert_id' => $alert->id,

        'before_acknowledged_by' => $alert->acknowledged_by,

        'before_acknowledged_at' => $alert->acknowledged_at,

        'user_id' => auth()->id(),

    ]);


    $alert->update([

        'acknowledged_by'=>
            auth()->id(),

        'acknowledged_at'=>
            now()

    ]);


    \Log::info('ACKNOWLEDGE ALERT AFTER UPDATE', [

        'alert_id' => $alert->id,

        'after_acknowledged_by' => $alert->fresh()->acknowledged_by,

        'after_acknowledged_at' => $alert->fresh()->acknowledged_at,

    ]);

}
else
{

    \Log::warning('ACKNOWLEDGE ALERT NOT FOUND', [

        'source_alert_id'=>$task->source_alert_id

    ]);

}



        }









        /*
        |--------------------------------------------------------------------------
        | Update Clinical Timeline
        |--------------------------------------------------------------------------
        */

        if ($task->ai_generated) {
            ClinicalTimeline::where(
                'resident_id',
                $task->resident_id
            )
            ->where(
                'event_type',
                'AI_DECISION'
            )
            ->latest('event_date')
            ->first()
            ?->update([
                'decision_status' => 'ACKNOWLEDGED',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_action' =>
                    'Nurse acknowledged AI clinical decision.',
            ]);
        }


        ActivityLog::create([


            'user_id'=>
                auth()->id(),


            'resident_id'=>
                $task->resident_id,


            'module'=>
                'Nurse Task',


            'action'=>
                'ACKNOWLEDGE',


            'description'=>

                'Nurse acknowledged AI generated task.'


        ]);









        return ApiResponse::success(


            'Task acknowledged successfully',


            [

                'task'=>$task,

                'escalation_log'=>$escalationLog


            ]


        );


    }

    /*
|--------------------------------------------------------------------------
| Record Clinical Outcome
|--------------------------------------------------------------------------
*/

public function recordOutcome(
    Request $request,
    $id
)
{


    $request->validate([

        'outcome_status'=>
            'required|in:IMPROVED,STABLE,DETERIORATED,UNKNOWN',

        'outcome_notes'=>
            'nullable|string'

    ]);





    $task = NurseTask::findOrFail($id);






    /*
    |--------------------------------------------------------------------------
    | Get Related AI Prediction
    |--------------------------------------------------------------------------
    */


    $prediction =
        HealthPrediction::where(
            'resident_id',
            $task->resident_id
        )
        ->latest('created_on')
        ->first();








    /*
    |--------------------------------------------------------------------------
    | Calculate AI Accuracy
    |--------------------------------------------------------------------------
    */


    $accuracy = null;



    if($prediction)
    {


        if(
            $request->outcome_status
            ===
            'IMPROVED'
        )
        {

            $accuracy = 95;

        }

        elseif(

            $request->outcome_status
            ===
            'STABLE'

        )
        {

            $accuracy = 85;

        }

        elseif(

            $request->outcome_status
            ===
            'DETERIORATED'

        )
        {

            $accuracy = 40;

        }


    }



    /*
    |--------------------------------------------------------------------------
    | Create AI Outcome Record
    |--------------------------------------------------------------------------
    */


            $outcome = AiClinicalOutcome::create([


                'resident_id'=>
                    $task->resident_id,


                'nurse_task_id'=>
                    $task->id,


                'prediction_id'=>
                    $prediction?->id,


                'initial_risk_level'=>
                    $prediction?->risk_level,


                'initial_confidence'=>
                    $prediction?->confidence ?? 0,


                'outcome_status'=>
                    $request->outcome_status,


                'outcome_notes'=>
                    $request->outcome_notes,


                'ai_accuracy_score'=>
                    $accuracy,


                'recorded_by'=>
                    auth()->id(),


                'recorded_at'=>
                    now()


            ]);








            return ApiResponse::success(

                'Clinical outcome recorded successfully',

                [

                    'outcome'=>$outcome

                ]

            );


        }





}