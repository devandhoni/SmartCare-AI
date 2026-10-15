import React, {useCallback, useEffect, useMemo, useState} from "react";
import {ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View} from "react-native";
import {addMedicine, createStock, editMedicine, editStock, getMedicines, getStock, getTransactions, inventoryError, moveStock, removeMedicine, type Medicine, type MedicineForm, type MovementType, type Stock, type StockForm, type StockTransaction} from "../../api/inventoryApi";

const emptyMedicine = (): MedicineForm => ({medicine_name:"",category:"",dosage:"",unit:"",supplier:""});
const emptyStock = (): StockForm => ({medication_id:"",quantity:"0",minimum_stock:"10",expiry_date:"",location:""});
const TYPES: MovementType[] = ["STOCK_IN","STOCK_OUT","ADJUSTMENT_IN","ADJUSTMENT_OUT"];
type Tab = "medicines" | "stock";
type FormMode = "medicine" | "stock" | "movement" | "history" | null;
const name = (medicine?: Medicine | null) => [medicine?.medicine_name,medicine?.dosage,medicine?.unit].filter(Boolean).join(" · ") || "Unknown medicine";
const positiveInt = (v:string, allowZero=false) => /^\d+$/.test(v.trim()) && Number.isSafeInteger(Number(v)) && Number(v)>= (allowZero?0:1);
const formatDate = (v?:string|null) => v ? String(v).slice(0,10) : "—";
function Button({title,onPress,disabled=false,secondary=false,danger=false}:{title:string;onPress:()=>void;disabled?:boolean;secondary?:boolean;danger?:boolean}) {return <Pressable disabled={disabled} onPress={onPress} style={[styles.button,secondary&&styles.secondary,danger&&styles.danger,disabled&&styles.disabled]}><Text style={[styles.buttonText,(secondary||danger)&&styles.secondaryText]}>{title}</Text></Pressable>;}
function Field({label,value,onChange,number=false,hint}:{label:string;value:string;onChange:(value:string)=>void;number?:boolean;hint?:string}) {return <View style={styles.field}><Text style={styles.label}>{label}</Text><TextInput style={styles.input} value={value} onChangeText={onChange} keyboardType={number?"numeric":"default"} autoCapitalize="sentences" placeholder={hint} placeholderTextColor="#94a3b8"/></View>;}
function Chip({title,active,onPress}:{title:string;active:boolean;onPress:()=>void}) {return <Pressable onPress={onPress} style={[styles.chip,active&&styles.chipActive]}><Text style={[styles.chipText,active&&styles.chipTextActive]}>{title}</Text></Pressable>;}
function Detail({label,value}:{label:string;value:unknown}) {return <Text style={styles.detail}><Text style={styles.detailLabel}>{label}: </Text>{value==null||value===""?"—":String(value)}</Text>;}
export default function InventoryScreen() {
  const [tab,setTab]=useState<Tab>("medicines");
  const [medicines,setMedicines]=useState<Medicine[]>([]);
  const [stock,setStock]=useState<Stock[]>([]);
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [search,setSearch]=useState("");
  const [filter,setFilter]=useState("ALL");
  const [mode,setMode]=useState<FormMode>(null);
  const [selectedMedicine,setSelectedMedicine]=useState<Medicine|null>(null);
  const [selectedStock,setSelectedStock]=useState<Stock|null>(null);
  const [medicineForm,setMedicineForm]=useState<MedicineForm>(emptyMedicine());
  const [stockForm,setStockForm]=useState<StockForm>(emptyStock());
  const [movementType,setMovementType]=useState<MovementType>("STOCK_IN");
  const [movementQuantity,setMovementQuantity]=useState("");
  const [reference,setReference]=useState("");
  const [transactions,setTransactions]=useState<StockTransaction[]>([]);
  const [loadingHistory,setLoadingHistory]=useState(false);
  const [message,setMessage]=useState("");
  const load=useCallback(async()=>{setLoading(true);try {const [m,s]=await Promise.all([getMedicines(),getStock()]);setMedicines(m);setStock(s);}catch(e){Alert.alert("Unable to load",inventoryError(e,"Unable to load inventory."));}finally{setLoading(false);}},[]);
  useEffect(()=>{void load();},[load]);
  const attention=stock.filter(s=>s.needs_attention).length;
  const available=medicines.filter(m=>!stock.some(s=>Number(s.medication_id)===Number(m.id)));
  const visibleMedicines=useMemo(()=>medicines.filter(m=>[m.medicine_name,m.category,m.dosage,m.unit,m.supplier].join(" ").toLowerCase().includes(search.toLowerCase())),[medicines,search]);
  const visibleStock=useMemo(()=>stock.filter(s=>{const status=String(s.stock_status||"").toUpperCase();const match=filter==="ALL"||(filter==="NEEDS_ATTENTION"?!!s.needs_attention:status===filter);return match&&[name(s.medication),s.location,status].join(" ").toLowerCase().includes(search.toLowerCase());}),[stock,search,filter]);
  const done=async(text:string)=>{setMode(null);setMessage(text);await load();};
  const save=async(action:()=>Promise<unknown>,success:string)=>{if(saving)return;setSaving(true);try{await action();await done(success);}catch(e){Alert.alert("Could not save",inventoryError(e,"Please check the details and try again."));}finally{setSaving(false);}};
  const openMedicine=(m?:Medicine)=>{setSelectedMedicine(m||null);setMedicineForm(m?{medicine_name:m.medicine_name,category:m.category||"",dosage:m.dosage||"",unit:m.unit||"",supplier:m.supplier||""}:emptyMedicine());setMode("medicine");setMessage("");};
  const openStock=(s?:Stock)=>{setSelectedStock(s||null);setStockForm(s?{medication_id:String(s.medication_id),quantity:String(s.quantity),minimum_stock:String(s.minimum_stock),expiry_date:formatDate(s.expiry_date)==="—"?"":formatDate(s.expiry_date),location:s.location||""}:emptyStock());setMode("stock");setMessage("");};
  const openMovement=(s:Stock)=>{setSelectedStock(s);setMovementType("STOCK_IN");setMovementQuantity("");setReference("");setMode("movement");setMessage("");};
  const openHistory=async(s:Stock)=>{setSelectedStock(s);setMode("history");setTransactions([]);setLoadingHistory(true);try{setTransactions(await getTransactions(s.id));}catch(e){Alert.alert("History unavailable",inventoryError(e,"Unable to load transactions."));}finally{setLoadingHistory(false);}};
  const confirmDelete=(m:Medicine)=>Alert.alert("Delete medicine?",`Delete ${name(m)}? This cannot be undone.`,[{text:"Cancel",style:"cancel"},{text:"Delete",style:"destructive",onPress:()=>{void save(()=>removeMedicine(m.id),"Medicine deleted.");}}]);
  const submitMedicine=()=>{if(!medicineForm.medicine_name.trim()){Alert.alert("Required","Enter a medicine name.");return;}if(medicineForm.medicine_name.length>255||medicineForm.category.length>100||medicineForm.dosage.length>100||medicineForm.unit.length>100||medicineForm.supplier.length>255){Alert.alert("Too long","Please shorten the medicine details.");return;}void save(()=>selectedMedicine?editMedicine(selectedMedicine.id,medicineForm):addMedicine(medicineForm),selectedMedicine?"Medicine updated.":"Medicine added.");};
  const submitStock=()=>{if(!selectedStock&&!stockForm.medication_id){Alert.alert("Required","Choose a medicine.");return;}if(!selectedStock&&!positiveInt(stockForm.quantity,true)){Alert.alert("Invalid quantity","Opening stock must be a whole number of zero or more.");return;}if(stockForm.minimum_stock.trim()&&!positiveInt(stockForm.minimum_stock,true)){Alert.alert("Invalid minimum","Minimum stock must be a whole number of zero or more.");return;}if(stockForm.expiry_date.trim()&&!/^\d{4}-\d{2}-\d{2}$/.test(stockForm.expiry_date.trim())){Alert.alert("Invalid date","Use YYYY-MM-DD for expiry date.");return;}if(stockForm.location.length>255){Alert.alert("Too long","Location must be 255 characters or less.");return;}void save(()=>selectedStock?editStock(selectedStock.id,stockForm):createStock(stockForm),selectedStock?"Stock details updated.":"Stock record created.");};
  const submitMovement=()=>{if(!selectedStock||!positiveInt(movementQuantity)){Alert.alert("Invalid quantity","Enter a whole number greater than zero.");return;}if(reference.length>255){Alert.alert("Too long","Reference must be 255 characters or less.");return;}const outgoing=movementType.endsWith("OUT");if(outgoing&&Number(movementQuantity)>Number(selectedStock.quantity)){Alert.alert("Insufficient stock","The quantity exceeds available stock.");return;}Alert.alert("Confirm stock movement",`${movementType.replace(/_/g," ")} ${movementQuantity} units for ${name(selectedStock.medication)}? This will be audited.`,[{text:"Cancel",style:"cancel"},{text:"Confirm",onPress:()=>{void save(()=>moveStock(selectedStock.id,movementType,Number(movementQuantity),reference),"Stock movement recorded.");}}]);};
  return <ScrollView style={styles.page} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.title}>Medicine Inventory</Text><Text style={styles.subtitle}>Medicine catalogue, stock levels and audited movements</Text>
    {!!message&&<Text style={styles.success}>{message}</Text>}
    {mode!==null ? <View style={styles.panel}>
      <Text style={styles.heading}>{mode==="medicine"?(selectedMedicine?"Edit medicine":"Add medicine"):mode==="stock"?(selectedStock?"Edit stock details":"Create stock record"):mode==="movement"?"Stock adjustment":"Transaction history"}</Text>
      <Button title="← Back to inventory" secondary onPress={()=>setMode(null)}/>
      {mode==="medicine"&&<>
        {(["medicine_name","category","dosage","unit","supplier"] as (keyof MedicineForm)[]).map(k=><Field key={k} label={({medicine_name:"Medicine name *",category:"Category",dosage:"Dosage / strength",unit:"Unit / form",supplier:"Supplier"} as Record<keyof MedicineForm,string>)[k]} value={medicineForm[k]} onChange={v=>setMedicineForm(p=>({...p,[k]:v}))}/>)}
        <Button title={saving?"Saving...":"Save medicine"} disabled={saving} onPress={submitMedicine}/>
      </>}
      {mode==="stock"&&<>
        {!selectedStock&&<><Text style={styles.label}>Select medicine *</Text>{available.length===0?<Text style={styles.subtitle}>Every medicine already has a stock record.</Text>:available.map(m=><Chip key={m.id} title={name(m)} active={stockForm.medication_id===String(m.id)} onPress={()=>setStockForm(p=>({...p,medication_id:String(m.id)}))}/>)}</>}
        {!selectedStock&&<Field label="Opening quantity *" value={stockForm.quantity} number onChange={v=>setStockForm(p=>({...p,quantity:v}))}/>}
        {!!selectedStock&&<Detail label="Current quantity (adjust separately)" value={selectedStock.quantity}/>}
        <Field label="Minimum stock" value={stockForm.minimum_stock} number onChange={v=>setStockForm(p=>({...p,minimum_stock:v}))}/>
        <Field label="Expiry date" hint="YYYY-MM-DD" value={stockForm.expiry_date} onChange={v=>setStockForm(p=>({...p,expiry_date:v}))}/>
        <Field label="Storage location" value={stockForm.location} onChange={v=>setStockForm(p=>({...p,location:v}))}/>
        <Button title={saving?"Saving...":"Save stock details"} disabled={saving||(!selectedStock&&!available.length)} onPress={submitStock}/>
      </>}
      {mode==="movement"&&selectedStock&&<>
        <Detail label="Medicine" value={name(selectedStock.medication)}/><Detail label="Available stock" value={selectedStock.quantity}/>
        {TYPES.map(t=><Chip key={t} title={t.replace(/_/g," ")} active={movementType===t} onPress={()=>setMovementType(t)}/>)}
        <Field label="Quantity *" number value={movementQuantity} onChange={setMovementQuantity}/><Field label="Reference / reason" value={reference} onChange={setReference}/>
        <Text style={styles.subtitle}>All movements create a transaction record. Stock Out cannot exceed available quantity.</Text>
        <Button title={saving?"Saving...":"Review and confirm movement"} disabled={saving} onPress={submitMovement}/>
      </>}
      {mode==="history"&&<>
        <Detail label="Medicine" value={name(selectedStock?.medication)}/>
        {loadingHistory?<ActivityIndicator/>:transactions.length===0?<Text style={styles.subtitle}>No transactions recorded.</Text>:transactions.map(t=><View key={t.id} style={styles.item}><Text style={styles.itemTitle}>{t.transaction_type} · {t.quantity} units</Text><Detail label="Date" value={t.transaction_date}/><Detail label="Reference" value={t.reference}/><Detail label="Resident" value={t.resident?.full_name}/><Detail label="Performed by" value={t.performedBy?.full_name||(typeof t.performed_by==="object"?t.performed_by?.full_name:null)}/></View>)}
      </>}
    </View> : <>
      <View style={styles.metrics}><Text style={styles.metric}>Medicines: {medicines.length}</Text><Text style={styles.metric}>Stock records: {stock.length}</Text><Text style={styles.metric}>Needs attention: {attention}</Text></View>
      <View style={styles.row}><Chip title="Medicine Master" active={tab==="medicines"} onPress={()=>{setTab("medicines");setSearch("");}}/><Chip title="Stock" active={tab==="stock"} onPress={()=>{setTab("stock");setSearch("");}}/></View>
      <Field label="Search" hint="Medicine, strength, location..." value={search} onChange={setSearch}/>
      <Button title={tab==="medicines"?"+ Add medicine":"+ Add stock record"} disabled={tab==="stock"&&available.length===0} onPress={()=>tab==="medicines"?openMedicine():openStock()}/>
      {tab==="stock"&&<View style={styles.filters}>{["ALL","NEEDS_ATTENTION","LOW_STOCK","OUT_OF_STOCK","EXPIRING_SOON","EXPIRED"].map(f=><Chip key={f} title={f.replace(/_/g," ")} active={filter===f} onPress={()=>setFilter(f)}/>)}</View>}
      {loading?<ActivityIndicator size="large" style={{margin:25}}/>:tab==="medicines"?(visibleMedicines.length===0?<Text style={styles.subtitle}>No medicines found.</Text>:visibleMedicines.map(m=>{const s=stock.find(x=>Number(x.medication_id)===Number(m.id));return <View key={m.id} style={styles.item}><Text style={styles.itemTitle}>{name(m)}</Text><Detail label="Category" value={m.category}/><Detail label="Supplier" value={m.supplier}/><Detail label="Stock status" value={s?.stock_status||"Not configured"}/><View style={styles.row}><Button title="Edit" secondary onPress={()=>openMedicine(m)}/><Button title="Delete" danger onPress={()=>confirmDelete(m)}/></View></View>;})):(visibleStock.length===0?<Text style={styles.subtitle}>No matching stock records.</Text>:visibleStock.map(s=><View key={s.id} style={styles.item}><Text style={styles.itemTitle}>{name(s.medication)}</Text><Detail label="Quantity" value={s.quantity}/><Detail label="Minimum" value={s.minimum_stock}/><Detail label="Status" value={String(s.stock_status||"IN_STOCK").replace(/_/g," ")}/><Detail label="Expiry" value={formatDate(s.expiry_date)}/><Detail label="Location" value={s.location}/><View style={styles.row}><Button title="Adjust" secondary onPress={()=>openMovement(s)}/><Button title="History" secondary onPress={()=>{void openHistory(s);}}/><Button title="Details" secondary onPress={()=>openStock(s)}/></View></View>))}
      <Button title="Refresh inventory" secondary onPress={()=>{void load();}}/>
    </>}
  </ScrollView>;
}
const styles=StyleSheet.create({page:{flex:1,backgroundColor:"#f8fafc"},content:{padding:16,paddingBottom:48,gap:12},title:{fontSize:25,fontWeight:"800",color:"#0f172a"},subtitle:{color:"#64748b",fontSize:13,lineHeight:20},panel:{backgroundColor:"white",borderRadius:14,padding:15,gap:12,borderWidth:1,borderColor:"#e2e8f0"},heading:{fontSize:19,fontWeight:"700",color:"#0f172a"},row:{flexDirection:"row",flexWrap:"wrap",gap:8,alignItems:"center"},filters:{gap:5},metrics:{backgroundColor:"white",padding:14,borderRadius:12,gap:4},metric:{fontSize:14,fontWeight:"600",color:"#334155"},item:{backgroundColor:"white",borderWidth:1,borderColor:"#e2e8f0",borderRadius:12,padding:14,gap:7},itemTitle:{fontSize:16,fontWeight:"700",color:"#0f172a"},detail:{color:"#334155",fontSize:13},detailLabel:{fontWeight:"700",color:"#64748b"},field:{gap:5},label:{fontWeight:"700",fontSize:13,color:"#334155"},input:{borderWidth:1,borderColor:"#cbd5e1",backgroundColor:"white",borderRadius:10,paddingHorizontal:12,paddingVertical:10,fontSize:15,color:"#0f172a"},button:{backgroundColor:"#2563eb",paddingVertical:10,paddingHorizontal:13,borderRadius:10,alignItems:"center",justifyContent:"center"},secondary:{backgroundColor:"#e2e8f0"},danger:{backgroundColor:"#fee2e2"},disabled:{opacity:0.45},buttonText:{color:"white",fontWeight:"700",fontSize:13},secondaryText:{color:"#1e293b"},chip:{paddingVertical:9,paddingHorizontal:12,borderRadius:10,backgroundColor:"#e2e8f0",marginBottom:4,alignSelf:"flex-start"},chipActive:{backgroundColor:"#dbeafe",borderWidth:1,borderColor:"#2563eb"},chipText:{color:"#334155",fontWeight:"600",fontSize:13},chipTextActive:{color:"#1d4ed8"},success:{color:"#047857",backgroundColor:"#d1fae5",padding:10,borderRadius:8,fontWeight:"600"}});
