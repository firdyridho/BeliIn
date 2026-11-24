import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ShoppingBag, Trash2, ArrowRight, Store, ShieldCheck } from 'lucide-react';
import { cartApi } from '../api/cart';
import { useAuthStore } from '../store/authStore';
import { useCartStore } from '../store/cartStore';
import { CartItem } from '../types';
import { formatRupiah } from '../utils/formatters';

export const CartPage: React.FC = () => {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();
  const { cart, setCart, fetchCart, isLoading } = useCartStore();
  const [updatingId, setUpdatingId] = useState<number | null>(null);

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login?redirect=/cart');
      return;
    }
    fetchCart();
  }, [isAuthenticated, fetchCart, navigate]);

  const handleUpdateQuantity = async (item: CartItem, newQty: number) => {
    if (newQty < 1) return;
    setUpdatingId(item.id);
    try {
      const res = await cartApi.updateItem(item.id, { quantity: newQty });
      setCart(res.data);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal memperbarui jumlah produk.');
    } finally {
      setUpdatingId(null);
    }
  };

  const handleRemoveItem = async (itemId: number) => {
    if (!confirm('Hapus produk ini dari keranjang?')) return;
    setUpdatingId(itemId);
    try {
      const res = await cartApi.removeItem(itemId);
      setCart(res.data);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal menghapus produk.');
    } finally {
      setUpdatingId(null);
    }
  };

  const items = cart?.items || [];

  // Group items by store
  const storeGroups = items.reduce((acc, item) => {
    const store = item.product?.store;
    const storeId = store?.id || 0;
    if (!acc[storeId]) {
      acc[storeId] = {
        store,
        items: [],
      };
    }
    acc[storeId].items.push(item);
    return acc;
  }, {} as Record<number, { store: any; items: CartItem[] }>);

  const subtotal = items.reduce((sum, item) => {
    const price = item.variant?.price || item.product?.base_price || 0;
    return sum + price * item.quantity;
  }, 0);

  if (isLoading && !cart) {
    return (
      <div className="max-w-7xl mx-auto py-12 px-4 space-y-6 animate-pulse">
        <div className="h-8 bg-slate-200 rounded w-1/4"></div>
        <div className="h-48 bg-slate-200 rounded-2xl"></div>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="max-w-4xl mx-auto py-16 px-4 text-center">
        <div className="bg-white rounded-3xl border border-slate-200 p-12 space-y-4 shadow-xs">
          <div className="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto">
            <ShoppingBag className="w-8 h-8" />
          </div>
          <h2 className="text-xl font-bold text-slate-800">Keranjang Belanjamu Kosong</h2>
          <p className="text-xs text-slate-500 max-w-sm mx-auto">
            Wah, keranjang belanjamu masih kosong nih. Yuk cari produk impianmu sekarang!
          </p>
          <div className="pt-2">
            <Link
              to="/catalog"
              className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold text-xs shadow-md transition-all"
            >
              Mulai Belanja Sekarang
              <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto py-6 space-y-6 pb-20">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-extrabold text-slate-900">Keranjang Belanja</h1>
        <span className="text-xs text-slate-500">{items.length} jenis produk</span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Cart Items Grouped by Store (8 Cols) */}
        <div className="lg:col-span-8 space-y-6">
          {Object.entries(storeGroups).map(([storeId, group]) => {
            const storeSubtotal = group.items.reduce((s, it) => {
              const p = it.variant?.price || it.product?.base_price || 0;
              return s + p * it.quantity;
            }, 0);

            return (
              <div key={storeId} className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                {/* Store Header */}
                <div className="bg-slate-50/80 px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Store className="w-4 h-4 text-blue-600" />
                    <span className="text-xs font-bold text-slate-900">
                      {group.store?.name || 'Toko'}
                    </span>
                    <span className="text-[11px] text-slate-500 font-normal">
                      • {group.store?.city || 'Indonesia'}
                    </span>
                  </div>
                  <span className="text-xs font-semibold text-slate-600">
                    Subtotal Toko: {formatRupiah(storeSubtotal)}
                  </span>
                </div>

                {/* Items List */}
                <div className="divide-y divide-slate-100 p-5 space-y-4">
                  {group.items.map((item) => {
                    const price = item.variant?.price || (item as any).price_amount || item.product?.base_price || 0;
                    const stock = item.variant?.stock || 20;
                    const isUpdating = updatingId === item.id;
                    const primaryImg = (item as any).image_url || (item as any).product?.images?.[0]?.file_path || item.product?.images?.[0]?.image_url;
                    const pName = (item as any).product_name || item.product?.name;
                    const vName = (item as any).variant_name || item.variant?.name || 'Standard';
                    const pSlug = (item as any).product_slug || item.product?.slug || item.product_id;

                    return (
                      <div key={item.id} className="pt-4 first:pt-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="flex items-center gap-4 flex-1">
                          <div className="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                            {primaryImg ? (
                              <img src={primaryImg} alt="" className="w-full h-full object-cover" />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center text-slate-400">
                                <ShoppingBag className="w-6 h-6 stroke-[1.2]" />
                              </div>
                            )}
                          </div>
                          <div>
                            <Link
                              to={`/products/${pSlug}`}
                              className="text-xs font-bold text-slate-900 hover:text-blue-600 line-clamp-1"
                            >
                              {pName}
                            </Link>
                            <p className="text-[11px] text-slate-500 mt-0.5">
                              Varian: <strong className="text-slate-700">{vName}</strong>
                            </p>
                            <p className="text-xs font-bold text-blue-700 mt-1">
                              {formatRupiah(price)}
                            </p>
                          </div>
                        </div>

                        {/* Quantity and Actions */}
                        <div className="flex items-center gap-4 self-end sm:self-center">
                          <div className="inline-flex items-center border border-slate-200 rounded-xl bg-white shadow-xs">
                            <button
                              type="button"
                              onClick={() => handleUpdateQuantity(item, item.quantity - 1)}
                              disabled={isUpdating || item.quantity <= 1}
                              className="px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50 rounded-l-xl disabled:opacity-40"
                            >
                              -
                            </button>
                            <span className="px-3 py-1 text-xs font-bold text-slate-900 w-9 text-center">
                              {item.quantity}
                            </span>
                            <button
                              type="button"
                              onClick={() => handleUpdateQuantity(item, item.quantity + 1)}
                              disabled={isUpdating || item.quantity >= stock}
                              className="px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50 rounded-r-xl disabled:opacity-40"
                            >
                              +
                            </button>
                          </div>

                          <button
                            type="button"
                            onClick={() => handleRemoveItem(item.id)}
                            disabled={isUpdating}
                            className="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors"
                            title="Hapus Produk"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>

        {/* Order Summary Sidebar (4 Cols) */}
        <div className="lg:col-span-4 space-y-4">
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
            <h3 className="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100">
              Ringkasan Belanja
            </h3>

            <div className="space-y-2.5 text-xs text-slate-600">
              <div className="flex items-center justify-between">
                <span>Total Harga ({items.length} Barang)</span>
                <span className="font-semibold text-slate-900">{formatRupiah(subtotal)}</span>
              </div>
              <div className="flex items-center justify-between text-slate-500">
                <span>Ongkos Kirim</span>
                <span>Dihitung di checkout</span>
              </div>
            </div>

            <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span className="text-xs font-bold text-slate-800">Total Estimasi</span>
              <span className="text-lg font-extrabold text-blue-700">{formatRupiah(subtotal)}</span>
            </div>

            <button
              type="button"
              onClick={() => navigate('/checkout')}
              className="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-md transition-all flex items-center justify-center gap-2 text-xs"
            >
              Lanjut ke Checkout
              <ArrowRight className="w-4 h-4" />
            </button>

            <div className="pt-2 flex items-center justify-center gap-1.5 text-[11px] text-slate-400">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
              <span>Stok otomatis dicadangkan 15 menit saat checkout</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
