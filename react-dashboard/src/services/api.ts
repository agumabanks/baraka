import axios from 'axios';
import type { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Create axios instance with default config
const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

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
  await axios.get(`${API_URL.replace('/api', '')}/sanctum/csrf-cookie`, {
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

export default api;