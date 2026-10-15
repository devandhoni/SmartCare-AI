import React, { useCallback, useMemo, useState } from "react";
import { ActivityIndicator, ScrollView, Text, TextInput, TouchableOpacity, View } from "react-native";
import { useFocusEffect } from "@react-navigation/native";
import { createMonthlyCheck, displayDateTime, getMonitoringResidents, getMonthlyChecks, localDateTime, monitoringError, validDateTime, type MonitoringCheck, type MonitoringResident } from "../../api/monitoringApi";
import { styles } from "./MonitoringStyles";

const TYPES = ["FASTING", "BEFORE_MEAL", "AFTER_MEAL", "RANDOM"];
const MONTHS = ["January","February","March","April","May","June","July","August","September","October","November","December"];
const now = new Date();
export default function MonthlyGlucoseScreen() {
  const [month, setMonth] = useState(now.getMonth()+1);
  const [year, setYear] = useState(now.getFullYear());
  const [residents, setResidents] = useState<MonitoringResident[]>([]);
  const [checks, setChecks] = useState<MonitoringCheck[]>([]);
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState<MonitoringResident | null>(null);
  const [glucose, setGlucose] = useState("");
  const [type, setType] = useState("FASTING");
  const [notes, setNotes] = useState("");
  const [recordedAt, setRecordedAt] = useState(localDateTime());
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [r,c] = await Promise.all([getMonitoringResidents(), getMonthlyChecks(month,year)]);
      setResidents(r); setChecks(c); setError("");
    } catch(e) { setError(monitoringError(e)); }
    finally { setLoading(false); }
  },[month,year]);
  useFocusEffect(useCallback(()=>{void load();},[load]));
  const byResident = useMemo(()=>new Map(checks.map(c=>[c.resident_id,c])),[checks]);
  const visible = residents.filter(r=>(`${r.full_name} ${r.ic_number??""}`).toLowerCase().includes(search.toLowerCase().trim()));
  const completed = residents.filter(r=>byResident.has(r.id)).length;
  const open = (resident: MonitoringResident) => {setSelected(resident);setGlucose("");setType("FASTING");setNotes("");setRecordedAt(localDateTime());setError("");setSuccess("");};
  const submit = async () => {
    if (!selected || saving) return;
    const value = Number(glucose);
    if (!glucose.trim() || !Number.isFinite(value) || value<1 || value>30) {setError("Glucose must be between 1 and 30 mmol/L.");return;}
    if (!validDateTime(recordedAt)) {setError("Enter a valid date/time as YYYY-MM-DD HH:mm:ss.");return;}
    const date = new Date(recordedAt.replace(" ","T"));
    if (date.getMonth()+1 !== month || date.getFullYear() !== year) {setError("The recorded date must match the selected month and year.");return;}
    if (notes.length>2000) {setError("Notes cannot exceed 2000 characters.");return;}
    setSaving(true);setError("");
    try {await createMonthlyCheck(selected.id,{blood_glucose:value,glucose_measurement_type:type,glucose_notes:notes.trim()||null,recorded_at:recordedAt});setSelected(null);setSuccess("Monthly glucose check saved.");await load();}
    catch(e){setError(monitoringError(e));}
    finally{setSaving(false);}
  };
  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.heading}>Monthly Glucose Checks</Text>
    <Text style={styles.subtitle}>One check per active resident per calendar month.</Text>
    <View style={styles.card}><Text style={styles.cardTitle}>{MONTHS[month-1]} {year}</Text>
      <View style={styles.row}><TouchableOpacity style={styles.chip} onPress={()=>{if(month===1){setMonth(12);setYear(y=>y-1);}else setMonth(m=>m-1);setSelected(null);}}><Text style={styles.chipText}>← Previous</Text></TouchableOpacity><TouchableOpacity style={styles.chip} onPress={()=>{if(month===12){setMonth(1);setYear(y=>y+1);}else setMonth(m=>m+1);setSelected(null);}}><Text style={styles.chipText}>Next →</Text></TouchableOpacity></View>
      <Text style={styles.summary}>Active: {residents.length}  ·  Completed: {completed}  ·  Pending: {residents.length-completed}</Text>
    </View>
    {!!error && <Text style={styles.error}>{error}</Text>}{!!success && <Text style={styles.success}>{success}</Text>}
    {selected ? <View style={styles.card}>
      <Text style={styles.cardTitle}>Record for {selected.full_name}</Text>
      <Text style={styles.label}>Blood glucose (mmol/L) *</Text><TextInput style={styles.input} value={glucose} onChangeText={setGlucose} keyboardType="decimal-pad" placeholder="e.g. 5.6" />
      <Text style={styles.label}>Measurement type *</Text><View style={styles.row}>{TYPES.map(t=><TouchableOpacity key={t} style={[styles.chip,type===t&&styles.chipActive]} onPress={()=>setType(t)}><Text style={styles.chipText}>{t.replace(/_/g," ")}</Text></TouchableOpacity>)}</View>
      <Text style={styles.label}>Recorded at *</Text><TextInput style={styles.input} value={recordedAt} onChangeText={setRecordedAt} autoCapitalize="none" placeholder="YYYY-MM-DD HH:mm:ss" /><Text style={styles.muted}>Use local date and time, e.g. 2026-10-15 09:30:00</Text>
      <Text style={styles.label}>Notes</Text><TextInput style={[styles.input,{minHeight:90,textAlignVertical:"top"}]} multiline value={notes} onChangeText={setNotes}/>
      <TouchableOpacity style={styles.button} disabled={saving} onPress={()=>void submit()}><Text style={styles.buttonText}>{saving?"Saving...":"Save Glucose Check"}</Text></TouchableOpacity>
      <TouchableOpacity style={[styles.button,styles.secondary]} disabled={saving} onPress={()=>setSelected(null)}><Text style={[styles.buttonText,styles.secondaryText]}>Cancel</Text></TouchableOpacity>
    </View> : <>
      <TextInput style={[styles.input,{marginBottom:12}]} value={search} onChangeText={setSearch} placeholder="Search active residents" />
      {loading ? <ActivityIndicator size="large" color="#2563eb"/> : visible.length===0 ? <Text style={styles.muted}>No active residents found.</Text> : visible.map(r=>{
        const c=byResident.get(r.id);return <View key={r.id} style={styles.card}><Text style={styles.cardTitle}>{r.full_name}</Text><Text style={styles.muted}>{c?`Completed · ${c.blood_glucose} mmol/L · ${(c.glucose_measurement_type??"").replace(/_/g," ")}`:"Pending"}</Text>{c?<Text style={styles.muted}>{displayDateTime(c.recorded_at)}</Text>:<TouchableOpacity style={styles.button} onPress={()=>open(r)}><Text style={styles.buttonText}>Record Check</Text></TouchableOpacity>}</View>;
      })}
    </>}
  </ScrollView>;
}
