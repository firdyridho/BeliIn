import React from 'react';
import { OrderStatus, SellerOrderStatus, PaymentStatus, ShipmentStatus } from '../../types';

interface StatusBadgeProps {
  type: 'order' | 'seller_order' | 'payment' | 'shipment';
  status: OrderStatus | SellerOrderStatus | PaymentStatus | ShipmentStatus | string;
  size?: 'sm' | 'md';
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ type, status, size = 'sm' }) => {
  let label = status;
  let colorClasses = 'bg-slate-100 text-slate-700 border-slate-200';

  const sizeClasses = size === 'sm' ? 'px-2.5 py-0.5 text-xs font-medium' : 'px-3 py-1 text-sm font-medium';

  if (type === 'order') {
    switch (status) {
      case 'pending':
        label = 'Menunggu Pembayaran';
        colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
        break;
      case 'paid':
        label = 'Dibayar';
        colorClasses = 'bg-blue-50 text-blue-700 border-blue-200';
        break;
      case 'processing':
        label = 'Diproses';
        colorClasses = 'bg-indigo-50 text-indigo-700 border-indigo-200';
        break;
      case 'shipped':
        label = 'Dikirim';
        colorClasses = 'bg-purple-50 text-purple-700 border-purple-200';
        break;
      case 'delivered':
        label = 'Selesai';
        colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        break;
      case 'cancelled':
        label = 'Dibatalkan';
        colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
        break;
    }
  } else if (type === 'seller_order') {
    switch (status) {
      case 'pending':
        label = 'Menunggu Konfirmasi';
        colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
        break;
      case 'accepted':
        label = 'Diterima Toko';
        colorClasses = 'bg-blue-50 text-blue-700 border-blue-200';
        break;
      case 'processing':
        label = 'Sedang Dikemas';
        colorClasses = 'bg-indigo-50 text-indigo-700 border-indigo-200';
        break;
      case 'shipped':
        label = 'Diserahkan ke Kurir';
        colorClasses = 'bg-purple-50 text-purple-700 border-purple-200';
        break;
      case 'delivered':
        label = 'Tiba di Tujuan';
        colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        break;
      case 'cancelled':
        label = 'Dibatalkan';
        colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
        break;
    }
  } else if (type === 'payment') {
    switch (status) {
      case 'pending':
        label = 'Belum Dibayar';
        colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
        break;
      case 'settled':
        label = 'Lunas';
        colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        break;
      case 'expired':
        label = 'Kedaluwarsa';
        colorClasses = 'bg-slate-100 text-slate-600 border-slate-200';
        break;
      case 'failed':
        label = 'Gagal';
        colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
        break;
    }
  } else if (type === 'shipment') {
    switch (status) {
      case 'pending':
        label = 'Menunggu Pickup';
        colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
        break;
      case 'picked_up':
        label = 'Kurir Telah Pickup';
        colorClasses = 'bg-blue-50 text-blue-700 border-blue-200';
        break;
      case 'in_transit':
        label = 'Dalam Pengiriman AnterIn';
        colorClasses = 'bg-orange-50 text-orange-700 border-orange-200';
        break;
      case 'out_for_delivery':
        label = 'Kurir Mengantar ke Lokasi';
        colorClasses = 'bg-purple-50 text-purple-700 border-purple-200';
        break;
      case 'delivered':
        label = 'Terkirim';
        colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        break;
      case 'failed':
        label = 'Gagal Kirim';
        colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
        break;
    }
  }

  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full border ${sizeClasses} ${colorClasses}`}>
      <span className="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
      {label}
    </span>
  );
};
