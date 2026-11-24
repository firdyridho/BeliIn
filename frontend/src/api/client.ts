import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

// Request interceptor: Attach Sanctum Bearer Token and optional Idempotency Key
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('beliin_token');
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Auto-generate idempotency key for state-modifying requests if not provided
    if (['post', 'put', 'patch'].includes(config.method?.toLowerCase() || '') && !config.headers['Idempotency-Key']) {
      if (config.url?.includes('/checkout') || config.url?.includes('/payments')) {
        config.headers['Idempotency-Key'] = `fe-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
      }
    }

    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor: Extract errors and handle 401
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    if (error.response?.status === 401) {
      // Clear token if expired or unauthorized
      localStorage.removeItem('beliin_token');
      localStorage.removeItem('beliin_user');
      // If not on login page, can trigger auth reset
      window.dispatchEvent(new Event('auth:unauthorized'));
    }
    return Promise.reject(error);
  }
);

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data;
    if (data?.message) {
      return data.message;
    }
    if (data?.errors) {
      const firstKey = Object.keys(data.errors)[0];
      if (firstKey && data.errors[firstKey].length > 0) {
        return data.errors[firstKey][0];
      }
    }
  }
  if (error instanceof Error) {
    return error.message;
  }
  return 'Terjadi kesalahan sistem. Silakan coba lagi.';
}
