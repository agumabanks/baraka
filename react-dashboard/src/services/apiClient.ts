import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

const API_BASE_URL = (import.meta.env.VITE_API_URL as string | undefined) || '/api';
const API_TIMEOUT = 30000;
const MAX_RETRIES = 3;

interface ApiRequestConfig extends AxiosRequestConfig {
  retryCount?: number;
  skipErrorHandling?: boolean;
}

class ApiClientService {
  private instance: AxiosInstance;
  private token: string | null = null;
  private refreshPromise: Promise<string> | null = null;

  constructor() {
    this.instance = axios.create({
      baseURL: API_BASE_URL,
      timeout: API_TIMEOUT,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    this.setupInterceptors();
    this.restoreToken();
  }

  private setupInterceptors(): void {
    // Request interceptor
    this.instance.interceptors.request.use(
      (config) => {
        if (this.token) {
          config.headers.Authorization = `Bearer ${this.token}`;
        }
        config.headers['X-Request-ID'] = this.generateRequestId();
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor
    this.instance.interceptors.response.use(
      (response) => response,
      async (error) => {
        const config = error.config as ApiRequestConfig;

        // Handle token expiry
        if (error.response?.status === 401 && !config.skipErrorHandling) {
          if (!this.refreshPromise) {
            this.refreshPromise = this.refreshAuthToken();
          }

          try {
            const newToken = await this.refreshPromise;
            config.headers.Authorization = `Bearer ${newToken}`;
            return this.instance(config);
          } catch (refreshError) {
            this.clearToken();
            window.location.href = '/login';
            return Promise.reject(refreshError);
          } finally {
            this.refreshPromise = null;
          }
        }

        // Handle rate limiting with retry
        if (error.response?.status === 429 && (config.retryCount || 0) < MAX_RETRIES) {
          config.retryCount = (config.retryCount || 0) + 1;
          const delay = Math.pow(2, config.retryCount) * 1000;
          await new Promise(resolve => setTimeout(resolve, delay));
          return this.instance(config);
        }

        return Promise.reject(error);
      }
    );
  }

  private generateRequestId(): string {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private restoreToken(): void {
    const stored = localStorage.getItem('auth_token');
    if (stored) {
      this.token = stored;
    }
  }

  public setToken(token: string): void {
    this.token = token;
    localStorage.setItem('auth_token', token);
    this.instance.defaults.headers.common.Authorization = `Bearer ${token}`;
  }

  public getToken(): string | null {
    return this.token;
  }

  public clearToken(): void {
    this.token = null;
    localStorage.removeItem('auth_token');
    delete this.instance.defaults.headers.common.Authorization;
  }

  private async refreshAuthToken(): Promise<string> {
    try {
      const response = await this.instance.post(
        '/v1/tokens/refresh',
        {},
        { skipErrorHandling: true } as ApiRequestConfig
      );

      if (response.data.success) {
        const newToken = response.data.data.token;
        this.setToken(newToken);
        return newToken;
      } else {
        throw new Error('Token refresh failed');
      }
    } catch (error) {
      this.clearToken();
      throw error;
    }
  }

  async get<T = any>(url: string, config?: ApiRequestConfig): Promise<AxiosResponse<T>> {
    return this.instance.get(url, config);
  }

  async post<T = any>(url: string, data?: any, config?: ApiRequestConfig): Promise<AxiosResponse<T>> {
    return this.instance.post(url, data, config);
  }

  async put<T = any>(url: string, data?: any, config?: ApiRequestConfig): Promise<AxiosResponse<T>> {
    return this.instance.put(url, data, config);
  }

  async patch<T = any>(url: string, data?: any, config?: ApiRequestConfig): Promise<AxiosResponse<T>> {
    return this.instance.patch(url, data, config);
  }

  async delete<T = any>(url: string, config?: ApiRequestConfig): Promise<AxiosResponse<T>> {
    return this.instance.delete(url, config);
  }

  async uploadFile(url: string, file: File, additionalData?: Record<string, any>): Promise<AxiosResponse> {
    const formData = new FormData();
    formData.append('file', file);
    
    if (additionalData) {
      Object.entries(additionalData).forEach(([key, value]) => {
        formData.append(key, String(value));
      });
    }

    return this.instance.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  }
}

export const apiClient = new ApiClientService();
export default apiClient;
