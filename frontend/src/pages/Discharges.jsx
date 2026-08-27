import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../services/api";

const EMPTY = {
  resident_id: "",
  discharged_at: "",
  discharge_type: "PLANNED_DISCHARGE",
  discharge_destination: "",
  reason_for_discharge: "",
  condition_at_discharge: "",
  treatment_care_summary: "",
  medical_summary: "",
  follow_up_instructions: "",
  medication_summary: "",
  medication_instructions: "",
  discharged_to: "",
  discharged_to_relationship: "",
  discharged_to_contact: "",
  belongings_returned: false,
  belongings_notes: "",
  administrative_notes: "",
};

const STEPS = [
  ["Resident", "Select resident"],
  ["Discharge", "Clinical summary"],
  ["Handover", "Medication & belongings"],
  ["Review", "Complete discharge"],
];

function Discharges() {
  const navigate = useNavigate();
  const [step, setStep] = useState(1);
  const [form, setForm] = useState({ ...EMPTY, discharged_at: localNow() });
  const [residents, setResidents] = useState([]);
  const [rooms, setRooms] = useState([]);
  const [medMaster, setMedMaster] = useState([]);
  const [contacts, setContacts] = useState([]);
  const [residentMeds, setResidentMeds] = useState([]);
  const [discharges, setDischarges] = useState([]);
  const [activeId, setActiveId] = useState(null);
  const [activeNumber, setActiveNumber] = useState("");
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [viewingDischarge, setViewingDischarge] = useState(null);
  const [loadingSummary, setLoadingSummary] = useState(false);

  useEffect(() => { loadAll(); }, []);

  const selectedResident = useMemo(
    () => residents.find(r => String(r.id) === String(form.resident_id)) || null,
    [residents, form.resident_id]
  );

  const drafts = discharges.filter(d => String(d.status).toUpperCase() === "DRAFT");
  const completed = discharges.filter(d => String(d.status).toUpperCase() === "COMPLETED");

  async function loadAll() {
    setLoading(true);
    try {
      const [r, d, rm, mm] = await Promise.all([
        api.get("/residents"),
        api.get("/discharges"),
        api.get("/rooms").catch(() => ({ data: [] })),
        api.get("/medications").catch(() => ({ data: [] })),
      ]);
      setResidents(Array.isArray(r.data?.residents ?? r.data) ? (r.data?.residents ?? r.data) : []);
      setDischarges(Array.isArray(d.data?.discharges ?? d.data) ? (d.data?.discharges ?? d.data) : []);
      setRooms(Array.isArray(rm.data?.rooms ?? rm.data) ? (rm.data?.rooms ?? rm.data) : []);
      setMedMaster(Array.isArray(mm.data?.medications ?? mm.data?.data ?? mm.data) ? (mm.data?.medications ?? mm.data?.data ?? mm.data) : []);
    } catch (e) {
      setError(apiError(e, "Unable to load discharge data."));
    } finally {
      setLoading(false);
    }
  }

  async function refreshLists() {
    const [r, d] = await Promise.all([api.get("/residents"), api.get("/discharges")]);
    setResidents(Array.isArray(r.data?.residents ?? r.data) ? (r.data?.residents ?? r.data) : []);
    setDischarges(Array.isArray(d.data?.discharges ?? d.data) ? (d.data?.discharges ?? d.data) : []);
  }

  function change(e) {
    const { name, value, type, checked } = e.target;
    setForm(f => ({ ...f, [name]: type === "checkbox" ? checked : value }));
    setError("");
  }

  async function chooseResident(id, preserve = false) {
    setForm(f => ({ ...f, resident_id: id }));
    setContacts([]);
    setResidentMeds([]);
    if (!id) return;

    try {
      const [c, m] = await Promise.all([
        api.get(`/residents/${id}/contacts`).catch(() => ({ data: { contacts: [] } })),
        api.get(`/residents/${id}/medications`).catch(() => ({ data: { medications: [] } })),
      ]);

      const cs = Array.isArray(c.data?.contacts) ? c.data.contacts : [];
      const ms = Array.isArray(m.data?.medications) ? m.data.medications : [];
      setContacts(cs);
      setResidentMeds(ms);

      if (!preserve) {
        const resident = residents.find(x => String(x.id) === String(id));
        const primary = cs.find(x => x.is_primary) || cs[0];
        setForm(f => ({
          ...f,
          medical_summary: f.medical_summary || residentMedicalSummary(resident),
          medication_summary: f.medication_summary || medicationSummary(ms, medMaster),
          discharged_to: f.discharged_to || primary?.full_name || resident?.emergency_contact || "",
          discharged_to_relationship: f.discharged_to_relationship || primary?.relationship || resident?.emergency_relationship || "",
          discharged_to_contact: f.discharged_to_contact || primary?.phone || resident?.emergency_phone || "",
        }));
      }
    } catch (e) {
      setError(apiError(e, "Unable to load resident details."));
    }
  }

  function validate(n) {
    if (n === 1 && !form.resident_id) return "Please select an active resident.";
    if (n === 2 && !form.discharged_at) return "Discharge date and time is required.";
    if (n === 2 && !form.reason_for_discharge.trim()) return "Reason for discharge is required.";
    if (n === 2 && !form.condition_at_discharge.trim()) return "Condition at discharge is required.";
    return "";
  }

  function next() {
    const msg = validate(step);
    if (msg) return setError(msg);
    setStep(s => Math.min(4, s + 1));
    setError("");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  async function saveDraft() {
    if (!form.resident_id) {
      setStep(1);
      return setError("Select a resident before saving a discharge draft.");
    }
    setBusy(true); setError(""); setSuccess("");
    try {
      let res;
      if (activeId) {
        res = await api.put(`/discharges/${activeId}`, payload(form));
        setSuccess("Discharge draft updated successfully.");
      } else {
        res = await api.post("/discharges", { resident_id: Number(form.resident_id), ...payload(form) });
        setActiveId(res.data?.discharge?.id || null);
        setActiveNumber(res.data?.discharge?.discharge_number || "");
        setSuccess("Discharge draft saved successfully.");
      }
      await refreshLists();
    } catch (e) {
      setError(apiError(e, "Unable to save discharge draft."));
    } finally { setBusy(false); }
  }

  async function resume(draft) {
    setBusy(true); setError(""); setSuccess("");
    try {
      const res = await api.get(`/discharges/${draft.id}`);
      const d = res.data?.discharge ?? res.data;
      const restored = {
        ...EMPTY,
        resident_id: String(d.resident_id || ""),
        discharged_at: d.discharged_at ? toLocalInput(d.discharged_at) : localNow(),
        discharge_type: d.discharge_type || "PLANNED_DISCHARGE",
        discharge_destination: d.discharge_destination || "",
        reason_for_discharge: d.reason_for_discharge || "",
        condition_at_discharge: d.condition_at_discharge || "",
        treatment_care_summary: d.treatment_care_summary || "",
        medical_summary: d.medical_summary || "",
        follow_up_instructions: d.follow_up_instructions || "",
        medication_summary: d.medication_summary || "",
        medication_instructions: d.medication_instructions || "",
        discharged_to: d.discharged_to || "",
        discharged_to_relationship: d.discharged_to_relationship || "",
        discharged_to_contact: d.discharged_to_contact || "",
        belongings_returned: Boolean(d.belongings_returned),
        belongings_notes: d.belongings_notes || "",
        administrative_notes: d.administrative_notes || "",
      };
      setForm(restored);
      setActiveId(d.id);
      setActiveNumber(d.discharge_number || "");
      await chooseResident(restored.resident_id, true);
      setStep(2);
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (e) {
      setError(apiError(e, "Unable to resume discharge draft."));
    } finally { setBusy(false); }
  }

  async function discard(draft) {
    if (!window.confirm(`Discard discharge draft for ${draft.resident?.full_name || "this resident"}?`)) return;
    try {
      await api.delete(`/discharges/${draft.id}`);
      if (String(activeId) === String(draft.id)) reset();
      setSuccess("Discharge draft discarded.");
      await refreshLists();
    } catch (e) { setError(apiError(e, "Unable to discard discharge draft.")); }
  }


  async function viewCompletedDischarge(discharge) {
    try {
      setLoadingSummary(true);
      setError("");

      const response = await api.get(
        `/discharges/${discharge.id}`
      );

      setViewingDischarge(
        response.data?.discharge ??
        response.data
      );
    } catch (e) {
      setError(
        apiError(
          e,
          "Unable to load discharge summary."
        )
      );
    } finally {
      setLoadingSummary(false);
    }
  }

  async function complete() {
    for (let n = 1; n <= 2; n++) {
      const msg = validate(n);
      if (msg) { setStep(n); return setError(msg); }
    }
    setBusy(true); setError(""); setSuccess("");
    try {
      let id = activeId;
      if (id) {
        await api.put(`/discharges/${id}`, payload(form));
      } else {
        const res = await api.post("/discharges", { resident_id: Number(form.resident_id), ...payload(form) });
        id = res.data?.discharge?.id;
      }
      if (!id) throw new Error("No discharge ID was returned.");
      const res = await api.post(`/discharges/${id}/complete`);
      const name = res.data?.discharge?.resident?.full_name || selectedResident?.full_name || "Resident";
      setSuccess(`${name} has been discharged successfully.`);
      reset();
      await refreshLists();
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (e) {
      setError(apiError(e, "Unable to complete the discharge."));
    } finally { setBusy(false); }
  }

  function reset() {
    setActiveId(null);
    setActiveNumber("");
    setContacts([]);
    setResidentMeds([]);
    setForm({ ...EMPTY, discharged_at: localNow() });
    setStep(1);
  }

  if (loading) return <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">Loading discharges...</div>;

  return (
    <div className="w-full min-w-0 space-y-6">
      <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 lg:p-8">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Resident Management</p>
            <h1 className="mt-2 text-3xl font-bold text-slate-800">Discharges</h1>
            <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Prepare a discharge summary, save it as a draft, resume later, and only change the resident to Discharged when completion is confirmed.</p>
          </div>
          <div className="rounded-xl bg-blue-50 px-4 py-3">
            <p className="text-xs font-semibold uppercase text-blue-600">Current Step</p>
            <p className="mt-1 font-bold text-slate-800">{step} of 4 · {STEPS[step - 1][0]}</p>
            {activeNumber && <p className="mt-1 text-xs text-slate-500">{activeNumber}</p>}
          </div>
        </div>
        <div className="mt-6 h-2 overflow-hidden rounded-full bg-slate-100">
          <div className="h-full rounded-full bg-blue-600" style={{ width: `${step * 25}%` }} />
        </div>
      </section>

      {error && <Alert error>{error}</Alert>}
      {success && <Alert>{success}</Alert>}

      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <Header title="In Progress Discharges" subtitle="Drafts do not change the resident's Active status." count={drafts.length} />
        {drafts.length === 0 ? <Empty text="No discharge drafts in progress." /> : drafts.map(d => (
          <Row key={d.id} title={d.resident?.full_name || "Resident"} subtitle={`${d.discharge_number} · Updated ${formatDateTime(d.updated_at)}`}>
            <button onClick={() => resume(d)} className="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Resume</button>
            <button onClick={() => discard(d)} className="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Discard</button>
          </Row>
        ))}
      </section>

      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="grid grid-cols-2 gap-2 border-b border-slate-200 bg-slate-50 p-3 lg:grid-cols-4">
          {STEPS.map((s, i) => {
            const n = i + 1, active = n === step, done = n < step;
            return <button key={n} type="button" onClick={() => n <= step && setStep(n)} className={`rounded-xl px-3 py-3 text-left ${active ? "bg-blue-600 text-white" : done ? "bg-blue-50 text-blue-700" : "bg-white text-slate-400"}`}>
              <span className="font-bold">{done ? "✓" : n} · {s[0]}</span>
              <span className="mt-1 block text-xs opacity-80">{s[1]}</span>
            </button>;
          })}
        </div>

        <div className="p-4 sm:p-6 lg:p-8">
          {step === 1 && <ResidentStep residents={residents} rooms={rooms} selected={selectedResident} value={form.resident_id} onSelect={chooseResident} />}
          {step === 2 && <DischargeStep form={form} change={change} />}
          {step === 3 && <HandoverStep form={form} change={change} contacts={contacts} meds={residentMeds} medMaster={medMaster} />}
          {step === 4 && <Review form={form} resident={selectedResident} rooms={rooms} setStep={setStep} />}

          <div className="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <button type="button" onClick={() => setStep(s => Math.max(1, s - 1))} disabled={step === 1 || busy} className="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 disabled:opacity-40">← Back</button>
            <div className="flex flex-col gap-3 sm:flex-row">
              <button type="button" onClick={saveDraft} disabled={busy} className="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 font-semibold text-blue-700">{busy ? "Saving..." : activeId ? "Save Draft Changes" : "Save Draft"}</button>
              {step < 4
                ? <button type="button" onClick={next} disabled={busy} className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white">Continue →</button>
                : <button type="button" onClick={complete} disabled={busy} className="rounded-xl bg-red-600 px-6 py-3 font-semibold text-white">{busy ? "Completing..." : "Complete Discharge"}</button>}
            </div>
          </div>
        </div>
      </section>

      <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <Header title="Completed Discharges" subtitle="Completed resident discharge summaries." count={completed.length} />
                  {completed.length === 0 ? (
            <Empty text="No completed discharges yet." />
          ) : (
            completed.slice(0, 10).map((d) => (
              <Row
                key={d.id}
                title={d.resident?.full_name || "Resident"}
                subtitle={`${d.discharge_number} · ${formatDateTime(d.discharged_at)}`}
              >
                <button
                  type="button"
                  onClick={() => viewCompletedDischarge(d)}
                  disabled={loadingSummary}
                  className="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-50"
                >
                  View Summary
                </button>

                {d.resident?.id && (
                  <button
                    type="button"
                    onClick={() =>
                      navigate(`/residents/${d.resident.id}`)
                    }
                    className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                  >
                    Open Resident
                  </button>
                )}
              </Row>
            ))
          )}
      </section>
      {viewingDischarge && (
        <DischargeSummaryModal
          discharge={viewingDischarge}
          onClose={() =>
            setViewingDischarge(null)
          }
        />
      )}
    </div>
  );
}


function DischargeSummaryModal({
  discharge,
  onClose,
}) {
  const resident =
    discharge?.resident || {};

  return (
    <div
      id="discharge-summary-overlay"
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
    >
      <div
        id="discharge-summary-print"
        className="max-h-[94vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
      >

        <div className="sticky top-0 z-10 flex flex-col gap-4 border-b border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">

          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">
              SmartCare-AI
            </p>

            <h2 className="mt-1 text-2xl font-bold text-slate-800">
              Resident Discharge Summary
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {discharge.discharge_number}
            </p>
          </div>

          <div className="print-hidden flex gap-2">

            <button
              type="button"
              onClick={() => window.print()}
              className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
            >
              Print Summary
            </button>

            <button
              type="button"
              onClick={onClose}
              className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              Close
            </button>

          </div>

        </div>


        <div className="space-y-6 p-5 sm:p-7">


          {/* Resident */}

          <SummarySection title="Resident Information">

            <SummaryGrid>

              <SummaryItem
                label="Resident Name"
                value={resident.full_name}
              />

              <SummaryItem
                label="IC / Passport"
                value={resident.ic_number}
              />

              <SummaryItem
                label="Admission Date"
                value={formatDate(
                  resident.admission_date
                )}
              />

              <SummaryItem
                label="Discharge Date"
                value={formatDateTime(
                  discharge.discharged_at
                )}
              />

              <SummaryItem
                label="Discharge Reference"
                value={discharge.discharge_number}
              />

              <SummaryItem
                label="Status"
                value={discharge.status}
              />

            </SummaryGrid>

          </SummarySection>


          {/* Discharge */}

          <SummarySection title="Discharge Details">

            <SummaryGrid>

              <SummaryItem
                label="Discharge Type"
                value={pretty(
                  discharge.discharge_type
                )}
              />

              <SummaryItem
                label="Destination"
                value={discharge.discharge_destination}
              />

              <SummaryItem
                label="Condition at Discharge"
                value={discharge.condition_at_discharge}
              />

              <SummaryItem
                label="Discharged To"
                value={discharge.discharged_to}
              />

              <SummaryItem
                label="Relationship"
                value={discharge.discharged_to_relationship}
              />

              <SummaryItem
                label="Contact"
                value={discharge.discharged_to_contact}
              />

            </SummaryGrid>


            <SummaryText
              label="Reason for Discharge"
              value={discharge.reason_for_discharge}
            />

          </SummarySection>


          {/* Clinical */}

          <SummarySection title="Clinical & Care Summary">

            <SummaryText
              label="Treatment / Care Summary"
              value={discharge.treatment_care_summary}
            />

            <SummaryText
              label="Medical Summary"
              value={discharge.medical_summary}
            />

            <SummaryText
              label="Follow-Up Instructions"
              value={discharge.follow_up_instructions}
            />

          </SummarySection>


          {/* Medication */}

          <SummarySection title="Medication Handover">

            <SummaryText
              label="Medication Summary"
              value={discharge.medication_summary}
            />

            <SummaryText
              label="Medication Instructions"
              value={discharge.medication_instructions}
            />

          </SummarySection>


          {/* Belongings */}

          <SummarySection title="Belongings & Administration">

            <SummaryGrid>

              <SummaryItem
                label="Belongings Returned"
                value={
                  discharge.belongings_returned
                    ? "Yes"
                    : "No"
                }
              />

              <SummaryItem
                label="Completed At"
                value={formatDateTime(
                  discharge.completed_at
                )}
              />

            </SummaryGrid>

            <SummaryText
              label="Belongings Notes"
              value={discharge.belongings_notes}
            />

            <SummaryText
              label="Administrative Notes"
              value={discharge.administrative_notes}
            />

          </SummarySection>


          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs leading-5 text-slate-500">
            This discharge summary is generated from the structured SmartCare-AI discharge record.
          </div>

        </div>

      </div>

    </div>
  );
}



function SummarySection({
  title,
  children,
}) {
  return (
    <section className="rounded-2xl border border-slate-200 p-5">
      <h3 className="text-lg font-bold text-slate-800">
        {title}
      </h3>

      <div className="mt-4 space-y-4">
        {children}
      </div>
    </section>
  );
}


function SummaryGrid({
  children,
}) {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      {children}
    </div>
  );
}


function SummaryItem({
  label,
  value,
}) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
        {label}
      </p>

      <p className="mt-1 break-words font-medium text-slate-700">
        {value || "—"}
      </p>
    </div>
  );
}


