import { apiClient } from './client';
import { ApiResponse, Payment } from '../types';

export const paymentApi = {
  async getPayment(paymentReference: string) {
    const res = await apiClient.get<ApiResponse<Payment>>(`/payments/${paymentReference}`);
    return res.data;
  },

  async simulateWebhook(data: { payment_reference: string; action: 'settle' | 'expire' | 'fail' }) {
    const res = await apiClient.post<ApiResponse<Payment>>(`/payments/${data.payment_reference}/simulate-pay`);
    return res.data;
  },
};
