export type CareRecordStatus =
  | "COMPLETED"
  | "OBSERVED"
  | "NEEDS_ATTENTION";

export interface CareRecordUser {
  id: number;
  full_name: string;
}

export interface CareRecordResident {
  id: number;
  full_name: string;
}

export interface CareRecord {
  id: number;
  resident_id: number;
  care_type: string;
  title: string;
  notes?: string | null;
  care_status: CareRecordStatus;
  recorded_at: string;
  recorded_by?: number | null;
  recorder?: CareRecordUser | null;
  resident?: CareRecordResident | null;
}

export interface CareRecordsListResponse {
  resident: CareRecordResident;
  care_records: CareRecord[];
}

export interface CareRecordInput {
  care_type: string;
  title: string;
  notes?: string | null;
  care_status: CareRecordStatus;
  recorded_at?: string;
}

export interface CareRecordMutationResponse {
  message: string;
  care_record: CareRecord;
}