function SummaryText({
  label,
  value,
}) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
        {label}
      </p>

      <p className="mt-2 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
        {value || "—"}
      </p>
    </div>
  );
}


function ResidentStep({ residents, rooms, selected, value, onSelect }) {
  return <StepCard title="Select Resident" desc="Choose an active resident. Saving a draft will not discharge them.">
    <Select label="Active Resident" value={value} onChange={e => onSelect(e.target.value)}
      options={[["", "Select resident"], ...residents.map(r => [String(r.id), `${r.full_name}${r.ic_number ? ` · ${r.ic_number}` : ""}`])]} />
    {selected && <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Info label="Resident" value={selected.full_name} />
      <Info label="Admission Date" value={formatDate(selected.admission_date)} />
      <Info label="Room" value={roomLabel(selected.room_id, rooms)} />
      <Info label="Medical Condition" value={selected.medical_condition || "—"} />
      <Info label="Allergies" value={selected.allergies || "—"} />
      <Info label="Emergency Contact" value={[selected.emergency_contact, selected.emergency_relationship].filter(Boolean).join(" · ") || "—"} />
    </div>}
  </StepCard>;
}

function DischargeStep({ form, change }) {
  return <StepCard title="Discharge & Clinical Summary" desc="Record when and why the resident is leaving and summarize their condition and follow-up needs.">
    <div className="grid gap-5 md:grid-cols-2">
      <Field label="Discharge Date & Time *" name="discharged_at" type="datetime-local" value={form.discharged_at} onChange={change} />
      <Select label="Discharge Type" name="discharge_type" value={form.discharge_type} onChange={change}
        options={[["PLANNED_DISCHARGE","Planned Discharge"],["HOSPITAL_TRANSFER","Hospital Transfer"],["CARE_FACILITY_TRANSFER","Transfer to Another Care Facility"],["FAMILY_REQUEST","Family Request"],["RESIDENT_REQUEST","Resident Request"],["OTHER","Other"]]} />
      <Field label="Discharge Destination" name="discharge_destination" value={form.discharge_destination} onChange={change} placeholder="Home, hospital, another facility..." />
      <Area label="Reason for Discharge *" name="reason_for_discharge" value={form.reason_for_discharge} onChange={change} />
      <Area label="Condition at Discharge *" name="condition_at_discharge" value={form.condition_at_discharge} onChange={change} />
      <Area label="Treatment / Care Summary" name="treatment_care_summary" value={form.treatment_care_summary} onChange={change} />
      <Area label="Medical Summary" name="medical_summary" value={form.medical_summary} onChange={change} />
      <Area label="Follow-Up Instructions" name="follow_up_instructions" value={form.follow_up_instructions} onChange={change} />
    </div>
  </StepCard>;
}

