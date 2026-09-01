import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { acceptNurseTask } from "../api/nurseTaskApi";
import api from "../services/api";

function NurseTaskTable({ tasks = [], onRefresh }) {
    const navigate = useNavigate();

    const [processingId, setProcessingId] = useState(null);
    const [completionTask, setCompletionTask] = useState(null);
    const [completionNotes, setCompletionNotes] = useState("");
    const [completionNoteError, setCompletionNoteError] = useState("");
    const [actionError, setActionError] = useState("");
    const [success, setSuccess] = useState("");

    const handleAccept = async (task) => {
        try {
            setProcessingId(task.id);
            setActionError("");
            setSuccess("");

            await acceptNurseTask(task.id);

            setSuccess(
                `Task accepted for ${
                    task.resident?.full_name || "resident"
                }.`
            );

            await onRefresh?.();
        } catch (err) {
            console.error("Accept task error:", err);

            setActionError(
                err?.response?.data?.message ||
                    "Unable to accept this task."
            );
        } finally {
            setProcessingId(null);
        }
    };

    const openCompletionModal = (task) => {
        setActionError("");
        setSuccess("");
        setCompletionNoteError("");
        setCompletionNotes("");
        setCompletionTask(task);
    };

    const closeCompletionModal = () => {
        if (processingId) return;

        setCompletionTask(null);
        setCompletionNotes("");
        setCompletionNoteError("");
    };

    const handleComplete = async (event) => {
        event.preventDefault();

        if (!completionTask) return;

        const trimmedNotes = completionNotes.trim();
        const routineCare =
            completionTask.task_type === "ROUTINE_CARE";

        if (routineCare && !trimmedNotes) {
            setCompletionNoteError(
                "Please enter completion notes for routine care."
            );
            return;
        }

        try {
            setProcessingId(completionTask.id);
            setActionError("");
            setSuccess("");
            setCompletionNoteError("");

            const payload = {
                completion_notes: trimmedNotes || null,
            };

            if (routineCare) {
                payload.care_status = "COMPLETED";
                payload.care_type =
                    completionTask.care_type ||
                    completionTask.care_plan?.care_type ||
                    (completionTask.source_type === "CARE_PLAN"
                        ? "Care Plan"
                        : "Routine Care");

                payload.care_title =
                    completionTask.task_name ||
                    "Routine Care Completed";
            }

            const response = await api.put(
                `/nurse/tasks/${completionTask.id}/complete`,
                payload
            );

            const alreadyCompleted =
                Boolean(
                    response?.data?.already_completed ??
                    response?.data?.data?.already_completed
                );

            if (alreadyCompleted) {
                setSuccess("Task was already completed.");
            } else if (routineCare) {
                setSuccess(
                    "Task completed and Care Record created."
                );
            } else {
                setSuccess("Task completed successfully.");
            }

            setCompletionTask(null);
            setCompletionNotes("");
            setCompletionNoteError("");

            await onRefresh?.();
        } catch (err) {
            console.error("Complete task error:", err);

            const validationErrors =
                err?.response?.data?.errors;

            const firstValidationError = validationErrors
                ? Object.values(validationErrors)
                      .flat()
                      .find(Boolean)
                : null;

            setActionError(
                firstValidationError ||
                    err?.response?.data?.message ||
                    "Unable to complete this task."
            );
        } finally {
            setProcessingId(null);
        }
    };

    const sourceInfo = (task) => {
        if (task.ai_generated) {
            return {
                label: "AI Clinical",
                cls: "bg-violet-100 text-violet-700",
            };
        }

        if (task.source_type === "CARE_PLAN") {
            return {
                label: "Care Plan",
                cls: "bg-blue-100 text-blue-700",
            };
        }

        if (task.task_type === "ROUTINE_CARE") {
            return {
                label: "Routine Care",
                cls: "bg-emerald-100 text-emerald-700",
            };
        }

        return {
            label: "General",
            cls: "bg-slate-100 text-slate-600",
        };
    };

    const isOverdue = (task) => {
        if (
            !task.scheduled_time ||
            ["Completed", "Cancelled"].includes(task.status)
        ) {
            return false;
        }

        const due = new Date(task.scheduled_time);

        return (
            !Number.isNaN(due.getTime()) &&
            due.getTime() < Date.now()
        );
    };

    const completedByName = (task) =>
        task.completed_by?.full_name ||
        task.completed_by?.name ||
        task.completedBy?.full_name ||
        task.completedBy?.name ||
        "System / Unassigned";

    const linkedCareRecord = (task) =>
        task.care_record || task.careRecord || null;

    if (!tasks.length) {
        return (
            <section className="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h2 className="font-bold text-slate-900">
                    Nurse Task Queue
                </h2>

                <p className="mt-2 text-sm text-slate-500">
                    No tasks match the selected filters.
                </p>
            </section>
        );
    }

    return (
        <>
            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="font-bold text-slate-900">
                            Nurse Task Queue
                        </h2>

                        <p className="mt-1 text-sm text-slate-500">
                            Complete scheduled care here. Routine-care
                            completion automatically creates a Care Record.
                        </p>
                    </div>

                    <span className="self-start rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                        {tasks.length} task
                        {tasks.length === 1 ? "" : "s"}
                    </span>
                </div>

                {(actionError || success) && (
                    <div className="px-5 pt-4">
                        {actionError && (
                            <div className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                {actionError}
                            </div>
                        )}

                        {success && (
                            <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                                {success}
                            </div>
                        )}
                    </div>
                )}

                <div className="space-y-4 p-5">
                    {tasks.map((task) => {
                        const source = sourceInfo(task);
                        const overdue = isOverdue(task);
                        const accepted =
                            task.status === "ACKNOWLEDGED";
                        const completed =
                            task.status === "Completed";
                        const cancelled =
                            task.status === "Cancelled";

                        const residentActive =
                            !task.resident?.status ||
                            task.resident.status === "Active";

                        const careRecord =
                            linkedCareRecord(task);

                        return (
                            <article
                                key={task.id}
                                className={`rounded-2xl border p-4 sm:p-5 ${
                                    overdue
                                        ? "border-red-200 bg-red-50/40"
                                        : completed
                                          ? "border-emerald-200 bg-emerald-50/30"
                                          : cancelled
                                            ? "border-slate-200 bg-slate-50"
                                            : "border-slate-200"
                                }`}
                            >
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span
                                                className={`rounded-full px-2.5 py-1 text-xs font-bold ${source.cls}`}
                                            >
                                                {source.label}
                                            </span>

                                            <PriorityBadge
                                                priority={task.priority}
                                            />

                                            {overdue && (
                                                <Badge
                                                    text="Overdue"
                                                    cls="bg-red-100 text-red-700"
                                                />
                                            )}

                                            <StatusBadge
                                                status={task.status}
                                            />
                                        </div>

                                        <h3 className="mt-3 text-lg font-bold text-slate-900">
                                            {task.task_name ||
                                                "Nurse Task"}
                                        </h3>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                task.resident_id &&
                                                navigate(
                                                    `/residents/${task.resident_id}`
                                                )
                                            }
                                            className="mt-1 text-sm font-semibold text-blue-700 hover:underline"
                                        >
                                            {task.resident
                                                ?.full_name ||
                                                "Unknown Resident"}
                                        </button>

                                        {task.description && (
                                            <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                                                {task.description}
                                            </p>
                                        )}

                                        {task.alert?.message && (
                                            <div className="mt-3 rounded-xl border border-violet-100 bg-violet-50 p-3">
                                                <p className="text-xs font-bold uppercase tracking-wide text-violet-700">
                                                    AI Clinical
                                                    Reason
                                                </p>

                                                <p className="mt-1 text-sm text-slate-700">
                                                    {
                                                        task.alert
                                                            .message
                                                    }
                                                </p>
                                            </div>
                                        )}
                                    </div>

                                    <div className="grid shrink-0 grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:min-w-[300px]">
                                        <Info
                                            label="Scheduled"
                                            value={formatDateTime(
                                                task.scheduled_time
                                            )}
                                        />

                                        <Info
                                            label="Assigned Nurse"
                                            value={
                                                task.assigned_user
                                                    ?.full_name ||
                                                "Not assigned"
                                            }
                                        />

                                        {task.care_plan_id && (
                                            <Info
                                                label="Care Plan"
                                                value={`#${task.care_plan_id}`}
                                            />
                                        )}

                                        {task.occurrence_key && (
                                            <Info
                                                label="Occurrence"
                                                value={
                                                    task.occurrence_key
                                                }
                                            />
                                        )}
                                    </div>
                                </div>

                                {!residentActive &&
                                    !completed &&
                                    !cancelled && (
                                        <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                                            This resident is no longer
                                            active. The task is retained
                                            for history and cannot be
                                            actioned.
                                        </div>
                                    )}

                                {completed && (
                                    <div className="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <p className="text-sm font-bold text-emerald-900">
                                                    Completion
                                                    Evidence
                                                </p>

                                                <p className="mt-1 text-xs text-emerald-700">
                                                    This task is
                                                    completed and is
                                                    read-only.
                                                </p>
                                            </div>

                                            {careRecord?.id && (
                                                <span className="rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm">
                                                    Care Record #
                                                    {careRecord.id}
                                                </span>
                                            )}
                                        </div>

                                        <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <Info
                                                label="Completed By"
                                                value={completedByName(
                                                    task
                                                )}
                                            />

                                            <Info
                                                label="Completed At"
                                                value={formatDateTime(
                                                    task.completed_time
                                                )}
                                            />
                                        </div>

                                        <div className="mt-3 rounded-xl bg-white p-3">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Completion Notes
                                            </p>

                                            <p className="mt-1 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">
                                                {task.completion_notes ||
                                                    "No completion notes were recorded."}
                                            </p>
                                        </div>

                                        {careRecord && (
                                            <div className="mt-3 rounded-xl bg-white p-3">
                                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Linked Care
                                                    Record
                                                </p>

                                                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-700">
                                                    <span>
                                                        <span className="font-semibold">
                                                            #
                                                            {
                                                                careRecord.id
                                                            }
                                                        </span>
                                                    </span>

                                                    {careRecord.care_type && (
                                                        <span>
                                                            {
                                                                careRecord.care_type
                                                            }
                                                        </span>
                                                    )}

                                                    {careRecord.title && (
                                                        <span>
                                                            {
                                                                careRecord.title
                                                            }
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {cancelled && (
                                    <div className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                        This task has been cancelled
                                        and is retained as read-only
                                        history.
                                    </div>
                                )}

                                <div className="mt-5 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            task.resident_id &&
                                            navigate(
                                                `/residents/${task.resident_id}`
                                            )
                                        }
                                        className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        View Resident
                                    </button>

                                    {task.ai_generated && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                task.resident_id &&
                                                navigate(
                                                    `/residents/${task.resident_id}/clinical-dashboard`
                                                )
                                            }
                                            className="rounded-lg border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-100"
                                        >
                                            View Clinical
                                        </button>
                                    )}

                                    {residentActive &&
                                        !accepted &&
                                        !completed &&
                                        !cancelled && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleAccept(task)
                                                }
                                                disabled={
                                                    processingId ===
                                                    task.id
                                                }
                                                className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                                            >
                                                {processingId ===
                                                task.id
                                                    ? "Accepting..."
                                                    : "Accept Task"}
                                            </button>
                                        )}

                                    {residentActive &&
                                        accepted &&
                                        !completed &&
                                        !cancelled && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openCompletionModal(
                                                        task
                                                    )
                                                }
                                                disabled={
                                                    processingId ===
                                                    task.id
                                                }
                                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                            >
                                                Complete Task
                                            </button>
                                        )}
                                </div>
                            </article>
                        );
                    })}
                </div>
            </section>

            {completionTask && (
                <div className="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 sm:items-center sm:p-4">
                    <div className="w-full max-w-xl rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                        <div className="border-b border-slate-100 p-5">
                            <h2 className="text-xl font-bold text-slate-900">
                                Complete Task
                            </h2>

                            <p className="mt-1 text-sm text-slate-500">
                                {completionTask.task_name} ·{" "}
                                {completionTask.resident
                                    ?.full_name || "Resident"}
                            </p>
                        </div>

                        <form
                            onSubmit={handleComplete}
                            className="p-5"
                        >
                            <label className="block text-sm font-semibold text-slate-700">
                                Completion Notes
                                {completionTask.task_type ===
                                    "ROUTINE_CARE" && (
                                    <span className="ml-1 text-red-600">
                                        *
                                    </span>
                                )}
                            </label>

                            <textarea
                                value={completionNotes}
                                onChange={(event) => {
                                    setCompletionNotes(
                                        event.target.value
                                    );

                                    if (
                                        event.target.value.trim()
                                    ) {
                                        setCompletionNoteError("");
                                    }
                                }}
                                rows={4}
                                maxLength={2000}
                                autoFocus
                                required={
                                    completionTask.task_type ===
                                    "ROUTINE_CARE"
                                }
                                placeholder="What care was provided? Add any relevant observations..."
                                className={`mt-2 w-full rounded-xl border px-3 py-3 text-sm outline-none ${
                                    completionNoteError
                                        ? "border-red-300 focus:border-red-400"
                                        : "border-slate-200 focus:border-blue-400"
                                }`}
                            />

                            <div className="mt-1 flex items-center justify-between gap-3 text-xs">
                                <span
                                    className={
                                        completionNoteError
                                            ? "text-red-600"
                                            : "text-slate-400"
                                    }
                                >
                                    {completionNoteError ||
                                        (completionTask.task_type ===
                                        "ROUTINE_CARE"
                                            ? "Required for routine-care completion."
                                            : "Optional for this task.")}
                                </span>

                                <span className="shrink-0 text-slate-400">
                                    {completionNotes.length}/2000
                                </span>
                            </div>

                            {completionTask.task_type ===
                                "ROUTINE_CARE" && (
                                <p className="mt-3 rounded-xl bg-blue-50 p-3 text-xs leading-5 text-blue-700">
                                    Completing this routine-care
                                    task will automatically create
                                    the resident&apos;s Care Record
                                    and preserve the completion note
                                    as care evidence.
                                </p>
                            )}

                            <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    onClick={closeCompletionModal}
                                    disabled={
                                        processingId ===
                                        completionTask.id
                                    }
                                    className="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    disabled={
                                        processingId ===
                                        completionTask.id
                                    }
                                    className="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                >
                                    {processingId ===
                                    completionTask.id
                                        ? "Completing..."
                                        : "Confirm Completion"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-xl bg-slate-50 p-3">
            <p className="text-xs font-medium text-slate-400">
                {label}
            </p>

            <p className="mt-1 break-words font-semibold text-slate-700">
                {value || "—"}
            </p>
        </div>
    );
}

