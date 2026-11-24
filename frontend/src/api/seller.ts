import { apiClient } from './client';
import { ApiResponse, Store, Product } from '../types';

export const sellerApi = {
  async getApplication() {
    const res = await apiClient.get<ApiResponse<any>>('/seller/application');
    return res.data;
  },

  async applySeller(data: { id_card_number: string; business_type: string; notes?: string }) {
    const res = await apiClient.post<ApiResponse<any>>('/seller/applications', data);
    return res.data;
  },

  async getStore() {
    const res = await apiClient.get<ApiResponse<Store>>('/seller/store');
    return res.data;
  },

  async createOrUpdateStore(data: { name: string; slug: string; city: string; description?: string }) {
    const res = await apiClient.post<ApiResponse<Store>>('/seller/store', data);
    return res.data;
  },

  async getMyProducts() {
    const res = await apiClient.get<ApiResponse<Product[]>>('/seller/products');
    return res.data;
  },

  async createProduct(data: any) {
    const res = await apiClient.post<ApiResponse<Product>>('/seller/products', data);
    return res.data;
  },
};