function HandoverStep({ form, change, contacts, meds, medMaster }) {
  return <StepCard title="Medication, Handover & Belongings" desc="Confirm ongoing medication information, receiving person and personal belongings.">
    <h3 className="font-bold text-slate-800">Current Medication</h3>
    {meds.length === 0 ? <Empty text="No resident medications found." /> : <div className="grid gap-3 md:grid-cols-2">
      {meds.map(m => <MedicationCard key={m.id} item={m} medMaster={medMaster} />)}
    </div>}
    <div className="grid gap-5 md:grid-cols-2">
      <Area label="Medication Summary" name="medication_summary" value={form.medication_summary} onChange={change} />
      <Area label="Medication Instructions" name="medication_instructions" value={form.medication_instructions} onChange={change} />
      <Field label="Discharged To" name="discharged_to" value={form.discharged_to} onChange={change} />
      <Field label="Relationship" name="discharged_to_relationship" value={form.discharged_to_relationship} onChange={change} />
      <Field label="Contact Number" name="discharged_to_contact" value={form.discharged_to_contact} onChange={change} />
    </div>
    {contacts.length > 0 && <p className="text-sm text-slate-500">Available contacts: {contacts.map(c => `${c.full_name}${c.relationship ? ` (${c.relationship})` : ""}`).join(", ")}</p>}
    <Toggle name="belongings_returned" checked={form.belongings_returned} onChange={change} title="Belongings Returned" />
    <div className="grid gap-5 md:grid-cols-2">
      <Area label="Belongings Notes" name="belongings_notes" value={form.belongings_notes} onChange={change} />
      <Area label="Administrative Notes" name="administrative_notes" value={form.administrative_notes} onChange={change} />
    </div>
  </StepCard>;
}

