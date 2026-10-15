import React, { useCallback, useMemo, useState } from "react";
import { ActivityIndicator, ScrollView, Text, TextInput, TouchableOpacity, View } from "react-native";
import { useFocusEffect } from "@react-navigation/native";
import { createWeeklyCheck, displayDateTime, getMonitoringResidents, getWeeklyChecks, localDateTime, monitoringError, validDateTime, type MonitoringCheck, type MonitoringResident } from "../../api/monitoringApi";
import { styles } from "./MonitoringStyles";

type Field = "blood_pressure_systolic" | "blood_pressure_diastolic" | "heart_rate" | "oxygen_level" | "temperature" | "weight" | "blood_glucose";
const FIELD_SPECS: {key:Field;label:string;min:number;max:number;integer:boolean;required:boolean}[] = [
  {key:"blood_pressure_systolic",label:"Systolic BP (mmHg)",min:50,max:250,integer:true,required:true},
  {key:"blood_pressure_diastolic",label:"Diastolic BP (mmHg)",min:30,max:150,integer:true,required:true},
  {key:"heart_rate",label:"Heart rate (bpm)",min:30,max:200,integer:true,required:true},
  {key:"oxygen_level",label:"Oxygen saturation (%)",min:50,max:100,integer:true,required:true},
  {key:"temperature",label:"Temperature (°C)",min:30,max:45,integer:false,required:true},
  {key:"weight",label:"Weight (kg)",min:1,max:300,integer:false,required:true},
  {key:"blood_glucose",label:"Blood glucose (mmol/L) — optional",min:1,max:30,integer:false,required:false},
];
const emptyFields = ():Record<Field,string> => ({blood_pressure_systolic:"",blood_pressure_diastolic:"",heart_rate:"",oxygen_level:"",temperature:"",weight:"",blood_glucose:""});
function weekBounds(date: Date) {
  const start=new Date(date.getFullYear(),date.getMonth(),date.getDate());
  start.setDate(start.getDate()-((start.getDay()+6)%7));
  const end=new Date(start);end.setDate(start.getDate()+7);
  return {start,end};
}
export default function WeeklyVitalsScreen() {
  const [residents,setResidents]=useState<MonitoringResident[]>([]);
  const [checks,setChecks]=useState<MonitoringCheck[]>([]);
  const [search,setSearch]=useState("");
  const [selected,setSelected]=useState<MonitoringResident|null>(null);
  const [fields,setFields]=useState<Record<Field,string>>(emptyFields());
  const [recordedAt,setRecordedAt]=useState(localDateTime());
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState("");
  const [success,setSuccess]=useState("");
  const bounds=useMemo(()=>weekBounds(new Date()),[]);
  const load=useCallback(async()=>{setLoading(true);try{const [r,c]=await Promise.all([getMonitoringResidents(),getWeeklyChecks()]);setResidents(r);setChecks(c);setError("");}catch(e){setError(monitoringError(e));}finally{setLoading(false);}},[]);
  useFocusEffect(useCallback(()=>{void load();},[load]));
  const thisWeek=useMemo(()=>checks.filter(c=>{const d=new Date(c.recorded_at.replace(" ","T"));return d>=bounds.start&&d<bounds.end;}),[checks,bounds]);
  const byResident=useMemo(()=>new Map(thisWeek.map(c=>[c.resident_id,c])),[thisWeek]);
  const visible=residents.filter(r=>(`${r.full_name} ${r.ic_number??""} ${r.room?.room_number??""}`).toLowerCase().includes(search.trim().toLowerCase()));
  const completed=residents.filter(r=>byResident.has(r.id)).length;
  const open=(r:MonitoringResident)=>{setSelected(r);setFields(emptyFields());setRecordedAt(localDateTime());setError("");setSuccess("");};
  const submit=async()=>{
    if(!selected||saving)return;
    const parsed={} as Record<Field,number|null>;
    for(const f of FIELD_SPECS){const raw=fields[f.key].trim();const value=Number(raw);
      if(!raw&&!f.required){parsed[f.key]=null;continue;}
      if(!raw||!Number.isFinite(value)||value<f.min||value>f.max||(f.integer&&!Number.isInteger(value))){setError(`${f.label}: enter ${f.integer?"a whole number":"a number"} from ${f.min} to ${f.max}.`);return;}
      parsed[f.key]=value;
    }
    if(!validDateTime(recordedAt)){setError("Enter a valid date/time as YYYY-MM-DD HH:mm:ss.");return;}
    const date=new Date(recordedAt.replace(" ","T"));
    if(date<bounds.start||date>=bounds.end){setError("Recorded date must fall within the current Monday–Sunday week.");return;}
    setSaving(true);setError("");
    try{await createWeeklyCheck(selected.id,{
      blood_pressure_systolic:parsed.blood_pressure_systolic!,blood_pressure_diastolic:parsed.blood_pressure_diastolic!,
      heart_rate:parsed.heart_rate!,oxygen_level:parsed.oxygen_level!,temperature:parsed.temperature!,weight:parsed.weight!,
      blood_glucose:parsed.blood_glucose,recorded_at:recordedAt
    });setSelected(null);setSuccess("Weekly vital check saved.");await load();}
    catch(e){setError(monitoringError(e));}finally{setSaving(false);}
  };
  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.heading}>Weekly Vital Checks</Text>
    <Text style={styles.subtitle}>Current week: {bounds.start.toLocaleDateString("en-MY")} – {new Date(bounds.end.getTime()-86400000).toLocaleDateString("en-MY")}</Text>
    <View style={styles.card}><Text style={styles.summary}>Completed: {completed}  ·  Still Due: {residents.length-completed}</Text><Text style={styles.muted}>{residents.length?Math.round(completed/residents.length*100):0}% of active residents checked</Text></View>
    {!!error&&<Text style={styles.error}>{error}</Text>}{!!success&&<Text style={styles.success}>{success}</Text>}
    {selected?<View style={styles.card}>
      <Text style={styles.cardTitle}>Record for {selected.full_name}</Text>
      {FIELD_SPECS.map(f=><View key={f.key}><Text style={styles.label}>{f.label}{f.required?" *":""}</Text><TextInput style={styles.input} keyboardType="decimal-pad" value={fields[f.key]} onChangeText={v=>setFields(p=>({...p,[f.key]:v}))} placeholder={`${f.min}–${f.max}`}/></View>)}
      <Text style={styles.label}>Recorded at *</Text><TextInput style={styles.input} value={recordedAt} onChangeText={setRecordedAt} autoCapitalize="none" placeholder="YYYY-MM-DD HH:mm:ss"/><Text style={styles.muted}>Local time; use YYYY-MM-DD HH:mm:ss</Text>
      <TouchableOpacity style={styles.button} disabled={saving} onPress={()=>void submit()}><Text style={styles.buttonText}>{saving?"Saving...":"Save Weekly Vitals"}</Text></TouchableOpacity>
      <TouchableOpacity style={[styles.button,styles.secondary]} disabled={saving} onPress={()=>setSelected(null)}><Text style={[styles.buttonText,styles.secondaryText]}>Cancel</Text></TouchableOpacity>
    </View>:<>
      <TextInput style={[styles.input,{marginBottom:12}]} value={search} onChangeText={setSearch} placeholder="Search active residents"/>
      {loading?<ActivityIndicator size="large" color="#2563eb"/>:visible.length===0?<Text style={styles.muted}>No active residents found.</Text>:visible.map(r=>{const c=byResident.get(r.id);return <View key={r.id} style={styles.card}><Text style={styles.cardTitle}>{r.full_name}</Text>{c?<><Text style={styles.success}>Completed</Text><Text style={styles.muted}>BP {c.blood_pressure_systolic}/{c.blood_pressure_diastolic} · Pulse {c.heart_rate} · SpO₂ {c.oxygen_level}%</Text><Text style={styles.muted}>Temperature {c.temperature}°C · Weight {c.weight} kg{c.blood_glucose!=null?` · Glucose ${c.blood_glucose} mmol/L`:""}</Text><Text style={styles.muted}>{displayDateTime(c.recorded_at)}</Text></>:<><Text style={styles.muted}>Pending</Text><TouchableOpacity style={styles.button} onPress={()=>open(r)}><Text style={styles.buttonText}>Record Weekly Check</Text></TouchableOpacity></>}</View>;})}
      <Text style={styles.cardTitle}>Completed This Week</Text>
      {thisWeek.length===0?<Text style={styles.muted}>No completed checks this week.</Text>:thisWeek.map(c=><View key={c.id} style={styles.card}><Text style={styles.cardTitle}>{c.resident?.full_name??`Resident #${c.resident_id}`}</Text><Text style={styles.muted}>BP {c.blood_pressure_systolic}/{c.blood_pressure_diastolic} · Pulse {c.heart_rate} · SpO₂ {c.oxygen_level}%</Text><Text style={styles.muted}>{displayDateTime(c.recorded_at)}</Text></View>)}
    </>}
  </ScrollView>;
}
