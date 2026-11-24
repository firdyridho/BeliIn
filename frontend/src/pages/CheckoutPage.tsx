import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { MapPin, Truck, ShieldCheck, Plus, CheckCircle2, AlertCircle, ArrowRight, Store } from 'lucide-react';
import { authApi } from '../api/auth';
import { checkoutApi } from '../api/checkout';
import { useAuthStore } from '../store/authStore';
import { useCartStore } from '../store/cartStore';
import { UserAddress, ShippingServiceOption, CartItem } from '../types';
import { formatRupiah } from '../utils/formatters';

export const CheckoutPage: React.FC = () => {
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();
  const { cart, fetchCart } = useCartStore();

  const [addresses, setAddresses] = useState<UserAddress[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [showAddressForm, setShowAddressForm] = useState(false);
  const [newAddress, setNewAddress] = useState({
    receiver_name: '',
    phone_number: '',
    city: 'Jakarta Selatan',
    postal_code: '12950',
    full_address: '',
    is_default: true,
  });

  // Rates mapped by storeId: ShippingServiceOption[]
  const [storeRates, setStoreRates] = useState<Record<number, ShippingServiceOption[]>>({});
  // Selected service_code per store: { [storeId]: service_code }
  const [selectedServices, setSelectedServices] = useState<Record<number, string>>({});
  // Buyer notes per store
  const [storeNotes, setStoreNotes] = useState<Record<number, string>>({});

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login?redirect=/checkout');
      return;
    }

    async function init() {
      setLoading(true);
      try {
        await fetchCart();
        const addrRes = await authApi.getAddresses();
        const userAddrs = addrRes.data || [];
        setAddresses(userAddrs);

        const defaultAddr = userAddrs.find((a) => a.is_default) || userAddrs[0];
        if (defaultAddr) {
          setSelectedAddressId(defaultAddr.id);
        } else {
          setShowAddressForm(true);
        }
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }
    init();
  }, [isAuthenticated, navigate, fetchCart]);

  const items = cart?.items || [];

  // Group items by store
  const storeGroups = items.reduce((acc, item) => {
    const store = item.product?.store;
    const storeId = store?.id || 0;
    if (!acc[storeId]) {
      acc[storeId] = {
        store,
        items: [],
        totalWeight: 0,
      };
    }
    acc[storeId].items.push(item);
    const weight = (item.variant?.weight_grams || 250) * item.quantity;
    acc[storeId].totalWeight += weight;
    return acc;
  }, {} as Record<number, { store: any; items: CartItem[]; totalWeight: number }>);

  // Fetch shipping rates whenever selectedAddress changes
  useEffect(() => {
    async function loadRatesForStores() {
      const activeAddr = addresses.find((a) => a.id === selectedAddressId);
      const destinationCity = activeAddr?.city || 'Jakarta Selatan';

      const ratesMap: Record<number, ShippingServiceOption[]> = {};
      const serviceDefaults: Record<number, string> = {};

      for (const [storeIdStr, group] of Object.entries(storeGroups)) {
        const storeId = Number(storeIdStr);
        try {
          const res = await checkoutApi.getRates({
            store_id: storeId,
            destination_city: destinationCity,
            total_weight: group.totalWeight || 500,
          });
          ratesMap[storeId] = res.data || [];
          // Default to first service (AnterIn Express preferred)
          if (res.data && res.data.length > 0) {
            const anterinOpt = res.data.find((o) => o.service_code.includes('ANTERIN'));
            serviceDefaults[storeId] = anterinOpt ? anterinOpt.service_code : res.data[0].service_code;
          }
        } catch (err) {
          console.error(`Failed to get rates for store ${storeId}`, err);
        }
      }

      setStoreRates(ratesMap);
      setSelectedServices((prev) => ({ ...serviceDefaults, ...prev }));
    }

    if (Object.keys(storeGroups).length > 0 && selectedAddressId) {
      loadRatesForStores();
    }
  }, [selectedAddressId, items.length]);

  const handleCreateAddress = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await authApi.createAddress(newAddress);
      const created = res.data;
      setAddresses((prev) => [...prev, created]);
      setSelectedAddressId(created.id);
      setShowAddressForm(false);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal menyimpan alamat.');
    }
  };

  const calculateSubtotal = () => {
    return items.reduce((sum, item) => {
      const price = item.variant?.price || item.product?.base_price || 0;
      return sum + price * item.quantity;
    }, 0);
  };

  const calculateTotalShipping = () => {
    let total = 0;
    for (const [storeIdStr, rates] of Object.entries(storeRates)) {
      const storeId = Number(storeIdStr);
      const chosenService = selectedServices[storeId];
      const matched = rates.find((r) => r.service_code === chosenService);
      if (matched) {
        total += matched.rate;
      }
    }
    return total;
  };

  const serviceFee = 1000;
  const subtotal = calculateSubtotal();
  const totalShipping = calculateTotalShipping();
  const grandTotal = subtotal + totalShipping + serviceFee;

  const handleProcessCheckout = async () => {
    if (!selectedAddressId) {
      setErrorMsg('Silakan pilih alamat pengiriman.');
      return;
    }

    // Verify all stores have a chosen service
    for (const storeId of Object.keys(storeGroups)) {
      if (!selectedServices[Number(storeId)]) {
        setErrorMsg('Silakan pilih opsi kurir pengiriman untuk setiap toko.');
        return;
      }
    }

    setSubmitting(true);
    setErrorMsg(null);

    const storesPayload = Object.keys(storeGroups).map((storeIdStr) => {
      const sId = Number(storeIdStr);
      const chosen = storeRates[sId]?.find((r) => r.service_code === selectedServices[sId]);
      const cost = chosen?.rate || 10000;

      return {
        store_id: sId,
        service_code: selectedServices[sId],
        shipping_service_code: selectedServices[sId],
        rate: cost,
        shipping_amount: cost,
        buyer_notes: storeNotes[sId] || '',
      };
    });

    try {
      const res = await checkoutApi.processCheckout({
        shipping_address_id: selectedAddressId,
        stores: storesPayload,
      } as any);

      const order = res.data;
      await fetchCart();

      // Navigate to payment page
      if (order.payment?.payment_reference) {
        navigate(`/payment/${order.payment.payment_reference}`);
      } else {
        navigate(`/orders/${order.id}`);
      }
    } catch (err: any) {
      const msg = err.response?.data?.message || 'Gagal memproses checkout. Silakan periksa stok produk.';
      setErrorMsg(msg);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto py-12 px-4 space-y-6 animate-pulse">
        <div className="h-8 bg-slate-200 rounded w-1/4"></div>
        <div className="h-64 bg-slate-200 rounded-3xl"></div>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="max-w-4xl mx-auto py-16 px-4 text-center">
        <h2 className="text-lg font-bold text-slate-800">Tidak ada produk untuk dicheckout</h2>
        <Link to="/cart" className="mt-4 inline-block text-xs font-bold text-blue-600 underline">
          Kembali ke Keranjang
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto py-6 space-y-6 pb-20">
      <h1 className="text-2xl font-extrabold text-slate-900">Checkout Pesanan</h1>

      {errorMsg && (
        <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-2 text-xs text-rose-800">
          <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Left Column (8 cols): Address & Per-Store Delivery */}
        <div className="lg:col-span-8 space-y-6">
          {/* Shipping Address Section */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-blue-600" />
                <h3 className="text-sm font-bold text-slate-900">Alamat Pengiriman</h3>
              </div>
              <button
                type="button"
                onClick={() => setShowAddressForm(!showAddressForm)}
                className="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1"
              >
                <Plus className="w-3.5 h-3.5" />
                Tambah Alamat Baru
              </button>
            </div>

            {/* Address Selection Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {addresses.map((addr) => {
                const isSelected = selectedAddressId === addr.id;
                return (
                  <div
                    key={addr.id}
                    onClick={() => setSelectedAddressId(addr.id)}
                    className={`p-4 rounded-xl border text-xs cursor-pointer transition-all ${
                      isSelected
                        ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20'
                        : 'border-slate-200 hover:border-slate-300 bg-white'
                    }`}
                  >
                    <div className="flex items-center justify-between font-bold text-slate-900 mb-1">
                      <span>{addr.receiver_name} ({addr.city})</span>
                      {isSelected && <CheckCircle2 className="w-4 h-4 text-blue-600" />}
                    </div>
                    <p className="text-slate-600 leading-relaxed line-clamp-2">{addr.full_address}</p>
                    <p className="text-slate-400 mt-1">{addr.phone_number} • Kodepos: {addr.postal_code}</p>
                  </div>
                );
              })}
            </div>

            {/* New Address Inline Form */}
            {showAddressForm && (
              <form onSubmit={handleCreateAddress} className="pt-4 border-t border-slate-100 space-y-3 bg-slate-50 p-4 rounded-xl">
                <h4 className="text-xs font-bold text-slate-800">Form Alamat Pengiriman Baru</h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="text-[11px] font-semibold text-slate-600 block mb-1">Nama Penerima</label>
                    <input
                      type="text"
                      required
                      value={newAddress.receiver_name}
                      onChange={(e) => setNewAddress({ ...newAddress, receiver_name: e.target.value })}
                      placeholder="Contoh: Budi Santoso"
                      className="w-full text-xs p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label className="text-[11px] font-semibold text-slate-600 block mb-1">No. Handphone</label>
                    <input
                      type="text"
                      required
                      value={newAddress.phone_number}
                      onChange={(e) => setNewAddress({ ...newAddress, phone_number: e.target.value })}
                      placeholder="08123456789"
                      className="w-full text-xs p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label className="text-[11px] font-semibold text-slate-600 block mb-1">Kota / Kabupaten</label>
                    <input
                      type="text"
                      required
                      value={newAddress.city}
                      onChange={(e) => setNewAddress({ ...newAddress, city: e.target.value })}
                      className="w-full text-xs p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label className="text-[11px] font-semibold text-slate-600 block mb-1">Kode Pos</label>
                    <input
                      type="text"
                      required
                      value={newAddress.postal_code}
                      onChange={(e) => setNewAddress({ ...newAddress, postal_code: e.target.value })}
                      className="w-full text-xs p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                  </div>
                </div>
                <div>
                  <label className="text-[11px] font-semibold text-slate-600 block mb-1">Alamat Lengkap (Jalan, RT/RW, No. Rumah)</label>
                  <textarea
                    required
                    rows={2}
                    value={newAddress.full_address}
                    onChange={(e) => setNewAddress({ ...newAddress, full_address: e.target.value })}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
                <div className="flex justify-end gap-2 pt-1">
                  <button
                    type="button"
                    onClick={() => setShowAddressForm(false)}
                    className="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-200 rounded-lg"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg"
                  >
                    Simpan Alamat
                  </button>
                </div>
              </form>
            )}
          </div>

          {/* Per-Store Delivery & Items Section */}
          <div className="space-y-6">
            <h3 className="text-sm font-bold text-slate-900">Pesanan per Toko</h3>

            {Object.entries(storeGroups).map(([storeIdStr, group]) => {
              const storeId = Number(storeIdStr);
              const rates = storeRates[storeId] || [];
              const selectedServiceCode = selectedServices[storeId];

              return (
                <div key={storeId} className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs space-y-4 p-5">
                  <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                      <Store className="w-4 h-4 text-blue-600" />
                      <span className="text-xs font-bold text-slate-900">{group.store?.name}</span>
                      <span className="text-[11px] text-slate-400">({group.store?.city})</span>
                    </div>
                    <span className="text-xs text-slate-500">
                      Total Berat: <strong>{group.totalWeight}g</strong>
                    </span>
                  </div>

                  {/* Items in this store */}
                  <div className="space-y-3">
                    {group.items.map((it) => (
                      <div key={it.id} className="flex items-center justify-between text-xs">
                        <span className="text-slate-800">
                          {it.product?.name} ({it.variant?.name}) x {it.quantity}
                        </span>
                        <span className="font-semibold text-slate-900">
                          {formatRupiah((it.variant?.price || 0) * it.quantity)}
                        </span>
                      </div>
                    ))}
                  </div>

                  {/* Shipping Method Selector */}
                  <div className="pt-3 border-t border-slate-100 space-y-2">
                    <label className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                      <Truck className="w-4 h-4 text-orange-500" />
                      Pilih Jasa Pengiriman:
                    </label>

                    {rates.length === 0 ? (
                      <div className="text-xs text-slate-400 py-2">Memuat opsi ongkir...</div>
                    ) : (
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        {rates.map((rateOpt) => {
                          const isAnterIn = rateOpt.service_code.includes('ANTERIN');
                          const isChecked = selectedServiceCode === rateOpt.service_code;

                          return (
                            <label
                              key={rateOpt.service_code}
                              className={`p-3 rounded-xl border text-xs cursor-pointer flex items-center justify-between transition-all ${
                                isChecked
                                  ? 'border-blue-600 bg-blue-50/40 ring-1 ring-blue-500'
                                  : 'border-slate-200 hover:border-slate-300'
                              }`}
                            >
                              <div className="flex items-center gap-2">
                                <input
                                  type="radio"
                                  name={`shipping-${storeId}`}
                                  value={rateOpt.service_code}
                                  checked={isChecked}
                                  onChange={() => setSelectedServices({ ...selectedServices, [storeId]: rateOpt.service_code })}
                                  className="text-blue-600 focus:ring-blue-500"
                                />
                                <div>
                                  <div className="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>{rateOpt.service_name}</span>
                                    {isAnterIn && (
                                      <span className="px-1.5 py-0.2 bg-orange-100 text-orange-800 text-[10px] font-bold rounded">
                                        AnterIn
                                      </span>
                                    )}
                                  </div>
                                  <span className="text-[11px] text-slate-400">Estimasi: {rateOpt.etd}</span>
                                </div>
                              </div>
                              <span className="font-bold text-blue-700">{formatRupiah(rateOpt.rate)}</span>
                            </label>
                          );
                        })}
                      </div>
                    )}
                  </div>

                  {/* Notes */}
                  <div className="pt-2">
                    <input
                      type="text"
                      placeholder="Catatan untuk penjual toko ini (opsional)..."
                      value={storeNotes[storeId] || ''}
                      onChange={(e) => setStoreNotes({ ...storeNotes, [storeId]: e.target.value })}
                      className="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Right Column (4 cols): Order Summary & Checkout Trigger */}
        <div className="lg:col-span-4 space-y-4">
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4 sticky top-24">
            <h3 className="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100">
              Ringkasan Pembayaran
            </h3>

            <div className="space-y-3 text-xs text-slate-600">
              <div className="flex items-center justify-between">
                <span>Subtotal Produk</span>
                <span className="font-semibold text-slate-900">{formatRupiah(subtotal)}</span>
              </div>
              <div className="flex items-center justify-between">
                <span>Total Ongkos Kirim</span>
                <span className="font-semibold text-slate-900">{formatRupiah(totalShipping)}</span>
              </div>
              <div className="flex items-center justify-between">
                <span>Biaya Layanan Platform</span>
                <span className="font-semibold text-slate-900">{formatRupiah(serviceFee)}</span>
              </div>
            </div>

            <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span className="text-xs font-bold text-slate-800">Total Tagihan</span>
              <span className="text-xl font-black text-blue-700">{formatRupiah(grandTotal)}</span>
            </div>

            <button
              type="button"
              onClick={handleProcessCheckout}
              disabled={submitting || !selectedAddressId}
              className="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2 text-xs disabled:opacity-50"
            >
              {submitting ? 'Memproses Reservasi Stok...' : 'Bayar Sekarang'}
              <ArrowRight className="w-4 h-4" />
            </button>

            <div className="pt-2 space-y-2 text-[11px] text-slate-400">
              <div className="flex items-center gap-1.5">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>Stok otomatis dicadangkan 15 menit secara atomik.</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Truck className="w-3.5 h-3.5 text-orange-500 shrink-0" />
                <span>Pengiriman didukung resmi oleh kurir AnterIn Express.</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