function Review({ form, resident, rooms, setStep }) {
  return <StepCard title="Review & Complete Discharge" desc="Completing this record changes the resident from Active to Discharged and releases the current room.">
    <div className="grid gap-4 lg:grid-cols-2">
      <ReviewCard title="Resident" edit={() => setStep(1)} rows={[["Name", resident?.full_name],["Admission Date", formatDate(resident?.admission_date)],["Room", roomLabel(resident?.room_id, rooms)]]} />
      <ReviewCard title="Discharge" edit={() => setStep(2)} rows={[["Date", formatDateTime(form.discharged_at)],["Type", pretty(form.discharge_type)],["Destination", form.discharge_destination],["Reason", form.reason_for_discharge],["Condition", form.condition_at_discharge]]} />
      <ReviewCard title="Follow-Up" edit={() => setStep(2)} rows={[["Care Summary", form.treatment_care_summary],["Medical Summary", form.medical_summary],["Follow-Up", form.follow_up_instructions]]} />
      <ReviewCard title="Handover" edit={() => setStep(3)} rows={[["Discharged To", form.discharged_to],["Relationship", form.discharged_to_relationship],["Contact", form.discharged_to_contact],["Belongings", form.belongings_returned ? "Returned" : "Not confirmed"]]} />
    </div>
    <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">Complete Discharge will save the discharge summary, set the resident to Discharged, store the discharge date, release the current room and create a discharge timeline event.</div>
  </StepCard>;
}

