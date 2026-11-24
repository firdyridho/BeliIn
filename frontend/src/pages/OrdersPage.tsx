import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Package, Truck, ChevronRight, CreditCard } from 'lucide-react';
import { ordersApi } from '../api/orders';
import { useAuthStore } from '../store/authStore';
import { Order } from '../types';
import { formatRupiah, formatDate } from '../utils/formatters';
import { StatusBadge } from '../components/common/StatusBadge';

export const OrdersPage: React.FC = () => {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<string>('all');

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login?redirect=/orders');
      return;
    }

    async function loadOrders() {
      setLoading(true);
      try {
        const res = await ordersApi.getMyOrders();
        setOrders(res.data || []);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }
    loadOrders();
  }, [isAuthenticated, navigate]);

  const filteredOrders = orders.filter((o) => {
    if (activeTab === 'all') return true;
    return o.status === activeTab;
  });

  return (
    <div className="max-w-5xl mx-auto py-6 space-y-6 pb-20">
      <div>
        <h1 className="text-2xl font-extrabold text-slate-900">Daftar Pesanan Saya</h1>
        <p className="text-xs text-slate-500 mt-1">
          Pantau progres pesanan dan pengiriman logistik AnterIn Express secara real-time
        </p>
      </div>

      {/* Tabs Filter */}
      <div className="flex gap-2 overflow-x-auto pb-2 border-b border-slate-200">
        {[
          { key: 'all', label: 'Semua' },
          { key: 'pending', label: 'Menunggu Bayar' },
          { key: 'paid', label: 'Sudah Bayar' },
          { key: 'processing', label: 'Diproses' },
          { key: 'shipped', label: 'Dikirim' },
          { key: 'delivered', label: 'Selesai' },
        ].map((tab) => (
          <button
            key={tab.key}
            onClick={() => setActiveTab(tab.key)}
            className={`px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
              activeTab === tab.key
                ? 'bg-blue-600 text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Orders List */}
      {loading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((n) => (
            <div key={n} className="bg-white rounded-2xl border border-slate-200 p-6 h-40 animate-pulse space-y-3">
              <div className="h-4 bg-slate-200 rounded w-1/4"></div>
              <div className="h-4 bg-slate-200 rounded w-1/2"></div>
            </div>
          ))}
        </div>
      ) : filteredOrders.length === 0 ? (
        <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-3">
          <Package className="w-12 h-12 text-slate-300 mx-auto" />
          <h3 className="text-base font-bold text-slate-800">Tidak ada pesanan pada status ini</h3>
          <p className="text-xs text-slate-400">Pesanan yang kamu buat akan otomatis tercatat di sini.</p>
          <Link to="/catalog" className="mt-2 inline-block px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-semibold">
            Mulai Belanja
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredOrders.map((order) => {
            const isPendingPayment = order.status === 'pending';

            return (
              <div key={order.id} className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs space-y-4 p-5 hover:border-slate-300 transition-colors">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                      <Package className="w-4 h-4" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-bold text-slate-900 font-mono">{order.order_number}</span>
                        <StatusBadge type="order" status={order.status} />
                      </div>
                      <span className="text-[11px] text-slate-400">{formatDate(order.created_at)}</span>
                    </div>
                  </div>

                  <div className="text-right">
                    <span className="text-[11px] text-slate-500 block">Total Tagihan</span>
                    <span className="text-sm font-extrabold text-blue-700">{formatRupiah(order.total_amount)}</span>
                  </div>
                </div>

                {/* Seller Orders Breakdown */}
                <div className="space-y-3 pl-2 sm:pl-4 border-l-2 border-slate-100">
                  {order.seller_orders?.map((so) => (
                    <div key={so.id} className="bg-slate-50/70 p-3.5 rounded-xl border border-slate-100 space-y-2">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <span className="text-xs font-bold text-slate-800">{so.store?.name}</span>
                          <StatusBadge type="seller_order" status={so.status} size="sm" />
                        </div>
                        <span className="text-xs font-semibold text-slate-700">{formatRupiah(so.total_amount)}</span>
                      </div>

                      {/* Items */}
                      <div className="text-xs text-slate-600 space-y-1">
                        {so.items?.map((item: any) => (
                          <div key={item.id} className="flex justify-between text-[11px]">
                            <span>{item.product_name || item.name} ({item.variant_name}) x {item.quantity}</span>
                            <span>{formatRupiah(item.subtotal || item.line_total)}</span>
                          </div>
                        ))}
                      </div>

                      {/* Logistics tracking info */}
                      {so.shipment && (
                        <div className="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
                          <div className="flex items-center gap-1.5 text-orange-700 font-medium">
                            <Truck className="w-3.5 h-3.5" />
                            <span>Resi AnterIn: <strong>{so.shipment.tracking_number}</strong></span>
                            <StatusBadge type="shipment" status={so.shipment.status} size="sm" />
                          </div>
                          <Link
                            to={`/tracking?tracking_number=${so.shipment.tracking_number}`}
                            className="font-bold text-blue-600 hover:underline flex items-center gap-0.5"
                          >
                            Lacak Resi &rarr;
                          </Link>
                        </div>
                      )}
                    </div>
                  ))}
                </div>

                {/* Actions Footer */}
                <div className="pt-2 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100">
                  <div className="flex items-center gap-2">
                    <span className="text-[11px] text-slate-400">Pembayaran:</span>
                    <StatusBadge type="payment" status={order.payment?.status || 'pending'} size="sm" />
                  </div>

                  <div className="flex items-center gap-2">
                    {isPendingPayment && order.payment?.payment_reference && (
                      <Link
                        to={`/payment/${order.payment.payment_reference}`}
                        className="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-xs"
                      >
                        <CreditCard className="w-3.5 h-3.5" />
                        Bayar Sekarang
                      </Link>
                    )}
                    <Link
                      to={`/orders/${order.id}`}
                      className="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1 transition-colors"
                    >
                      Detail Pesanan
                      <ChevronRight className="w-3.5 h-3.5" />
                    </Link>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};
