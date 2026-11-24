import { apiClient } from './client';
import { ApiResponse, Order, SellerOrder, Shipment } from '../types';

export const ordersApi = {
  async getMyOrders(params?: { status?: string; page?: number }) {
    const res = await apiClient.get<ApiResponse<Order[]>>('/orders', { params });
    return res.data;
  },

  async getOrderDetail(id: number | string) {
    const res = await apiClient.get<ApiResponse<Order>>(`/orders/${id}`);
    return res.data;
  },

  // Seller orders endpoints
  async getSellerOrders(params?: { status?: string; page?: number }) {
    const res = await apiClient.get<ApiResponse<SellerOrder[]>>('/seller/orders', { params });
    return res.data;
  },

  async updateSellerOrderStatus(sellerOrderId: number, status: string) {
    const res = await apiClient.put<ApiResponse<SellerOrder>>(`/seller/orders/${sellerOrderId}/status`, { status });
    return res.data;
  },

  async shipSellerOrder(sellerOrderId: number, payload: { shipping_service_id: number; tracking_number?: string }) {
    const res = await apiClient.post<ApiResponse<Shipment>>(`/seller/orders/${sellerOrderId}/ship`, payload);
    return res.data;
  },
};