const StepCard = ({ title, desc, children }) => <div className="space-y-6"><div><h2 className="text-2xl font-bold text-slate-800">{title}</h2><p className="mt-2 text-sm leading-6 text-slate-500">{desc}</p></div>{children}</div>;
const Field = ({ label, ...p }) => <label className="block"><span className="mb-2 block text-sm font-semibold text-slate-700">{label}</span><input {...p} className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" /></label>;
const Area = ({ label, ...p }) => <label className="block"><span className="mb-2 block text-sm font-semibold text-slate-700">{label}</span><textarea rows="4" {...p} className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" /></label>;
const Select = ({ label, options, ...p }) => <label className="block"><span className="mb-2 block text-sm font-semibold text-slate-700">{label}</span><select {...p} className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">{options.map(([v,t]) => <option key={`${v}-${t}`} value={v}>{t}</option>)}</select></label>;
const Toggle = ({ title, ...p }) => <label className="flex items-center gap-3 rounded-xl border border-slate-200 p-4"><input type="checkbox" {...p} /><span className="font-semibold text-slate-700">{title}</span></label>;
const Info = ({ label, value }) => <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-xs font-semibold uppercase text-slate-400">{label}</p><p className="mt-2 font-bold text-slate-700">{value}</p></div>;
const Alert = ({ error, children }) => <div className={`rounded-2xl border px-5 py-4 text-sm font-medium ${error ? "border-red-200 bg-red-50 text-red-700" : "border-emerald-200 bg-emerald-50 text-emerald-700"}`}>{children}</div>;
const Empty = ({ text }) => <div className="p-6 text-sm text-slate-500">{text}</div>;
const Header = ({ title, subtitle, count }) => <div className="flex items-center justify-between border-b border-slate-200 p-5 sm:p-6"><div><h2 className="text-lg font-bold text-slate-800">{title}</h2><p className="mt-1 text-sm text-slate-500">{subtitle}</p></div><span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{count}</span></div>;
const Row = ({ title, subtitle, children }) => <div className="flex flex-col gap-4 border-b border-slate-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between"><div><h3 className="font-bold text-slate-800">{title}</h3><p className="mt-1 text-sm text-slate-500">{subtitle}</p></div><div className="flex gap-2">{children}</div></div>;

