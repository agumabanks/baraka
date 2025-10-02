import axios from 'axios';
import type { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

const DEFAULT_DEV_API_BASE = 'http://localhost:8000/api';
const DEFAULT_PROD_API_BASE = 'https://baraka.sanaa.ug/api';

const sanitizeBaseUrl = (url: string): string => url.replace(/\/$/, '');

const resolveApiBaseUrl = (): string => {
  const envUrl = import.meta.env.VITE_API_URL as string | undefined;

  if (envUrl && envUrl.trim().length > 0) {
    return sanitizeBaseUrl(envUrl);
  }

  if (typeof window !== 'undefined') {
    const { hostname } = window.location;

    if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '::1') {
      return DEFAULT_DEV_API_BASE;
    }

    return DEFAULT_PROD_API_BASE;
  }

  return import.meta.env.PROD ? DEFAULT_PROD_API_BASE : DEFAULT_DEV_API_BASE;
};

const API_BASE_URL = resolveApiBaseUrl();

const resolveSanctumUrl = (): string => {
  const envSanctum = import.meta.env.VITE_SANCTUM_URL as string | undefined;

  if (envSanctum && envSanctum.trim().length > 0) {
    return envSanctum;
  }

  try {
    const resolved = new URL(API_BASE_URL);
    return `${resolved.origin}/sanctum/csrf-cookie`;
  } catch (error) {
    if (typeof window !== 'undefined') {
      return `${window.location.origin.replace(/\/$/, '')}/sanctum/csrf-cookie`;
    }
    return `${DEFAULT_PROD_API_BASE.replace(/\/$/, '')}/sanctum/csrf-cookie`;
  }
};

const SANCTUM_URL = resolveSanctumUrl();

// Create axios instance with default config
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'apiKey': '123456rx-ecourier123456', // Required API key for backend
  },
});

if (typeof window !== 'undefined') {
  const storedLocale = window.localStorage.getItem('dashboard_locale');
  if (storedLocale) {
    api.defaults.headers.common['Accept-Language'] = storedLocale;
  }
}

// Request interceptor to add auth token
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token');
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear token and redirect to login
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Get CSRF token from Laravel Sanctum
export const getCsrfToken = async (): Promise<void> => {
  await axios.get(SANCTUM_URL, {
    withCredentials: true,
  });
};

// Auth API functions
export const authApi = {
  login: async (email: string, password: string) => {
    await getCsrfToken();
    const response = await api.post('/auth/login', { email, password });
    return response.data;
  },

  register: async (name: string, email: string, password: string, password_confirmation: string) => {
    await getCsrfToken();
    const response = await api.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation,
    });
    return response.data;
  },

  logout: async () => {
    const response = await api.post('/auth/logout');
    return response.data;
  },

  getUser: async () => {
    const response = await api.get('/auth/user');
    return response.data;
  },
};

// Dashboard API functions
export const dashboardApi = {
  getKPIs: async () => {
    const response = await api.get('/v10/dashboard/kpis');
    return response.data;
  },

  getCharts: async () => {
    const response = await api.get('/v10/dashboard/charts');
    return response.data;
  },

  getWorkflowQueue: async () => {
    const response = await api.get('/v10/dashboard/workflow-queue');
    return response.data;
  },

  getData: async () => {
    const response = await api.get('/v10/dashboard/data');
    return response.data;
  },
};

export const navigationApi = {
  getAdminNavigation: async () => {
    const response = await api.get('/navigation/admin');
    return response.data;
  },
};

export default api;
