import { apiClient } from './client';
import { ApiResponse, Shipment } from '../types';

export const trackingApi = {
  async trackShipment(trackingNumber: string) {
    const res = await apiClient.get<ApiResponse<Shipment>>(`/shipments/track/${trackingNumber}`);
    return res.data;
  },

  async advanceEvent(trackingNumber: string, payload: { status: string; location: string; description: string }) {
    const res = await apiClient.post<ApiResponse<Shipment>>(`/shipments/track/${trackingNumber}/events`, payload);
    return res.data;
  },
};