function Badge({ text, cls }) {
    return (
        <span
            className={`rounded-full px-2.5 py-1 text-xs font-bold ${cls}`}
        >
            {text}
        </span>
    );
}

function PriorityBadge({ priority }) {
    const value = String(priority || "NORMAL").toUpperCase();

    const classes = {
        CRITICAL: "bg-red-100 text-red-700",
        URGENT: "bg-orange-100 text-orange-700",
        HIGH: "bg-amber-100 text-amber-700",
        NORMAL: "bg-slate-100 text-slate-600",
        LOW: "bg-slate-100 text-slate-500",
    };

    return (
        <Badge
            text={value}
            cls={classes[value] || classes.NORMAL}
        />
    );
}

function StatusBadge({ status }) {
    const classes = {
        Pending: "bg-amber-100 text-amber-700",
        ACKNOWLEDGED: "bg-blue-100 text-blue-700",
        Completed: "bg-emerald-100 text-emerald-700",
        Cancelled: "bg-slate-200 text-slate-600",
    };

    return (
        <Badge
            text={
                status === "ACKNOWLEDGED"
                    ? "Accepted"
                    : status || "Unknown"
            }
            cls={
                classes[status] ||
                "bg-slate-100 text-slate-600"
            }
        />
    );
}

function formatDateTime(value) {
    if (!value) return "Not recorded";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString("en-MY", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

export default NurseTaskTable;
