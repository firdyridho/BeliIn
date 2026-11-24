import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { ArrowLeft, MapPin, CreditCard, Truck, Store, ExternalLink } from 'lucide-react';
import { ordersApi } from '../api/orders';
import { Order } from '../types';
import { formatRupiah, formatDate } from '../utils/formatters';
import { StatusBadge } from '../components/common/StatusBadge';
import { TrackingTimeline } from '../components/tracking/TrackingTimeline';

export const OrderDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadDetail() {
      if (!id) return;
      try {
        const res = await ordersApi.getOrderDetail(id);
        setOrder(res.data);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }
    loadDetail();
  }, [id]);

  if (loading) {
    return (
      <div className="max-w-5xl mx-auto py-12 px-4 space-y-6 animate-pulse">
        <div className="h-6 bg-slate-200 rounded w-1/4"></div>
        <div className="h-64 bg-slate-200 rounded-3xl"></div>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="max-w-xl mx-auto py-16 px-4 text-center">
        <h2 className="text-lg font-bold text-slate-800">Pesanan Tidak Ditemukan</h2>
        <Link to="/orders" className="mt-4 inline-block text-xs font-bold text-blue-600 underline">
          Kembali ke Daftar Pesanan
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto py-6 space-y-8 pb-20">
      {/* Top Header */}
      <div className="flex items-center justify-between">
        <button
          onClick={() => navigate('/orders')}
          className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900"
        >
          <ArrowLeft className="w-4 h-4" /> Kembali
        </button>
        <div className="flex items-center gap-2">
          <StatusBadge type="order" status={order.status} size="md" />
        </div>
      </div>

      <div className="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
          <div>
            <span className="text-xs text-slate-400 block">Nomor Pesanan</span>
            <span className="text-lg font-mono font-extrabold text-slate-900">{order.order_number}</span>
          </div>
          <div>
            <span className="text-xs text-slate-400 block">Waktu Pemesanan</span>
            <span className="text-xs font-semibold text-slate-800">{formatDate(order.created_at)}</span>
          </div>
          <div>
            <span className="text-xs text-slate-400 block">Total Tagihan</span>
            <span className="text-lg font-black text-blue-700">{formatRupiah(order.total_amount)}</span>
          </div>
        </div>

        {/* Address & Payment Info Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Shipping Address */}
          <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
            <div className="flex items-center gap-2 text-xs font-bold text-slate-800">
              <MapPin className="w-4 h-4 text-blue-600" />
              <span>Alamat Pengiriman</span>
            </div>
            {order.shipping_address ? (
              <div className="text-xs text-slate-600 space-y-1">
                <p className="font-bold text-slate-900">
                  {order.shipping_address.receiver_name} ({order.shipping_address.phone_number})
                </p>
                <p>{order.shipping_address.full_address}</p>
                <p className="text-slate-400">Kota {order.shipping_address.city}, {order.shipping_address.postal_code}</p>
              </div>
            ) : (
              <p className="text-xs text-slate-400">Informasi alamat tidak tersedia.</p>
            )}
          </div>

          {/* Payment Info */}
          <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-xs font-bold text-slate-800">
                <CreditCard className="w-4 h-4 text-emerald-600" />
                <span>Informasi Pembayaran</span>
              </div>
              <StatusBadge type="payment" status={order.payment?.status || 'pending'} size="sm" />
            </div>

            {order.payment ? (
              <div className="text-xs text-slate-600 space-y-1">
                <div className="flex justify-between">
                  <span>Ref Pembayaran:</span>
                  <code className="font-mono font-semibold text-slate-800">{order.payment.payment_reference}</code>
                </div>
                <div className="flex justify-between">
                  <span>Metode:</span>
                  <span className="font-semibold text-slate-800">{order.payment.channel || 'QRIS / Virtual Account'}</span>
                </div>
                <div className="flex justify-between">
                  <span>Biaya Layanan:</span>
                  <span className="font-semibold text-slate-800">{formatRupiah(order.service_fee)}</span>
                </div>
                {order.payment.status === 'pending' && (
                  <div className="pt-2">
                    <Link
                      to={`/payment/${order.payment.payment_reference}`}
                      className="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold block text-center"
                    >
                      Bayar Tagihan Sekarang
                    </Link>
                  </div>
                )}
              </div>
            ) : (
              <p className="text-xs text-slate-400">Belum ada record pembayaran.</p>
            )}
          </div>
        </div>

        {/* Multi-Seller Orders Breakdown */}
        <div className="space-y-6 pt-4">
          <h3 className="text-sm font-bold text-slate-900">Rincian Paket & Pengiriman</h3>

          {order.seller_orders?.map((so) => (
            <div key={so.id} className="border border-slate-200 rounded-2xl overflow-hidden space-y-4 p-5 shadow-2xs">
              <div className="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div className="flex items-center gap-2">
                  <Store className="w-4 h-4 text-blue-600" />
                  <span className="text-xs font-bold text-slate-900">{so.store?.name}</span>
                  <span className="text-[11px] text-slate-400">({so.store?.city})</span>
                  <span className="text-[11px] font-mono text-slate-400">• {so.seller_order_number}</span>
                </div>
                <StatusBadge type="seller_order" status={so.status} size="sm" />
              </div>

              {/* Items List */}
              <div className="space-y-3">
                {so.items?.map((item: any) => (
                  <div key={item.id} className="flex items-center justify-between text-xs">
                    <div>
                      <p className="font-bold text-slate-900">{item.name || item.product_name}</p>
                      <p className="text-[11px] text-slate-500">Varian: {item.variant_name} • {item.quantity} x {formatRupiah(item.unit_price || item.price)}</p>
                    </div>
                    <span className="font-bold text-slate-900">{formatRupiah(item.line_total || item.subtotal)}</span>
                  </div>
                ))}
              </div>

              {/* Shipping Subtotal */}
              <div className="pt-3 border-t border-slate-100 flex justify-between text-xs text-slate-600">
                <span>Ongkos Kirim Toko:</span>
                <span className="font-bold text-slate-900">{formatRupiah(so.shipping_cost)}</span>
              </div>

              {/* Live Tracking Timeline if Shipped */}
              {so.shipment && (
                <div className="pt-4 border-t border-slate-100 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                      <Truck className="w-4 h-4 text-orange-600" />
                      Status Pengiriman AnterIn Express
                    </span>
                    <Link
                      to={`/tracking?tracking_number=${so.shipment.tracking_number}`}
                      className="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1"
                    >
                      Buka di Halaman Pelacakan <ExternalLink className="w-3.5 h-3.5" />
                    </Link>
                  </div>
                  <TrackingTimeline shipment={so.shipment} />
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
