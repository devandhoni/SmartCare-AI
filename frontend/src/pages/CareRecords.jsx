import React, { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import api from "../services/api";

const TYPES = ["Personal Care","Hygiene","Mobility","Nutrition","Hydration","Toileting","Skin Care","Positioning","Activity","Sleep","Behaviour","Safety","General Observation","Other"];
const STATUSES = [
  ["COMPLETED","Completed"],
  ["OBSERVED","Observed"],
  ["NEEDS_ATTENTION","Needs Attention"],
];

export default function CareRecords() {
  const [residents,setResidents] = useState([]);
  const [residentId,setResidentId] = useState("");
  const [records,setRecords] = useState([]);
  const [loading,setLoading] = useState(true);
  const [recordsLoading,setRecordsLoading] = useState(false);
  const [saving,setSaving] = useState(false);
  const [error,setError] = useState("");
  const [success,setSuccess] = useState("");
  const [showForm,setShowForm] = useState(false);
  const [search,setSearch] = useState("");
  const [status,setStatus] = useState("ALL");
  const [period,setPeriod] = useState("TODAY");
  const [form,setForm] = useState({care_type:"Personal Care",title:"",notes:"",care_status:"COMPLETED",recorded_at:""});

  const message = (e,fallback) => e?.response?.data?.message || Object.values(e?.response?.data?.errors || {}).flat()[0] || fallback;

  useEffect(() => { loadResidents(); }, []);
  useEffect(() => { if (residentId) loadRecords(residentId); else setRecords([]); }, [residentId]);

  async function loadResidents() {
    setLoading(true); setError("");
    try {
      const {data} = await api.get("/residents");
      const list = data?.residents?.data ?? data?.residents ?? data?.data?.data ?? data?.data ?? data ?? [];
      setResidents((Array.isArray(list) ? list : []).filter(r => String(r.status || "").toLowerCase() === "active"));
    } catch(e) {
      console.error("Failed to load residents:",e);
      setError(message(e,"Unable to load active residents."));
    } finally { setLoading(false); }
  }

  async function loadRecords(id) {
    setRecordsLoading(true); setError("");
    try {
      const {data} = await api.get(`/residents/${id}/care-records`);
      setRecords(Array.isArray(data?.care_records) ? data.care_records : []);
    } catch(e) {
      console.error("Failed to load care records:",e);
      setRecords([]); setError(message(e,"Unable to load care records."));
    } finally { setRecordsLoading(false); }
  }

  const resident = residents.find(r => String(r.id) === String(residentId));

  const visible = useMemo(() => records.filter(r => {
    if (status !== "ALL" && r.care_status !== status) return false;
    const d = new Date(r.recorded_at);
    if (period === "TODAY") {
      const n = new Date();
      if (d.toDateString() !== n.toDateString()) return false;
    }
    if (period === "7D") {
      const start = new Date(); start.setHours(0,0,0,0); start.setDate(start.getDate()-6);
      if (d < start) return false;
    }
    const q = search.trim().toLowerCase();
    if (!q) return true;
    return [r.care_type,r.title,r.notes,r.recorder?.full_name].filter(Boolean).some(v => String(v).toLowerCase().includes(q));
  }), [records,status,period,search]);

  const counts = useMemo(() => ({
    total: visible.length,
    completed: visible.filter(r => r.care_status === "COMPLETED").length,
    observed: visible.filter(r => r.care_status === "OBSERVED").length,
    attention: visible.filter(r => r.care_status === "NEEDS_ATTENTION").length,
  }), [visible]);

  async function submit(e) {
    e.preventDefault();
    if (!residentId) return setError("Select an active resident first.");
    if (!form.title.trim()) return setError("Care record title is required.");
    setSaving(true); setError(""); setSuccess("");
    try {
      const payload = {...form,title:form.title.trim(),notes:form.notes.trim() || null};
      if (!payload.recorded_at) delete payload.recorded_at;
      await api.post(`/residents/${residentId}/care-records`,payload);
      setSuccess("Care record saved successfully.");
      setForm({care_type:"Personal Care",title:"",notes:"",care_status:"COMPLETED",recorded_at:""});
      setShowForm(false);
      await loadRecords(residentId);
    } catch(e) {
      console.error("Failed to save care record:",e);
      setError(message(e,"Unable to save care record."));
    } finally { setSaving(false); }
  }

  const fmt = value => {
    if (!value) return "-";
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? value : d.toLocaleString([], {year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"});
  };
  const badge = s => s === "COMPLETED" ? "bg-emerald-100 text-emerald-700" : s === "NEEDS_ATTENTION" ? "bg-red-100 text-red-700" : "bg-amber-100 text-amber-700";
  const label = s => STATUSES.find(x => x[0] === s)?.[1] || s || "-";

  return <div className="w-full min-w-0 max-w-full space-y-6">
    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">Daily Care</p>
          <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Care Records</h1>
          <p className="mt-2 text-sm text-slate-500">Record and review daily care for active residents.</p>
        </div>
        <div className="flex flex-col gap-2 sm:flex-row">
          <Link to="/today" className="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700">Back to Today</Link>
          <button disabled={!residentId} onClick={() => {setError("");setSuccess("");setShowForm(v=>!v);}} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:bg-slate-300">{showForm?"Close Form":"Record Care"}</button>
        </div>
      </div>
    </section>

    {error && <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}
    {success && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{success}</div>}

    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <label className="mb-2 block text-sm font-semibold text-slate-700">Active Resident</label>
      <div className="flex flex-col gap-3 lg:flex-row">
        <select value={residentId} disabled={loading} onChange={e=>{setResidentId(e.target.value);setShowForm(false);setSuccess("");}} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm">
          <option value="">{loading?"Loading active residents...":"Select resident"}</option>
          {residents.map(r=><option key={r.id} value={r.id}>{r.full_name || r.name || `Resident #${r.id}`}</option>)}
        </select>
        {resident && <Link to={`/residents/${resident.id}`} className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-center text-sm font-semibold text-blue-700">Open Resident Profile</Link>}
      </div>
      {!loading && residents.length===0 && <p className="mt-3 text-sm text-slate-500">No active residents are available.</p>}
    </section>

    {showForm && resident && <section className="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm sm:p-6">
      <h2 className="text-xl font-bold text-slate-900">Record Care</h2>
      <p className="mt-1 text-sm text-slate-500">{resident.full_name || resident.name}</p>
      <form onSubmit={submit} className="mt-5 space-y-4">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <Field label="Care Type"><select value={form.care_type} onChange={e=>setForm({...form,care_type:e.target.value})} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{TYPES.map(x=><option key={x}>{x}</option>)}</select></Field>
          <Field label="Status"><select value={form.care_status} onChange={e=>setForm({...form,care_status:e.target.value})} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{STATUSES.map(([v,l])=><option key={v} value={v}>{l}</option>)}</select></Field>
        </div>
        <Field label="Care Record Title"><input required maxLength={255} value={form.title} onChange={e=>setForm({...form,title:e.target.value})} placeholder="e.g. Morning personal care completed" className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"/></Field>
        <Field label="Notes"><textarea rows={4} value={form.notes} onChange={e=>setForm({...form,notes:e.target.value})} placeholder="Relevant care observations or notes..." className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 resize-y"/></Field>
        <Field label="Recorded Time"><input type="datetime-local" value={form.recorded_at} onChange={e=>setForm({...form,recorded_at:e.target.value})} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 md:max-w-md"/><p className="mt-1 text-xs text-slate-500">Leave blank to use current server time.</p></Field>
        <div className="flex flex-col-reverse gap-3 border-t pt-4 sm:flex-row sm:justify-end">
          <button type="button" onClick={()=>setShowForm(false)} className="rounded-xl border px-5 py-2.5 text-sm font-semibold">Cancel</button>
          <button disabled={saving} className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white disabled:bg-blue-300">{saving?"Saving...":"Save Care Record"}</button>
        </div>
      </form>
    </section>}

    {residentId && <>
      <section className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Summary label="Records" value={counts.total}/><Summary label="Completed" value={counts.completed}/><Summary label="Observed" value={counts.observed}/><Summary label="Needs Attention" value={counts.attention} attention/>
      </section>
      <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div className="mb-5 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
          <div><h2 className="text-xl font-bold text-slate-900">Care History</h2><p className="mt-1 text-sm text-slate-500">Review recorded care for {resident?.full_name || resident?.name}.</p></div>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <input type="search" value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search records..." className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"/>
            <select value={period} onChange={e=>setPeriod(e.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="TODAY">Today</option><option value="7D">Last 7 Days</option><option value="ALL">All History</option></select>
            <select value={status} onChange={e=>setStatus(e.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="ALL">All Statuses</option>{STATUSES.map(([v,l])=><option key={v} value={v}>{l}</option>)}</select>
          </div>
        </div>
        {recordsLoading ? <Empty text="Loading care records..."/> : visible.length===0 ? <Empty text={records.length===0?"No care has been recorded for this resident yet.":"No records match the selected filters."}/> : <>
          <div className="space-y-3 lg:hidden">{visible.map(r=><Card key={r.id} r={r} fmt={fmt} badge={badge} label={label}/>)}</div>
          <div className="hidden overflow-x-auto lg:block">
            <table className="w-full min-w-[850px]">
              <thead><tr className="border-b text-left text-xs uppercase tracking-wide text-slate-500"><th className="p-3">Recorded</th><th className="p-3">Care Type</th><th className="p-3">Record</th><th className="p-3">Status</th><th className="p-3">Recorded By</th></tr></thead>
              <tbody>{visible.map(r=><tr key={r.id} className="border-b align-top last:border-0"><td className="whitespace-nowrap p-3 text-sm text-slate-600">{fmt(r.recorded_at)}</td><td className="p-3 text-sm font-semibold">{r.care_type}</td><td className="max-w-md p-3"><p className="font-semibold">{r.title}</p>{r.notes&&<p className="mt-1 text-sm text-slate-500">{r.notes}</p>}</td><td className="p-3"><span className={`rounded-full px-2.5 py-1 text-xs font-bold ${badge(r.care_status)}`}>{label(r.care_status)}</span></td><td className="p-3 text-sm text-slate-600">{r.recorder?.full_name || "-"}</td></tr>)}</tbody>
            </table>
          </div>
        </>}
      </section>
    </>}
    {!residentId && !loading && residents.length>0 && <Empty text="Select an active resident above to record care and review care history."/>}
  </div>;
}

function Field({label,children}) { return <div><label className="mb-2 block text-sm font-semibold text-slate-700">{label}</label>{children}</div>; }
function Summary({label,value,attention}) { return <div className={`rounded-2xl border p-4 shadow-sm ${attention?"border-red-200 bg-red-50":"border-slate-200 bg-white"}`}><p className={`text-xs font-semibold uppercase ${attention?"text-red-600":"text-slate-500"}`}>{label}</p><p className={`mt-2 text-2xl font-bold ${attention?"text-red-700":"text-slate-900"}`}>{value}</p></div>; }
function Empty({text}) { return <div className="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">{text}</div>; }
function Card({r,fmt,badge,label}) { return <article className="rounded-xl border border-slate-200 bg-slate-50 p-4"><div className="flex items-start justify-between gap-3"><div><p className="text-xs font-semibold uppercase text-blue-600">{r.care_type}</p><h3 className="mt-1 font-bold text-slate-900">{r.title}</h3></div><span className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${badge(r.care_status)}`}>{label(r.care_status)}</span></div>{r.notes&&<p className="mt-3 whitespace-pre-wrap text-sm text-slate-600">{r.notes}</p>}<div className="mt-4 border-t pt-3 text-sm text-slate-600"><p><span className="text-slate-500">Recorded: </span>{fmt(r.recorded_at)}</p><p className="mt-1"><span className="text-slate-500">Staff: </span>{r.recorder?.full_name || "-"}</p></div></article>; }