function ReviewCard({ title, rows, edit }) {
  return <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5"><div className="flex justify-between"><h3 className="font-bold text-slate-800">{title}</h3><button onClick={edit} className="text-sm font-semibold text-blue-600">Edit</button></div><dl className="mt-4 space-y-3">{rows.map(([k,v]) => <div key={k} className="grid grid-cols-[110px_1fr] gap-3 text-sm"><dt className="text-slate-400">{k}</dt><dd className="break-words font-medium text-slate-700">{v || "—"}</dd></div>)}</dl></div>;
}

function MedicationCard({ item, medMaster }) {
  const med = item.medication || medMaster.find(x => String(x.id) === String(item.medication_id));
  return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="font-bold text-slate-800">{med ? [med.medicine_name, med.dosage, med.unit].filter(Boolean).join(" · ") : `Medication #${item.medication_id}`}</p><p className="mt-2 text-xs leading-5 text-slate-500">{[item.frequency, item.time_slot, item.dosage_instruction].filter(Boolean).join(" · ") || "No instructions"}</p></div>;
}

function payload(f) {
  const p = {};
  Object.keys(EMPTY).forEach(k => {
    if (k === "resident_id") return;
    p[k] = k === "belongings_returned" ? Boolean(f[k]) : emptyToNull(f[k]);
  });
  return p;
}

