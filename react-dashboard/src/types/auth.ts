export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  status: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  last_login_at: string | null;
  last_login_ip: string | null;
  email_verified_at: string | null;
  
  role?: {
    id: number;
    name: string;
    permissions: string[];
  };
  
  branch?: {
    id: number;
    name: string;
    code: string;
    type: string;
    address: string;
    city: string;
    country: string;
    phone: string;
    email: string;
    is_hub: boolean;
    status: number;
  };
  
  permissions: string[];
  abilities: {
    can_manage_users: boolean;
    can_manage_shipments: boolean;
    can_manage_branches: boolean;
    can_view_reports: boolean;
    can_manage_system: boolean;
    can_merchant_portal: boolean;
    is_system_admin: boolean;
    is_branch_manager: boolean;
    can_create_shipments: boolean;
    can_update_shipments: boolean;
    can_delete_shipments: boolean;
    can_assign_shipments: boolean;
    can_manage_financial: boolean;
  };
  
  // Profile information
  profile?: {
    first_name: string;
    last_name: string;
    full_name: string;
    initials: string;
    timezone: string;
    preferred_language: string;
    profile_data: any;
  };
  
  // Statistics
  stats?: {
    activities_count: number;
    recent_activities: Array<{
      title: string;
      message: string;
      type: string;
      created_at: string;
    }>;
    login_count: number;
    avg_session_duration: string;
    active_sessions: number;
  };
  
  // Tokens
  tokens?: {
    total: number;
    active: number;
    recent: Array<{
      name: string;
      abilities: string[];
      created_at: string;
      last_used_at: string | null;
      expires_at: string;
      revoked_at: string | null;
      is_current: boolean;
    }>;
  };
  
  // Notifications
  notifications?: {
    unread_count: number;
    total_count: number;
    recent: Array<{
      id: number;
      type: string;
      title: string;
      message: string;
      read_at: string | null;
      created_at: string;
    }>;
  };
  
  // System information
  system: {
    permissions: User['abilities'];
    is_system_admin: boolean;
    is_branch_manager: boolean;
    can_merchant_portal: boolean;
    created_at_formatted: string;
    updated_at_formatted: string;
  };
}

export interface LoginForm {
  email: string;
  password: string;
  remember?: boolean;
  device_name?: string;
}

export interface RegisterForm {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  role_id?: number;
  device_name?: string;
}

export interface PasswordResetForm {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface ProfileUpdateForm {
  name?: string;
  email?: string;
  phone?: string;
  profile_data?: Record<string, any>;
}

export interface Preferences {
  theme: 'light' | 'dark';
  language: 'en' | 'fr' | 'sw';
  timezone: string;
  notifications: {
    email: boolean;
    push: boolean;
    sms: boolean;
    dashboard: boolean;
    shipments: boolean;
    reports: boolean;
  };
}

export interface LoginResponse {
  success: boolean;
  type: string;
  message: string;
  data: {
    user: User;
    token: string;
    token_type: string;
    expires_at: string;
    permissions: string[];
  };
  timestamp: string;
}

export interface ErrorResponse {
  success: false;
  type: string;
  message: string;
  errors?: Record<string, string[]> | string;
  code?: string;
  timestamp: string;
  debug?: {
    file: string;
    line: number;
    trace: string[];
  };
}

export interface ApiState {
  isLoading: boolean;
  error: string | null;
  lastError?: ErrorResponse;
  user: User | null;
  permissions: string[];
  isAuthenticated: boolean;
}

export type ApiResponse<T = any> = {
  success: boolean;
  type: string;
  message: string;
  data?: T;
  errors?: Record<string, string[]> | string;
  timestamp: string;
};

export interface PaginatedResponse<T> {
  success: boolean;
  type: string;
  message: string;
  data: {
    items: T[];
    pagination: {
      current_page: number;
      total_pages: number;
      total_items: number;
      per_page: number;
    };
  };
  timestamp: string;
}

export interface WebSocketMessage {
  type: string;
  data: any;
  timestamp: string;
  channel: string;
}
