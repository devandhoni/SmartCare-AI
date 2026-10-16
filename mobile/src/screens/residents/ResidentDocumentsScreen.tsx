import React,{useCallback,useEffect,useState} from "react";
import {ActivityIndicator,Alert,Pressable,RefreshControl,ScrollView,StyleSheet,Text,TextInput,View} from "react-native";
import {useRoute} from "@react-navigation/native";
import * as DocumentPicker from "expo-document-picker";
import * as FileSystem from "expo-file-system/legacy";
import * as Sharing from "expo-sharing";
import * as SecureStore from "expo-secure-store";
import api from "../../api/api";
import {deleteResidentDocument,getResidentDocuments,ResidentDocument,updateResidentDocument,uploadResidentDocument} from "../../api/residentHistoryApi";
const ACCEPTED=["pdf","jpg","jpeg","png","doc","docx"];
const message=(e:any)=>e?.response?.data?.message || "Request failed. Please try again.";
export default function ResidentDocumentsScreen(){
 const route=useRoute<any>();const id=Number(route.params?.residentId);
 const [docs,setDocs]=useState<ResidentDocument[]>([]);const [busy,setBusy]=useState(false);const [error,setError]=useState("");const [working,setWorking]=useState(false);
 const [selected,setSelected]=useState<ResidentDocument|null>(null);const [upload,setUpload]=useState(false);const [picked,setPicked]=useState<DocumentPicker.DocumentPickerAsset|null>(null);
 const [title,setTitle]=useState("");const [kind,setKind]=useState("");const [notes,setNotes]=useState("");const [status,setStatus]=useState("ACTIVE");
 const load=useCallback(async()=>{setBusy(true);setError("");try{setDocs(await getResidentDocuments(id));}catch(e:any){setError(message(e));}finally{setBusy(false);}},[id]);useEffect(()=>{load();},[load]);
 const reset=()=>{setSelected(null);setUpload(false);setPicked(null);setTitle("");setKind("");setNotes("");setStatus("ACTIVE");};
 const edit=(d:ResidentDocument)=>{setUpload(false);setSelected(d);setTitle(d.title);setKind(d.document_type);setNotes(d.notes||"");setStatus(d.status||"ACTIVE");};
 const pick=async()=>{try{const r=await DocumentPicker.getDocumentAsync({copyToCacheDirectory:true,type:"*/*"});if(r.canceled)return;const f=r.assets[0];const ext=f.name.split(".").pop()?.toLowerCase()||"";if(!ACCEPTED.includes(ext)){Alert.alert("Unsupported file","Select PDF, JPG, PNG, DOC, or DOCX.");return;}if(f.size && f.size>10*1024*1024){Alert.alert("File too large","Maximum size is 10 MB.");return;}setPicked(f);if(!title.trim())setTitle(f.name);}catch(e:any){Alert.alert("File picker",message(e));}};
 const save=async()=>{if(!title.trim()||!kind.trim()){Alert.alert("Required","Enter a document title and type.");return;}if(upload&&!picked){Alert.alert("Required","Select a document file.");return;}setWorking(true);try{if(upload&&picked)await uploadResidentDocument(id,{uri:picked.uri,name:picked.name,mimeType:picked.mimeType||undefined},{title:title.trim(),document_type:kind.trim(),notes});else if(selected)await updateResidentDocument(selected.id,{title:title.trim(),document_type:kind.trim(),notes,status});reset();await load();Alert.alert("Saved","Document information saved.");}catch(e:any){Alert.alert("Unable to save",message(e));}finally{setWorking(false);}};
 const remove=(d:ResidentDocument)=>Alert.alert("Delete document?",`This permanently deletes ${d.title} and its file.`,[{text:"Cancel",style:"cancel"},{text:"Delete permanently",style:"destructive",onPress:async()=>{setWorking(true);try{await deleteResidentDocument(d.id);reset();await load();}catch(e:any){Alert.alert("Delete failed",message(e));}finally{setWorking(false);}}}]);
 const download=async(d:ResidentDocument)=>{setWorking(true);try{const token=await SecureStore.getItemAsync("token");if(!token)throw new Error("Session expired. Sign in again.");const url=(api.defaults.baseURL||"").replace(/\/$/,"")+`/resident-documents/${d.id}/download`;const name=(d.original_name||`document-${d.id}`).replace(/[^a-zA-Z0-9._-]/g,"_");const target=(FileSystem.cacheDirectory||FileSystem.documentDirectory)+`${d.id}-${name}`;const result=await FileSystem.downloadAsync(url,target,{headers:{Authorization:`Bearer ${token}`,Accept:"application/octet-stream"}});if(result.status!==200){await FileSystem.deleteAsync(target,{idempotent:true});throw new Error(`Download failed (HTTP ${result.status}).`);}if(await Sharing.isAvailableAsync())await Sharing.shareAsync(result.uri);else Alert.alert("Downloaded","File saved to the app's local storage.");}catch(e:any){Alert.alert("Download failed",e?.message||"Unable to download document.");}finally{setWorking(false);}};
 return <ScrollView style={s.page} contentContainerStyle={s.content} refreshControl={<RefreshControl refreshing={busy} onRefresh={load}/> }>
 <Text style={s.heading}>Resident Documents</Text><Text style={s.hint}>Documents are private resident records.</Text>
 <Pressable style={s.button} onPress={()=>{reset();setUpload(true);}}><Text style={s.buttonText}>+ Upload Document</Text></Pressable>
 {error?<Text style={s.error}>{error}</Text>:null}{busy?<ActivityIndicator/>:null}
 {!busy&&!docs.length?<Text style={s.hint}>No documents recorded.</Text>:null}
 {docs.map(d=><View key={d.id} style={s.card}><Text style={s.title}>{d.title}</Text><Text>{d.document_type} · {d.status||"ACTIVE"}</Text><Text style={s.hint}>{d.original_name||"File"} · {d.created_at||""}</Text>{d.notes?<Text>{d.notes}</Text>:null}
 <View style={s.row}><Pressable disabled={working} onPress={()=>download(d)} style={s.small}><Text style={s.smallText}>Download / Share</Text></Pressable><Pressable disabled={working} onPress={()=>edit(d)} style={s.small}><Text style={s.smallText}>Edit</Text></Pressable><Pressable disabled={working} onPress={()=>remove(d)} style={s.danger}><Text style={s.buttonText}>Delete</Text></Pressable></View></View>)}
 {(upload||selected)?<View style={s.card}><Text style={s.title}>{upload?"Upload Document":"Edit Document"}</Text>
 {upload?<Pressable onPress={pick} style={s.small}><Text style={s.smallText}>{picked?picked.name:"Choose PDF, image, or Word file"}</Text></Pressable>:null}
 <Text>Document title</Text><TextInput style={s.input} value={title} onChangeText={setTitle} placeholder="Document title"/>
 <Text>Document type</Text><TextInput style={s.input} value={kind} onChangeText={setKind} placeholder="e.g. Medical Report"/>
 <Text>Notes (optional)</Text><TextInput style={[s.input,{minHeight:65}]} multiline value={notes} onChangeText={setNotes}/>
 {!upload?<><Text>Status</Text><View style={s.row}>{["ACTIVE","ARCHIVED"].map(v=><Pressable key={v} onPress={()=>setStatus(v)} style={status===v?s.button:s.small}><Text style={status===v?s.buttonText:s.smallText}>{v}</Text></Pressable>)}</View></>:null}
 <Text style={s.hint}>Supported: PDF, JPG, PNG, DOC, DOCX · Maximum 10 MB.</Text>
 <View style={s.row}><Pressable disabled={working} onPress={save} style={s.button}><Text style={s.buttonText}>{working?"Saving...":"Save"}</Text></Pressable><Pressable onPress={reset} style={s.small}><Text style={s.smallText}>Cancel</Text></Pressable></View></View>:null}
 </ScrollView>;
}
const s=StyleSheet.create({page:{flex:1,backgroundColor:"#f8fafc"},content:{padding:16,paddingBottom:40,gap:12},heading:{fontSize:22,fontWeight:"800",color:"#0f172a"},hint:{fontSize:13,color:"#64748b"},error:{color:"#b91c1c"},card:{padding:15,backgroundColor:"white",borderRadius:12,borderWidth:1,borderColor:"#e2e8f0",gap:8},title:{fontSize:16,fontWeight:"700",color:"#0f172a"},row:{flexDirection:"row",gap:8,flexWrap:"wrap",alignItems:"center"},button:{backgroundColor:"#2563eb",borderRadius:9,padding:11},buttonText:{color:"white",fontWeight:"700"},small:{backgroundColor:"#e2e8f0",borderRadius:9,padding:10},smallText:{color:"#0f172a",fontWeight:"600"},danger:{backgroundColor:"#b91c1c",borderRadius:9,padding:10},input:{borderWidth:1,borderColor:"#cbd5e1",borderRadius:8,padding:10,backgroundColor:"white"}});
