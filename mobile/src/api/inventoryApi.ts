import api from "./api";

export type Medicine = { id: number; medicine_name: string; category?: string | null; dosage?: string | null; unit?: string | null; supplier?: string | null };
export type Stock = { id: number; medication_id: number; medication?: Medicine | null; quantity: number; minimum_stock: number; expiry_date?: string | null; location?: string | null; stock_status?: string | null; needs_attention?: boolean };
export type StockTransaction = { id: number; transaction_type: string; quantity: number; reference?: string | null; transaction_date?: string | null; resident?: {full_name?: string} | null; performedBy?: {full_name?: string} | null; performed_by?: {full_name?: string} | number | null };
export type MedicineForm = {medicine_name: string; category: string; dosage: string; unit: string; supplier: string};
export type StockForm = {medication_id: string; quantity: string; minimum_stock: string; expiry_date: string; location: string};
export type MovementType = "STOCK_IN" | "STOCK_OUT" | "ADJUSTMENT_IN" | "ADJUSTMENT_OUT";
const unpack = <T>(data: unknown, key: string): T[] => {
  if (Array.isArray(data)) return data as T[];
  if (data && typeof data === "object") {
    const record = data as Record<string, unknown>;
    const value = record[key] ?? record.data;
    if (Array.isArray(value)) return value as T[];
    if (value && typeof value === "object" && Array.isArray((value as {data?: unknown}).data)) return (value as {data: T[]}).data;
  }
  return [];
};
export async function getMedicines() {const response = await api.get("/medications"); return unpack<Medicine>(response.data, "medications");}
export async function getStock() {const response = await api.get("/medicine-inventory"); return unpack<Stock>(response.data, "inventory");}
export async function addMedicine(form: MedicineForm) {return api.post("/medications", {medicine_name:form.medicine_name.trim(),category:form.category.trim()||null,dosage:form.dosage.trim()||null,unit:form.unit.trim()||null,supplier:form.supplier.trim()||null});}
export async function editMedicine(id: number, form: MedicineForm) {return api.put(`/medications/${id}`, {medicine_name:form.medicine_name.trim(),category:form.category.trim()||null,dosage:form.dosage.trim()||null,unit:form.unit.trim()||null,supplier:form.supplier.trim()||null});}
export async function removeMedicine(id: number) {return api.delete(`/medications/${id}`);}
export async function createStock(form: StockForm) {return api.post("/medicine-inventory", {medication_id:Number(form.medication_id),quantity:Number(form.quantity),minimum_stock:form.minimum_stock.trim()?Number(form.minimum_stock):10,expiry_date:form.expiry_date.trim()||null,location:form.location.trim()||null});}
export async function editStock(id: number, form: StockForm) {return api.put(`/medicine-inventory/${id}`, {minimum_stock:form.minimum_stock.trim()?Number(form.minimum_stock):0,expiry_date:form.expiry_date.trim()||null,location:form.location.trim()||null});}
export async function moveStock(id: number, type: MovementType, quantity: number, reference: string) {return api.post(`/medicine-inventory/${id}/stock-adjustment`, {transaction_type:type,quantity,reference:reference.trim()||null});}
export async function getTransactions(id: number) {const response = await api.get(`/medicine-inventory/${id}/transactions`); return unpack<StockTransaction>(response.data,"transactions");}
export function inventoryError(error: unknown, fallback: string) {const data=(error as {response?:{data?:{message?:string;errors?:Record<string,string[]>}};message?:string})?.response?.data; const first=data?.errors?Object.values(data.errors).flat()[0]:undefined;return first||data?.message||(error as {message?:string})?.message||fallback;}
