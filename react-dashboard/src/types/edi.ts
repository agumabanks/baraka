/**
 * TypeScript type definitions for EDI (Electronic Data Interchange) functionality
 * Based on Laravel API endpoints for EDI transaction management
 */

export interface EDiTransaction {
  id: string;
  transaction_type: '850' | '856' | '997' | '810' | '820' | '214';
  document_type: string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'acknowledged';
  sender: string;
  receiver: string;
  trading_partner_id?: string;
  control_number: string;
  version: string;
  transaction_set: string;
  payload: Record<string, unknown>;
  acknowledgment_id?: string;
  error_message?: string;
  processing_started_at?: string;
  processing_completed_at?: string;
  created_at: string;
  updated_at: string;
  retry_attempts?: number;
  last_error_code?: string;
  last_error_message?: string;
  last_error_at?: string;
  acknowledgment?: EDIAcknowledgment;
}

export interface EDIAcknowledgment {
  id: string;
  transaction_id: string;
  acknowledgment_type: '997' | '999' | '999-A' | '999-B';
  status: 'pending' | 'generated' | 'sent' | 'accepted' | 'rejected';
  functional_group_control_number: string;
  control_number: string;
  error_code?: string;
  error_description?: string;
  technical_ack_code?: string;
  implementation_ack_code?: string;
  created_at: string;
  sent_at?: string;
}

export interface EDITransactionFilters {
  transaction_type?: '850' | '856' | '997' | '810' | '820' | '214';
  status?: 'pending' | 'processing' | 'completed' | 'failed' | 'acknowledged';
  date_range?: {
    from: string;
    to: string;
  };
  trading_partner_id?: string;
  control_number?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface EDITransactionSummary {
  total: number;
  pending: number;
  processing: number;
  completed: number;
  failed: number;
  acknowledged: number;
  today: number;
  this_week: number;
  this_month: number;
}

export interface EDIBatchSubmission {
  id: string;
  name: string;
  transaction_count: number;
  total_size_bytes: number;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  submitted_by: string;
  submitted_at: string;
  completed_at?: string;
  error_message?: string;
  transactions: EDiTransaction[];
}

export interface EDITradingPartner {
  id: string;
  name: string;
  isa_id: string;
  gs_id: string;
  is_active: boolean;
  connection_type: 'as2' | 'sftp' | 'api';
  endpoint_url?: string;
  certificate_thumbprint?: string;
  contact_email: string;
  contact_phone?: string;
  last_connected_at?: string;
  connection_status: 'connected' | 'disconnected' | 'error';
  error_message?: string;
}

export interface EDIDocumentType {
  code: string;
  name: string;
  description: string;
  category: 'order' | 'shipping' | 'invoice' | 'payment' | 'other';
  is_active: boolean;
  required_segments: string[];
  optional_segments: string[];
}

export interface EDiTransactionDetail extends EDiTransaction {
  acknowledgment?: EDIAcknowledgment;
  trading_partner?: EDITradingPartner;
  processing_logs: EDiProcessingLog[];
  raw_payload: string;
  parsed_segments: EDISegment[];
}

export interface EDiProcessingLog {
  id: string;
  transaction_id: string;
  level: 'info' | 'warning' | 'error';
  message: string;
  code?: string;
  segment_position?: number;
  created_at: string;
}

export interface EDISegment {
  tag: string;
  elements: string[];
  position: number;
  is_valid: boolean;
  validation_errors?: string[];
}

export interface EDITransactionValidation {
  is_valid: boolean;
  errors: EDISegmentError[];
  warnings: string[];
  total_segments: number;
  valid_segments: number;
}

export interface EDISegmentError {
  position: number;
  segment_tag: string;
  error_code: string;
  error_description: string;
  element_position?: number;
}

export interface EDISubmissionHistory {
  id: string;
  submission_type: 'single' | 'batch';
  file_name: string;
  file_size: number;
  record_count: number;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  submitted_by: string;
  submitted_at: string;
  processed_at?: string;
  error_message?: string;
  success_count: number;
  error_count: number;
}

export interface EDIPerformanceMetrics {
  overview?: {
    total_transactions: number;
    success_rate: number;
    average_processing_time: number;
    acknowledged_rate: number;
  };
  transaction_volume_chart?: Array<{
    date: string;
    count: number;
    success?: number;
    failed?: number;
  }>;
  processing_time_chart?: Array<{
    date: string;
    average_time: number;
    p50?: number;
    p95?: number;
  }>;
  document_type_performance?: Array<{
    document_code: string;
    document_type: string;
    count: number;
    success_rate: number;
    average_processing_time: number;
  }>;
  response_time_chart: Array<{
    date: string;
    average_time: number;
    p50: number;
    p95: number;
  }>;
  throughput_chart: Array<{
    date: string;
    sent: number;
    received: number;
    acknowledged: number;
  }>;
  error_rate_chart: Array<{
    date: string;
    error_rate: number;
  }>;
  document_type_breakdown: Array<{
    document_type: string;
    count: number;
    success_rate: number;
  }>;
  trading_partner_performance: Array<{
    partner_name: string;
    sent: number;
    received: number;
    success_rate: number;
    average_response_time: number;
  }>;
}

export interface EDiProvider {
  id: string;
  name: string;
  type: 'van' | 'value-added-network' | 'direct';
  connection_info: {
    host?: string;
    port?: number;
    username?: string;
    certificate_path?: string;
    timeout?: number;
  };
  supported_document_types: string[];
  is_active: boolean;
  configuration: Record<string, unknown>;
}

export interface EDIControlNumber {
  id: string;
  control_type: 'isa' | 'gs' | 'st' | 'se';
  current_number: number;
  last_assigned: string;
  is_active: boolean;
  description: string;
}

export interface PaginatedEdiTransactions {
  data: EDiTransaction[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  summary: EDITransactionSummary;
}

export interface EDIFilterOptions {
  transaction_types: Array<{ value: string; label: string }>;
  statuses: Array<{ value: string; label: string }>;
  document_types: Array<{ value: string; label: string }>;
  trading_partners: Array<{ value: string; label: string }>;
}