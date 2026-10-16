import api from "./api";
export type Resident = {id:number;full_name:string;status:string};
export type OperationRecord = Record<string,any> & {id:number;resident_id:number;status:string;resident?:Resident};
export type Kind = "visitors"|"home-leaves"|"parcels";
const keys:Record<Kind,string>={visitors:"visitors","home-leaves":"home_leaves",parcels:"parcels"};
function unpack<T>(data:any,key:string):T[]{const value=Array.isArray(data)?data:data?.[key]??data?.data;return Array.isArray(value)?value:Array.isArray(value?.data)?value.data:[];}
export async function loadOperations(kind:Kind){const [a,b]=await Promise.all([api.get(`/${kind}`),api.get("/residents")]);return {records:unpack<OperationRecord>(a.data,keys[kind]),residents:unpack<Resident>(b.data,"residents")};}
export async function createOperation(kind:Kind,payload:Record<string,any>){return api.post(`/${kind}`,payload);}
export async function updateOperation(kind:Kind,id:number,payload:Record<string,any>){return api.put(`/${kind}/${id}`,payload);}
export async function deleteOperation(kind:Kind,id:number){return api.delete(`/${kind}/${id}`);}
export async function finishOperation(kind:Kind,id:number,payload:Record<string,any>){const suffix=kind==="visitors"?"checkout":kind==="home-leaves"?"return":"collect";return api.post(`/${kind}/${id}/${suffix}`,payload);}
export async function notifyParcel(id:number){return api.post(`/parcels/${id}/notify`);}
export function operationError(error:unknown){const e=error as {response?:{data?:{message?:string;errors?:Record<string,string[]>}};message?:string};const d=e?.response?.data;return (d?.errors?Object.values(d.errors).flat()[0]:undefined)||d?.message||e?.message||"Request failed";}
