import { apiClient } from './client';
import { ApiResponse, CheckoutRequest, Order, ShippingServiceOption } from '../types';

export const checkoutApi = {
  async getRates(params: { store_id: number; destination_city: string; total_weight: number }) {
    const res = await apiClient.get<ApiResponse<ShippingServiceOption[]>>('/shipping/rates', { params });
    return res.data;
  },

  async processCheckout(payload: CheckoutRequest) {
    const res = await apiClient.post<ApiResponse<Order>>('/checkout', payload);
    return res.data;
  },
};
