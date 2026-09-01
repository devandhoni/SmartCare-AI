import { useCallback, useEffect, useMemo, useState } from "react";
import { getNurseTasks } from "../api/nurseTaskApi";
import NurseTaskTable from "../components/NurseTaskTable";

function NurseDashboard() {
    const [tasks, setTasks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState("");
    const [lastUpdated, setLastUpdated] = useState(null);
    const [statusFilter, setStatusFilter] = useState("OPEN");
    const [sourceFilter, setSourceFilter] = useState("ALL");
    const [priorityFilter, setPriorityFilter] = useState("ALL");
    const [search, setSearch] = useState("");

    const loadTasks = useCallback(async (silent = false) => {
        try {
            silent ? setRefreshing(true) : setLoading(true);
            setError("");
            const response = await getNurseTasks();
            const taskData = response?.tasks ?? response?.data?.tasks ?? response?.data ?? response ?? [];
            setTasks(Array.isArray(taskData) ? taskData : []);
            setLastUpdated(new Date());
        } catch (err) {
            console.error("Nurse dashboard loading error:", err);
            setError(err?.response?.data?.message ?? err?.message ?? "Unable to load nurse tasks.");
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => { loadTasks(); }, [loadTasks]);

    const isOverdue = (task) => {
        if (!task?.scheduled_time || ["Completed", "Cancelled"].includes(task.status)) return false;
        const due = new Date(task.scheduled_time);
        return !Number.isNaN(due.getTime()) && due.getTime() < Date.now();
    };

    const counts = useMemo(() => ({
        pending: tasks.filter((t) => t.status === "Pending").length,
        accepted: tasks.filter((t) => t.status === "ACKNOWLEDGED").length,
        overdue: tasks.filter(isOverdue).length,
        completed: tasks.filter((t) => t.status === "Completed").length,
    }), [tasks]);

    const filteredTasks = useMemo(() => {
        const term = search.trim().toLowerCase();
        return tasks.filter((task) => {
            if (statusFilter === "OPEN" && !["Pending", "ACKNOWLEDGED"].includes(task.status)) return false;
            if (!["ALL", "OPEN"].includes(statusFilter) && task.status !== statusFilter) return false;

            const source = task.ai_generated ? "AI" :
                task.source_type === "CARE_PLAN" ? "CARE_PLAN" :
                task.task_type === "ROUTINE_CARE" ? "ROUTINE_CARE" : "GENERAL";

            if (sourceFilter !== "ALL" && source !== sourceFilter) return false;
            if (priorityFilter !== "ALL" && String(task.priority || "NORMAL").toUpperCase() !== priorityFilter) return false;
            if (!term) return true;

            return [task.task_name, task.description, task.resident?.full_name, task.assigned_user?.full_name]
                .filter(Boolean).some((v) => String(v).toLowerCase().includes(term));
        });
    }, [tasks, statusFilter, sourceFilter, priorityFilter, search]);

    if (loading) {
        return <div className="flex min-h-[420px] items-center justify-center">
            <div className="text-center">
                <div className="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-slate-700" />
                <p className="mt-4 text-sm font-medium text-slate-500">Loading nurse task queue...</p>
            </div>
        </div>;
    }

    return <div className="w-full min-w-0 space-y-6">
        <section className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p className="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">Daily Care Operations</p>
                <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-900">Nurse Dashboard</h1>
                <p className="mt-2 text-sm text-slate-500">Review and complete Care Plan, routine-care and AI clinical tasks.</p>
                {lastUpdated && <p className="mt-1 text-xs text-slate-400">Last updated {lastUpdated.toLocaleTimeString([], {hour:"numeric", minute:"2-digit"})}</p>}
            </div>
            <button type="button" onClick={() => loadTasks(true)} disabled={refreshing}
                className="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60 sm:w-auto">
                {refreshing ? "Refreshing..." : "Refresh Tasks"}
            </button>
        </section>

        {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

        <section className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <SummaryCard label="Pending" value={counts.pending} />
            <SummaryCard label="Accepted" value={counts.accepted} />
            <SummaryCard label="Overdue" value={counts.overdue} attention={counts.overdue > 0} />
            <SummaryCard label="Completed" value={counts.completed} />
        </section>

        <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <input value={search} onChange={(e)=>setSearch(e.target.value)} placeholder="Search resident or task..."
                    className="rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-400" />
                <select value={statusFilter} onChange={(e)=>setStatusFilter(e.target.value)} className="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="OPEN">Open Tasks</option><option value="Pending">Pending</option>
                    <option value="ACKNOWLEDGED">Accepted</option><option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option><option value="ALL">All Statuses</option>
                </select>
                <select value={sourceFilter} onChange={(e)=>setSourceFilter(e.target.value)} className="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="ALL">All Task Sources</option><option value="CARE_PLAN">Care Plan</option>
                    <option value="ROUTINE_CARE">Manual Routine Care</option><option value="AI">AI Clinical</option>
                    <option value="GENERAL">General</option>
                </select>
                <select value={priorityFilter} onChange={(e)=>setPriorityFilter(e.target.value)} className="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="ALL">All Priorities</option><option value="CRITICAL">Critical</option>
                    <option value="URGENT">Urgent</option><option value="HIGH">High</option>
                    <option value="NORMAL">Normal</option><option value="LOW">Low</option>
                </select>
            </div>
        </section>

        <NurseTaskTable tasks={filteredTasks} onRefresh={() => loadTasks(true)} />
    </div>;
}

function SummaryCard({label, value, attention=false}) {
    return <div className={`rounded-2xl border p-4 shadow-sm ${attention ? "border-red-200 bg-red-50" : "border-slate-200 bg-white"}`}>
        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
        <p className={`mt-2 text-3xl font-bold ${attention ? "text-red-700" : "text-slate-900"}`}>{value}</p>
    </div>;
}

export default NurseDashboard;