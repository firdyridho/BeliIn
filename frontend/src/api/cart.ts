import { apiClient } from './client';
import { ApiResponse, Cart } from '../types';

export const cartApi = {
  async getCart() {
    const res = await apiClient.get<ApiResponse<Cart>>('/cart');
    return res.data;
  },

  async addItem(data: { product_variant_id: number; quantity: number }) {
    const res = await apiClient.post<ApiResponse<Cart>>('/cart/items', {
      variant_id: data.product_variant_id,
      product_variant_id: data.product_variant_id,
      quantity: data.quantity,
    });
    return res.data;
  },

  async updateItem(itemId: number, data: { quantity: number }) {
    const res = await apiClient.put<ApiResponse<Cart>>(`/cart/items/${itemId}`, data);
    return res.data;
  },

  async removeItem(itemId: number) {
    const res = await apiClient.delete<ApiResponse<Cart>>(`/cart/items/${itemId}`);
    return res.data;
  },
};
