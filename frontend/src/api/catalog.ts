import { apiClient } from './client';
import { ApiResponse, Category, Product } from '../types';

export const catalogApi = {
  async getCategories() {
    const res = await apiClient.get<ApiResponse<Category[]>>('/categories');
    return res.data;
  },

  async getProducts(params?: { category_id?: number; search?: string; q?: string; page?: number; per_page?: number }) {
    const qParams: Record<string, any> = { ...params };
    if (params?.search && !params?.q) {
      qParams.q = params.search;
    }
    const res = await apiClient.get<ApiResponse<Product[]>>('/products', { params: qParams });
    return res.data;
  },

  async getProduct(idOrSlug: number | string) {
    const res = await apiClient.get<ApiResponse<Product>>(`/products/${idOrSlug}`);
    return res.data;
  },
};
