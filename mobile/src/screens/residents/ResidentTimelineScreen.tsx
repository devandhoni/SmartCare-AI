import React, {useCallback, useEffect, useState} from "react";
import {ActivityIndicator, RefreshControl, ScrollView, StyleSheet, Text, View} from "react-native";
import {useRoute} from "@react-navigation/native";
import {getResidentTimeline, TimelineResponse} from "../../api/residentHistoryApi";
const display = (v: unknown) => v === null || v === undefined || v === "" ? "—" : String(v);
export default function ResidentTimelineScreen() {
 const route = useRoute<any>(); const id = Number(route.params?.residentId);
 const [data,setData] = useState<TimelineResponse | null>(null); const [busy,setBusy]=useState(true); const [error,setError]=useState("");
 const load=useCallback(async()=>{setBusy(true);setError("");try{setData(await getResidentTimeline(id));}catch(e:any){setError(e?.response?.data?.message || "Unable to load clinical timeline.");}finally{setBusy(false);}},[id]);
 useEffect(()=>{load();},[load]);
 return <ScrollView style={s.page} contentContainerStyle={s.content} refreshControl={<RefreshControl refreshing={busy} onRefresh={load}/> }>
 <Text style={s.heading}>Clinical Timeline</Text><Text style={s.hint}>Recorded events from the resident's clinical history.</Text>
 {error ? <Text style={s.error}>{error}</Text> : null}
 {busy && !data ? <ActivityIndicator/> : null}
 {data?.clinical_summary ? <View style={s.card}><Text style={s.title}>Summary</Text><Text>Total events: {display(data.clinical_summary.total_events)}</Text><Text>Critical events: {display(data.clinical_summary.critical_events)}</Text><Text>Latest condition: {display(data.clinical_summary.latest_condition)}</Text><Text>Timeline risk indicator: {display(data.clinical_summary.risk_level)}</Text></View> : null}
 {!busy && !error && !data?.timeline?.length ? <Text style={s.hint}>No timeline events recorded.</Text> : null}
 {(data?.timeline || []).map((event,i)=><View key={String(event.id ?? i)} style={s.card}>
 <Text style={s.title}>{display(event.title ?? event.event_title ?? event.label ?? event.event_type ?? "Clinical event")}</Text>
 <Text style={s.hint}>{display(event.date ?? event.event_date ?? event.recorded_at ?? event.created_at ?? event.timestamp)}</Text>
 {event.description || event.details || event.message ? <Text>{display(event.description ?? event.details ?? event.message)}</Text> : null}
 {event.severity || event.priority || event.status ? <Text style={s.hint}>{display(event.severity ?? event.priority ?? event.status)}</Text> : null}
 </View>)}
 <Text style={s.hint}>Clinical timeline summaries are not a substitute for a clinical assessment.</Text>
 </ScrollView>;
}
const s=StyleSheet.create({page:{flex:1,backgroundColor:"#f8fafc"},content:{padding:16,paddingBottom:32,gap:12},heading:{fontSize:22,fontWeight:"800",color:"#0f172a"},hint:{fontSize:13,color:"#64748b"},error:{color:"#b91c1c"},card:{padding:16,backgroundColor:"white",borderRadius:12,borderWidth:1,borderColor:"#e2e8f0",gap:6},title:{fontSize:16,fontWeight:"700",color:"#0f172a"}});