function residentMedicalSummary(r) {
  if (!r) return "";
  return [
    r.medical_condition ? `Medical condition: ${r.medical_condition}` : null,
    r.chronic_disease ? `Chronic disease: ${r.chronic_disease}` : null,
    r.allergies ? `Allergies: ${r.allergies}` : null,
    r.medical_notes ? `Medical notes: ${r.medical_notes}` : null,
  ].filter(Boolean).join("\n");
}

function medicationSummary(ms, master) {
  return ms.map(m => {
    const med = m.medication || master.find(x => String(x.id) === String(m.medication_id));
    return [
      med ? [med.medicine_name, med.dosage, med.unit].filter(Boolean).join(" ") : `Medication #${m.medication_id}`,
      m.dosage_quantity ? `Qty ${m.dosage_quantity}` : null,
      m.frequency, m.time_slot, m.dosage_instruction
    ].filter(Boolean).join(" · ");
  }).join("\n");
}

function roomLabel(id, rooms) {
  if (!id) return "Not assigned";
  const r = rooms.find(x => String(x.id) === String(id));
  return r ? `Room ${r.room_number}` : `Room #${id}`;
}

function emptyToNull(v) { if (v === null || v === undefined) return null; const s = String(v).trim(); return s === "" ? null : s; }
function localNow() { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0,16); }
function toLocalInput(v) { const d = new Date(v); return Number.isNaN(d.getTime()) ? "" : new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0,16); }
function pretty(v) { return String(v || "").replaceAll("_"," ").toLowerCase().replace(/\b\w/g, c => c.toUpperCase()); }
function formatDate(v) { if (!v) return "—"; const d = new Date(`${String(v).slice(0,10)}T00:00:00`); return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleDateString("en-MY",{day:"2-digit",month:"short",year:"numeric"}); }
function formatDateTime(v) { if (!v) return "—"; const d = new Date(v); return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleString("en-MY",{day:"2-digit",month:"short",year:"numeric",hour:"2-digit",minute:"2-digit"}); }
function apiError(e, fallback) { const data = e?.response?.data; const first = data?.errors ? Object.values(data.errors).flat().find(Boolean) : null; return first || data?.message || e?.message || fallback; }

export default Discharges;