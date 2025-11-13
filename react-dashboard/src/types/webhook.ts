/**
 * TypeScript type definitions for Webhook Management functionality
 * Based on Laravel API endpoints for webhook administration
 */

export interface WebhookEndpoint {
  id: string;
  name: string;
  url: string;
  events: string[];
  is_active: boolean;
  active?: boolean;
  secret_key: string;
  created_at: string;
  updated_at: string;
  last_triggered_at?: string;
  delivery_stats?: WebhookDeliveryStats;
  last_test_at?: string;
  success_rate?: number;
  description?: string;
  last_delivery_error?: string;
  retry_policy?: {
    max_attempts: number;
    interval_seconds: number;
    strategy: string;
    backoff_factor?: number;
    initial_delay?: number;
    backoff_multiplier?: number;
    max_delay?: number;
  };
  last_delivery_at?: string;
  failure_count?: number;
}

export interface WebhookDelivery {
  id: string;
  webhook_endpoint_id: string;
  event_type: string;
  payload: Record<string, unknown>;
  response_status?: number;
  response_body?: string;
  error_message?: string;
  duration_ms?: number;
  retry_count: number;
  status: 'pending' | 'success' | 'failed' | 'retrying';
  created_at: string;
  completed_at?: string;
}

export interface WebhookDeliveryStats {
  total_deliveries: number;
  successful_deliveries: number;
  failed_deliveries: number;
  average_response_time: number;
  last_delivery_at?: string;
  success_rate: number;
}

export interface WebhookEvent {
  id: string;
  name: string;
  description: string;
  category: string;
  is_active: boolean;
  created_at: string;
}

export interface WebhookDeliveryFilters {
  status?: 'pending' | 'success' | 'failed' | 'retrying';
  event_type?: string;
  date_range?: {
    from: string;
    to: string;
  };
  webhook_endpoint_id?: string;
  search?: string;
}

export interface WebhookEndpointForm {
  name: string;
  url: string;
  events: string[];
  is_active: boolean;
  secret_key?: string;
}

export interface WebhookDeliverySummary {
  total: number;
  pending: number;
  success: number;
  failed: number;
  retrying: number;
}

export interface WebhookHealthOverview {
  total_endpoints: number;
  active_endpoints: number;
  success_rate: number;
  average_response_time: number;
  failed_deliveries: number;
  pending_deliveries: number;
}

export interface WebhookHealthComponent {
  name: string;
  status: 'operational' | 'degraded' | 'down';
}

export interface WebhookHealthStatus {
  overall_status: 'healthy' | 'degraded' | 'down';
  last_check: string;
  endpoint_count: number;
  active_endpoints: number;
  recent_deliveries: number;
  error_rate: number;
  average_response_time: number;
  overview: WebhookHealthOverview;
  delivery_volume_chart: Array<{
    date: string;
    count: number;
    success: number;
    failed: number;
  }>;
  response_time_chart: Array<{
    date: string;
    average_time: number;
    p50: number;
    p95: number;
  }>;
  components: WebhookHealthComponent[];
  alerts: WebhookAlert[];
}

export interface WebhookAlert {
  id: string;
  type: 'error_rate' | 'response_time' | 'endpoint_down' | 'delivery_failure';
  severity: 'low' | 'medium' | 'high' | 'critical' | 'warning';
  message: string;
  endpoint_id?: string;
  created_at: string;
  resolved_at?: string;
  title?: string;
  timestamp?: string;
}

export interface WebhookTestPayload {
  event_type: string;
  payload: Record<string, unknown>;
}

export interface WebhookTestResult {
  success: boolean;
  response_status: number;
  response_body: string;
  duration_ms: number;
  error_message?: string;
  timestamp: string;
}

export interface WebhookMetrics {
  delivery_chart: Array<{
    date: string;
    total: number;
    success: number;
    failed: number;
  }>;
  response_time_chart: Array<{
    date: string;
    average_time: number;
    p50: number;
    p95: number;
  }>;
  error_rate_chart: Array<{
    date: string;
    error_rate: number;
  }>;
  top_events: Array<{
    event_type: string;
    count: number;
    success_rate: number;
  }>;
}

export interface PaginatedWebhookDeliveries {
  data: WebhookDelivery[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface WebhookEndpointFilters {
  status: 'all' | 'active' | 'inactive';
  search: string;
}
