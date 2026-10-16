import React, { useCallback, useEffect, useState } from "react";
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from "react-native";
import api from "../../api/api";

type Resident = { id: number; full_name: string; status?: string };
type Fee = { configured: boolean; monthly_fee: string | null; reason: string | null };
type Payment = { id: number; amount: string; payment_reference: string; payment_method: string; payment_date: string };
type Item = { id: number; description: string; quantity: string; unit_amount: string; line_total: string };
type Invoice = { id: number; invoice_number: string; billing_period_start: string; billing_period_end: string; due_date: string; status: string; is_overdue: boolean; total_amount: string; amount_paid: string; balance_due: string; items?: Item[]; payments?: Payment[] };
type Receipt = { payment_id: number; payment_reference: string; invoice_number: string; resident_name: string; payment_amount: string; payment_date: string; payment_method: string; received_by?: string | null; invoice_balance_due: string };
const METHODS = ["CASH", "BANK_TRANSFER", "CARD", "CHEQUE", "OTHER"];
const reasonLabels: Record<string, string> = {
  NO_COMPLETED_ADMISSION: "No completed admission.",
  NO_ADMISSION_CONSENT: "No admission agreement.",
  CONSENT_NOT_COMPLETED: "Admission agreement not completed.",
  PAYMENT_TERMS_NOT_ACKNOWLEDGED: "Payment terms not acknowledged.",
  AGREEMENT_NOT_ACKNOWLEDGED: "Admission agreement not acknowledged.",
  BILLING_FEE_NOT_CONFIGURED: "Monthly fee not configured.",
};
const currency = (value: unknown) => `RM ${Number(value ?? 0).toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const datePart = (value?: string) => value ? value.slice(0, 10) : "-";
const today = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,"0")}-${String(d.getDate()).padStart(2,"0")}`; };
const errorText = (e: unknown) => {
  const err = e as { response?: { data?: { message?: string; errors?: Record<string,string[]> } } };
  const messages = err?.response?.data?.errors;
  return (messages && Object.values(messages).flat()[0]) || err?.response?.data?.message || "Request failed. Please try again.";
};
function residentsFrom(data: any): Resident[] {
  const items = Array.isArray(data) ? data : Array.isArray(data?.data) ? data.data : Array.isArray(data?.residents) ? data.residents : data?.residents?.data;
  return Array.isArray(items) ? items : [];
}
function Action({ title, onPress, disabled = false, subtle = false }: { title: string; onPress: () => void; disabled?: boolean; subtle?: boolean }) {
  return <Pressable onPress={onPress} disabled={disabled} style={[styles.action, subtle && styles.secondary, disabled && styles.disabled]}><Text style={[styles.actionText, subtle && styles.secondaryText]}>{title}</Text></Pressable>;
}
function Label({ title, value }: { title: string; value: string }) { return <View style={styles.stat}><Text style={styles.muted}>{title}</Text><Text style={styles.value}>{value}</Text></View>; }
export default function BillingScreen() {
  const [residents, setResidents] = useState<Resident[]>([]);
  const [residentId, setResidentId] = useState<number | null>(null);
  const [residentSearch, setResidentSearch] = useState("");
  const [residentPicker, setResidentPicker] = useState(false);
  const [fee, setFee] = useState<Fee | null>(null);
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [balance, setBalance] = useState("0.00");
  const [selected, setSelected] = useState<Invoice | null>(null);
  const [receipt, setReceipt] = useState<Receipt | null>(null);
  const [receiptId, setReceiptId] = useState<number | null>(null);
  const [paymentOpen, setPaymentOpen] = useState(false);
  const [amount, setAmount] = useState("");
  const [paymentDate, setPaymentDate] = useState(today());
  const [method, setMethod] = useState("CASH");
  const [notes, setNotes] = useState("");
  const [month, setMonth] = useState(today().slice(0,7));
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");
  const [failure, setFailure] = useState("");
  const resident = residents.find(r => r.id === residentId);
  const active = resident?.status === "Active";
  const loadResidents = useCallback(async () => {
    try { const { data } = await api.get("/residents", { params: { per_page: 200 } }); const list = residentsFrom(data); setResidents(list); setResidentId(current => current ?? ((list.find(r=>r.status === "Active") || list[0])?.id ?? null)); }
    catch (e) { setFailure(errorText(e)); }
  }, []);
  const loadBilling = useCallback(async (id: number) => {
    setLoading(true); setFailure("");
    try {
      const [f, i] = await Promise.all([api.get(`/residents/${id}/billing/fee-status`), api.get(`/residents/${id}/billing/invoices`)]);
      setFee(f.data); setInvoices(Array.isArray(i.data?.invoices) ? i.data.invoices : []); setBalance(String(i.data?.outstanding_balance ?? "0.00"));
    } catch (e) { setFee(null); setInvoices([]); setBalance("0.00"); setFailure(errorText(e)); }
    finally { setLoading(false); }
  }, []);
  useEffect(() => { void loadResidents(); }, [loadResidents]);
  useEffect(() => { setSelected(null); setReceipt(null); setPaymentOpen(false); setMessage(""); if (residentId != null) void loadBilling(residentId); }, [residentId, loadBilling]);
  const run = async (fn: () => Promise<void>) => { setBusy(true); setFailure(""); setMessage(""); try { await fn(); } catch (e) { setFailure(errorText(e)); Alert.alert("Billing", errorText(e)); } finally { setBusy(false); } };
  const generate = () => {
    if (!residentId || !active || !fee?.configured || !/^\d{4}-(0[1-9]|1[0-2])$/.test(month)) { setFailure("Select an active resident with a configured fee and valid billing month (YYYY-MM)."); return; }
    Alert.alert("Generate invoice?", `Create the invoice for ${month}? This action changes billing records.`, [ { text: "Cancel", style: "cancel" }, { text: "Generate", onPress: () => void run(async () => { await api.post(`/residents/${residentId}/billing/invoices/monthly`, { billing_month: `${month}-01` }); setMessage("Monthly invoice generated."); await loadBilling(residentId); }) } ]);
  };
  const record = () => {
    if (!selected || !residentId) return;
    const numeric = Number(amount);
    if (!Number.isFinite(numeric) || numeric <= 0 || numeric > Number(selected.balance_due) || !/^\d{4}-\d{2}-\d{2}$/.test(paymentDate)) { setFailure("Enter a valid amount within the outstanding balance and a payment date (YYYY-MM-DD)."); return; }
    Alert.alert("Confirm payment", `Record ${currency(numeric)} for ${selected.invoice_number}? Please verify the payment was received.`, [ { text: "Cancel", style: "cancel" }, { text: "Confirm payment", onPress: () => void run(async () => { await api.post(`/billing/invoices/${selected.id}/payments`, { amount: numeric, payment_date: paymentDate, payment_method: method, notes: notes.trim() || null }); setPaymentOpen(false); setSelected(null); setMessage("Payment recorded successfully."); await loadBilling(residentId); }) } ]);
  };
  const openInvoice = async (invoice: Invoice) => {
    setFailure(""); setSelected(invoice); setReceipt(null); setPaymentOpen(false);
    try { const {data} = await api.get(`/billing/invoices/${invoice.id}`); if (data?.invoice) setSelected(data.invoice); }
    catch (e) { setFailure(errorText(e)); }
  };
  const openReceipt = async (id: number) => {
    setReceiptId(id); setFailure("");
    try { const {data} = await api.get(`/billing/payments/${id}/receipt`); setReceipt(data?.receipt ?? null); }
    catch (e) { setFailure(errorText(e)); }
    finally { setReceiptId(null); }
  };
  const downloadPdf = async (id: number, reference: string) => {
    await run(async () => {
      // Fetch with Axios to retain Sanctum Authorization; do not expose a token in a browser URL.
      const FileSystem = await import("expo-file-system/legacy");
      const Sharing = await import("expo-sharing");
      const token = await (await import("expo-secure-store")).getItemAsync("token");
      const base = String(api.defaults.baseURL ?? "").replace(/\/$/, "");
      const uri = `${base}/billing/payments/${id}/receipt/pdf`;
      const output = `${FileSystem.cacheDirectory}Receipt-${reference.replace(/[^A-Za-z0-9_-]/g, "_")}.pdf`;
      const result = await FileSystem.downloadAsync(uri, output, { headers: { Accept: "application/pdf", ...(token ? { Authorization: `Bearer ${token}` } : {}) } });
      if (result.status !== 200) throw new Error("PDF server response was not successful.");
      if (await Sharing.isAvailableAsync()) await Sharing.shareAsync(result.uri, { mimeType: "application/pdf", UTI: "com.adobe.pdf" });
      else Alert.alert("Receipt saved temporarily", "Sharing is not available on this device.");
    });
  };
  return <ScrollView style={styles.root} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Text style={styles.heading}>Billing & Payments</Text>
    <Text style={styles.muted}>Review resident fees, invoices, payments and receipts.</Text>
    {!!failure && <Text style={styles.error}>{failure}</Text>}{!!message && <Text style={styles.success}>{message}</Text>}
    <View style={styles.card}><Text style={styles.label}>Resident</Text><Action title={resident ? `${resident.full_name} (${resident.status || "Unknown"})  ▾` : "Choose resident ▾"} subtle onPress={()=>setResidentPicker(!residentPicker)} />
      {residentPicker && <View><TextInput style={styles.input} placeholder="Search residents" value={residentSearch} onChangeText={setResidentSearch} />
      {residents.filter(r => r.full_name.toLowerCase().includes(residentSearch.toLowerCase())).map(r => <Pressable key={r.id} style={styles.choice} onPress={()=>{setResidentId(r.id);setResidentPicker(false);}}><Text>{r.full_name} — {r.status}</Text></Pressable>)}</View>}
      {residentId != null && <Action title={loading ? "Loading..." : "Refresh billing"} subtle disabled={loading || busy} onPress={()=>void loadBilling(residentId)} />}
    </View>
    {loading ? <ActivityIndicator size="large" /> : residentId != null && <>
      <View style={styles.card}><Label title="Monthly fee" value={fee?.configured ? currency(fee.monthly_fee) : "Not configured"}/>{!fee?.configured && <Text style={styles.muted}>{reasonLabels[fee?.reason ?? ""] || "Billing agreement needs attention."}</Text>}<Label title="Outstanding balance" value={currency(balance)}/><Label title="Invoices" value={String(invoices.length)}/></View>
      <View style={styles.card}><Text style={styles.label}>Generate monthly invoice</Text><Text style={styles.muted}>Billing month (YYYY-MM)</Text><TextInput style={styles.input} value={month} onChangeText={setMonth} placeholder="2026-10" autoCapitalize="none" />
        {!active && <Text style={styles.muted}>Historical billing is view-only for inactive residents.</Text>}
        <Action title="Generate invoice" disabled={busy || !active || !fee?.configured} onPress={generate}/></View>
      <Text style={styles.section}>Invoice history</Text>
      {invoices.length === 0 && <Text style={styles.muted}>No invoices recorded for this resident.</Text>}
      {invoices.map(inv => <Pressable key={inv.id} style={styles.card} onPress={()=>void openInvoice(inv)}><Text style={styles.label}>{inv.invoice_number}  ›</Text><Text style={styles.muted}>{datePart(inv.billing_period_start)} to {datePart(inv.billing_period_end)}</Text><Text style={styles.muted}>{inv.status.replace(/_/g," ")}{inv.is_overdue ? " • OVERDUE" : ""} • Due {datePart(inv.due_date)}</Text><Label title="Remaining" value={currency(inv.balance_due)}/></Pressable>)}
    </>}
    {selected && <View style={styles.card}><Text style={styles.section}>Invoice details</Text><Text style={styles.label}>{selected.invoice_number}</Text><Label title="Status" value={selected.status}/><Label title="Total" value={currency(selected.total_amount)}/><Label title="Paid" value={currency(selected.amount_paid)}/><Label title="Remaining" value={currency(selected.balance_due)}/>
      {(selected.items || []).map(i=><View key={i.id} style={styles.choice}><Text>{i.description}</Text><Text>{i.quantity} × {currency(i.unit_amount)} = {currency(i.line_total)}</Text></View>)}
      <Text style={styles.label}>Payments</Text>{!selected.payments?.length && <Text style={styles.muted}>No payments yet.</Text>}
      {(selected.payments || []).map(p=><View key={p.id} style={styles.choice}><Text style={styles.label}>{p.payment_reference}</Text><Text>{datePart(p.payment_date)} • {currency(p.amount)} • {p.payment_method}</Text><Action title={receiptId===p.id ? "Loading..." : "View receipt"} subtle disabled={receiptId===p.id || busy} onPress={()=>void openReceipt(p.id)}/><Action title="Download / Share PDF" subtle disabled={busy} onPress={()=>void downloadPdf(p.id,p.payment_reference)}/></View>)}
      {active && ["ISSUED","PARTIALLY_PAID"].includes(selected.status) && Number(selected.balance_due)>0 && !paymentOpen && <Action title="Record payment" disabled={busy} onPress={()=>{setAmount(String(selected.balance_due));setPaymentDate(today());setMethod("CASH");setNotes("");setPaymentOpen(true);}} />}
      {paymentOpen && <View><Text style={styles.section}>Record payment</Text><Text style={styles.muted}>Amount (RM)</Text><TextInput style={styles.input} keyboardType="decimal-pad" value={amount} onChangeText={setAmount}/><Text style={styles.muted}>Date (YYYY-MM-DD)</Text><TextInput style={styles.input} value={paymentDate} onChangeText={setPaymentDate}/><Text style={styles.muted}>Payment method</Text><View style={styles.wrap}>{METHODS.map(m=><Pressable key={m} style={[styles.chip,method===m && styles.chipActive]} onPress={()=>setMethod(m)}><Text style={method===m ? styles.chipActiveText:undefined}>{m.replace(/_/g," ")}</Text></Pressable>)}</View><Text style={styles.muted}>Notes (optional)</Text><TextInput style={styles.input} maxLength={2000} value={notes} onChangeText={setNotes}/><Action title={busy ? "Recording..." : "Confirm payment"} disabled={busy} onPress={record}/><Action title="Cancel" subtle disabled={busy} onPress={()=>setPaymentOpen(false)}/></View>}
      <Action title="Close invoice" subtle onPress={()=>{setSelected(null);setReceipt(null);setPaymentOpen(false);}} />
    </View>}
    {receipt && <View style={styles.card}><Text style={styles.section}>Payment receipt</Text><Text style={styles.label}>{receipt.payment_reference}</Text><Label title="Resident" value={receipt.resident_name}/><Label title="Invoice" value={receipt.invoice_number}/><Label title="Amount" value={currency(receipt.payment_amount)}/><Label title="Date" value={datePart(receipt.payment_date)}/><Label title="Method" value={receipt.payment_method}/><Label title="Received by" value={receipt.received_by || "-"}/><Action title="Share receipt PDF" disabled={busy} onPress={()=>void downloadPdf(receipt.payment_id, receipt.payment_reference)}/><Action title="Close receipt" subtle onPress={()=>setReceipt(null)}/></View>}
  </ScrollView>;
}
const styles = StyleSheet.create({ root:{flex:1,backgroundColor:"#f8fafc"}, content:{padding:16,paddingBottom:64,gap:12}, heading:{fontSize:23,fontWeight:"800",color:"#0f172a"}, section:{fontSize:18,fontWeight:"800",color:"#0f172a",marginVertical:6}, label:{fontWeight:"700",fontSize:15,color:"#0f172a"}, muted:{color:"#64748b",fontSize:13,lineHeight:20}, value:{fontSize:19,fontWeight:"700",color:"#0f172a",marginTop:3}, card:{padding:16,borderWidth:1,borderColor:"#e2e8f0",borderRadius:16,backgroundColor:"white",gap:10}, stat:{paddingVertical:3}, action:{backgroundColor:"#2563eb",borderRadius:10,paddingVertical:12,paddingHorizontal:14,alignItems:"center",marginTop:3}, actionText:{color:"white",fontWeight:"700",textAlign:"center"}, secondary:{backgroundColor:"#e2e8f0"}, secondaryText:{color:"#0f172a"}, disabled:{opacity:.45}, input:{borderWidth:1,borderColor:"#cbd5e1",borderRadius:10,padding:12,color:"#0f172a",backgroundColor:"white"}, choice:{paddingVertical:12,borderTopWidth:1,borderTopColor:"#e2e8f0",gap:5}, wrap:{flexDirection:"row",flexWrap:"wrap",gap:8}, chip:{padding:9,borderRadius:8,backgroundColor:"#e2e8f0"}, chipActive:{backgroundColor:"#2563eb"}, chipActiveText:{color:"white",fontWeight:"700"}, error:{color:"#991b1b",backgroundColor:"#fee2e2",padding:12,borderRadius:8}, success:{color:"#166534",backgroundColor:"#dcfce7",padding:12,borderRadius:8} });
