import { apiClient } from './client';
import { ApiResponse, User, UserAddress } from '../types';

export const authApi = {
  async register(data: { name: string; email: string; password: string; password_confirmation: string; phone?: string }) {
    const res = await apiClient.post<ApiResponse<{ user: User; token: string }>>('/auth/register', data);
    return res.data;
  },

  async login(data: { email?: string; login?: string; password: string }) {
    const payload = {
      login: data.login || data.email,
      email: data.email || data.login,
      password: data.password,
    };
    const res = await apiClient.post<ApiResponse<{ user: User; token: string }>>('/auth/login', payload);
    return res.data;
  },

  async getMe() {
    const res = await apiClient.get<ApiResponse<User>>('/auth/me');
    return res.data;
  },

  async logout() {
    const res = await apiClient.post<ApiResponse<null>>('/auth/logout');
    return res.data;
  },

  async getAddresses() {
    const res = await apiClient.get<ApiResponse<UserAddress[]>>('/addresses');
    return res.data;
  },

  async createAddress(data: Omit<UserAddress, 'id' | 'user_id'>) {
    const res = await apiClient.post<ApiResponse<UserAddress>>('/addresses', data);
    return res.data;
  },

  async updateAddress(id: number, data: Partial<Omit<UserAddress, 'id' | 'user_id'>>) {
    const res = await apiClient.put<ApiResponse<UserAddress>>(`/addresses/${id}`, data);
    return res.data;
  },

  async deleteAddress(id: number) {
    const res = await apiClient.delete<ApiResponse<null>>(`/addresses/${id}`);
    return res.data;
  },
};
