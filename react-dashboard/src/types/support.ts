export interface SupportSelectOption {
  value: string;
  label: string;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  last_page: number;
  total: number;
}

export type SupportStatus = 'pending' | 'processing' | 'resolved' | 'closed';
export type SupportPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface Support {
  id: number;
  subject: string;
  userName: string;
  userEmail: string;
  userMobile: string | null;
  department: string;
  service: string | null;
  priority: SupportPriority;
  description: string;
  date: string;
  status?: SupportStatus;
  created_at?: string;
  updated_at?: string;
}

export interface SupportChat {
  id: number;
  support_id: number;
  user_id: number;
  user_name?: string;
  attached_file?: number | null;
  message: string;
  created_at: string;
  updated_at?: string;
}

export interface SupportDetail {
  support: Support;
  chats: SupportChat[];
}

export interface Department {
  id: number;
  title: string;
}

export interface SupportCreateMeta {
  departments: Department[];
}

export interface SupportEditMeta {
  support: Support;
  departments: Department[];
}

export interface SupportSummary {
  totals: {
    all: number;
    by_status: Partial<Record<SupportStatus, number>>;
  };
  by_priority: Partial<Record<SupportPriority, number>>;
  generated_at: string;
}

export interface SupportFilters {
  status_options: SupportSelectOption[];
  priority_options: SupportSelectOption[];
  department_options: SupportSelectOption[];
}

export interface SupportListResponse {
  supports: Support[];
  pagination?: PaginationMeta;
  summary?: SupportSummary;
  filters?: SupportFilters;
}

export interface SupportFormData {
  department_id: string;
  service: string;
  priority: SupportPriority;
  subject: string;
  description: string;
  attached_file?: File | null;
}

export interface SupportReplyData {
  support_id: number;
  message: string;
  attached_file?: File | null;
}
