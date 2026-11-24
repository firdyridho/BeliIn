import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Store, Package, Truck, CheckCircle2, RefreshCw } from 'lucide-react';
import { sellerApi } from '../api/seller';
import { ordersApi } from '../api/orders';
import { useAuthStore } from '../store/authStore';
import { SellerOrder, Store as StoreType } from '../types';
import { formatRupiah } from '../utils/formatters';
import { StatusBadge } from '../components/common/StatusBadge';

export const SellerDashboardPage: React.FC = () => {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();

  const [store, setStore] = useState<StoreType | null>(null);
  const [orders, setOrders] = useState<SellerOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState<number | null>(null);
  const [msg, setMsg] = useState<string | null>(null);

  // Store registration form state
  const [storeName, setStoreName] = useState('');
  const [storeCity, setStoreCity] = useState('Jakarta Selatan');
  const [storeDesc, setStoreDesc] = useState('');

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login?redirect=/seller/dashboard');
      return;
    }

    loadDashboardData();
  }, [isAuthenticated, navigate]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      // 1. Get Store profile
      try {
        const storeRes = await sellerApi.getStore();
        setStore(storeRes.data);
      } catch (e) {
        setStore(null);
      }

      // 2. Get Seller incoming orders
      try {
        const ordersRes = await ordersApi.getSellerOrders();
        setOrders(ordersRes.data || []);
      } catch (e) {
        setOrders([]);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleCreateStore = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await sellerApi.createOrUpdateStore({
        name: storeName,
        slug: storeName.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
        city: storeCity,
        description: storeDesc,
      });
      setStore(res.data);
      setMsg('Toko Anda berhasil dibuat!');
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal membuat toko.');
    }
  };

  const handleUpdateStatus = async (orderId: number, nextStatus: string) => {
    setUpdatingId(orderId);
    try {
      await ordersApi.updateSellerOrderStatus(orderId, nextStatus);
      await loadDashboardData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal mengubah status pesanan.');
    } finally {
      setUpdatingId(null);
    }
  };

  const handleShipWithAnterIn = async (orderId: number) => {
    setUpdatingId(orderId);
    try {
      const res = await ordersApi.shipSellerOrder(orderId, {
        shipping_service_id: 1, // Default AnterIn Express Service ID
      });
      setMsg(`Paket berhasil diserahkan ke AnterIn Express! No. Resi: ${res.data.tracking_number}`);
      await loadDashboardData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal memproses pengiriman dengan AnterIn.');
    } finally {
      setUpdatingId(null);
    }
  };

  if (loading) {
    return (
      <div className="max-w-6xl mx-auto py-12 px-4 space-y-6 animate-pulse">
        <div className="h-8 bg-slate-200 rounded w-1/4"></div>
        <div className="h-64 bg-slate-200 rounded-3xl"></div>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto py-6 space-y-8 pb-20">
      {/* Top Banner */}
      <div className="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div className="flex items-center gap-4">
          <div className="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl">
            <Store className="w-7 h-7" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-xl font-extrabold text-slate-900">
                {store ? store.name : 'Buka Toko di BeliIn'}
              </h1>
              {store && (
                <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                  {store.status.toUpperCase()}
                </span>
              )}
            </div>
            <p className="text-xs text-slate-500 mt-0.5">
              {store ? `Kota Asal: ${store.city} • Mitra Logistik: AnterIn Express` : 'Kelola katalog dan pesanan pembeli dengan mudah.'}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={loadDashboardData}
            className="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
            title="Refresh Data"
          >
            <RefreshCw className="w-4 h-4" />
          </button>
        </div>
      </div>

      {msg && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-2 text-xs text-emerald-800">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{msg}</span>
        </div>
      )}

      {!store ? (
        /* Create Store Onboarding Form */
        <div className="bg-white rounded-3xl border border-slate-200 p-8 shadow-xs max-w-xl mx-auto space-y-6">
          <div className="text-center space-y-1">
            <Store className="w-10 h-10 text-emerald-600 mx-auto" />
            <h2 className="text-lg font-bold text-slate-900">Buka Toko Anda Sekarang</h2>
            <p className="text-xs text-slate-500">
              Mulai berjualan di BeliIn dan jangkau jutaan pelanggan dengan dukungan kurir AnterIn Express.
            </p>
          </div>

          <form onSubmit={handleCreateStore} className="space-y-4">
            <div>
              <label className="text-xs font-semibold text-slate-700 block mb-1">Nama Toko</label>
              <input
                type="text"
                required
                value={storeName}
                onChange={(e) => setStoreName(e.target.value)}
                placeholder="Contoh: Maju Jaya Elektronik"
                className="w-full p-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-slate-700 block mb-1">Kota Toko (Asal Pengiriman)</label>
              <input
                type="text"
                required
                value={storeCity}
                onChange={(e) => setStoreCity(e.target.value)}
                className="w-full p-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-slate-700 block mb-1">Deskripsi Singkat Toko</label>
              <textarea
                rows={3}
                value={storeDesc}
                onChange={(e) => setStoreDesc(e.target.value)}
                placeholder="Jelaskan jenis produk yang Anda jual..."
                className="w-full p-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500"
              />
            </div>

            <button
              type="submit"
              className="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md text-xs transition-colors"
            >
              Aktifkan Toko Saya
            </button>
          </form>
        </div>
      ) : (
        /* Orders & Store Management Tabs */
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-bold text-slate-900">Pesanan Masuk ({orders.length})</h2>
            <span className="text-xs text-slate-500">Pesanan khusus untuk toko Anda</span>
          </div>

          {orders.length === 0 ? (
            <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-3">
              <Package className="w-12 h-12 text-slate-300 mx-auto" />
              <h3 className="text-sm font-bold text-slate-800">Belum ada pesanan masuk</h3>
              <p className="text-xs text-slate-400">
                Pesanan pembeli yang dialokasikan ke toko Anda akan muncul di sini.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {orders.map((so) => {
                const isUpdating = updatingId === so.id;

                return (
                  <div key={so.id} className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-mono font-bold text-xs text-slate-900">{so.seller_order_number}</span>
                          <StatusBadge type="seller_order" status={so.status} size="sm" />
                        </div>
                        {so.buyer_notes && (
                          <p className="text-[11px] text-amber-700 mt-1 bg-amber-50 px-2 py-0.5 rounded">
                            Catatan Pembeli: {so.buyer_notes}
                          </p>
                        )}
                      </div>

                      <div className="text-right">
                        <span className="text-[11px] text-slate-400 block">Total Pesanan Toko</span>
                        <span className="text-sm font-extrabold text-slate-900">{formatRupiah(so.total_amount)}</span>
                      </div>
                    </div>

                    {/* Items Table */}
                    <div className="space-y-2">
                      {so.items?.map((it: any) => (
                        <div key={it.id} className="flex items-center justify-between text-xs text-slate-700">
                          <span>{it.name || it.product_name} ({it.variant_name}) x {it.quantity}</span>
                          <span className="font-semibold">{formatRupiah(it.line_total || it.subtotal)}</span>
                        </div>
                      ))}
                    </div>

                    {/* Shipment info if already shipped */}
                    {so.shipment && (
                      <div className="p-3 bg-orange-50/70 border border-orange-200 rounded-xl flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div className="flex items-center gap-2 text-orange-950">
                          <Truck className="w-4 h-4 text-orange-600" />
                          <span>Resi AnterIn Express: <strong className="font-mono">{so.shipment.tracking_number}</strong></span>
                        </div>
                        <Link
                          to={`/tracking?tracking_number=${so.shipment.tracking_number}`}
                          className="font-bold text-blue-600 hover:underline"
                        >
                          Lacak Posisi Kurir &rarr;
                        </Link>
                      </div>
                    )}

                    {/* Seller Action Controls */}
                    <div className="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2">
                      {so.status === 'accepted' && (
                        <button
                          type="button"
                          onClick={() => handleUpdateStatus(so.id, 'processing')}
                          disabled={isUpdating}
                          className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors"
                        >
                          Mulai Kemas Paket (Processing)
                        </button>
                      )}

                      {so.status === 'processing' && !so.shipment && (
                        <button
                          type="button"
                          onClick={() => handleShipWithAnterIn(so.id)}
                          disabled={isUpdating}
                          className="px-4 py-2 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5"
                        >
                          <Truck className="w-4 h-4" />
                          Kirim dengan AnterIn Express (Generate AWB)
                        </button>
                      )}

                      {so.status === 'shipped' && (
                        <span className="text-xs text-slate-500 flex items-center gap-1">
                          <CheckCircle2 className="w-4 h-4 text-emerald-600" /> Paket Sedang Ditangani AnterIn
                        </span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}
    </div>
  );
};
