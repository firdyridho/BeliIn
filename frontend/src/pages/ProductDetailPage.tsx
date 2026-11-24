import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ShoppingCart, Zap, Store, ShieldCheck, ChevronRight, CheckCircle2, AlertCircle, ShoppingBag } from 'lucide-react';
import { catalogApi } from '../api/catalog';
import { cartApi } from '../api/cart';
import { useAuthStore } from '../store/authStore';
import { useCartStore } from '../store/cartStore';
import { Product, ProductVariant } from '../types';
import { formatRupiah } from '../utils/formatters';

export const ProductDetailPage: React.FC = () => {
  const { idOrSlug } = useParams<{ idOrSlug: string }>();
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();
  const { fetchCart } = useCartStore();

  const [product, setProduct] = useState<Product | null>(null);
  const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [loading, setLoading] = useState(true);
  const [addingToCart, setAddingToCart] = useState(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    async function loadProduct() {
      if (!idOrSlug) return;
      setLoading(true);
      try {
        const res = await catalogApi.getProduct(idOrSlug);
        setProduct(res.data);
        if (res.data?.variants && res.data.variants.length > 0) {
          // Select first variant with stock, or first variant
          const initial = res.data.variants.find((v) => v.stock > 0) || res.data.variants[0];
          setSelectedVariant(initial);
        }
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }
    loadProduct();
  }, [idOrSlug]);

  const handleAddToCart = async (goToCart = false) => {
    if (!isAuthenticated) {
      navigate('/login?redirect=' + encodeURIComponent(window.location.pathname));
      return;
    }

    if (!selectedVariant) {
      setErrorMessage('Silakan pilih varian produk terlebih dahulu.');
      return;
    }

    if (selectedVariant.stock <= 0) {
      setErrorMessage('Maaf, stok varian ini sedang habis.');
      return;
    }

    setAddingToCart(true);
    setErrorMessage(null);

    try {
      await cartApi.addItem({
        product_variant_id: selectedVariant.id,
        quantity,
      });
      await fetchCart();

      if (goToCart) {
        navigate('/cart');
      } else {
        setToastMessage(`Berhasil menambahkan ${quantity} barang ke keranjang!`);
        setTimeout(() => setToastMessage(null), 3500);
      }
    } catch (err: any) {
      const msg = err.response?.data?.message || 'Gagal menambahkan ke keranjang.';
      setErrorMessage(msg);
    } finally {
      setAddingToCart(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto py-12 px-4 animate-pulse space-y-8">
        <div className="h-6 bg-slate-200 rounded w-1/4"></div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
          <div className="h-96 bg-slate-200 rounded-3xl"></div>
          <div className="space-y-4">
            <div className="h-8 bg-slate-200 rounded w-3/4"></div>
            <div className="h-6 bg-slate-200 rounded w-1/3"></div>
            <div className="h-32 bg-slate-200 rounded"></div>
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="text-center py-20 bg-white rounded-3xl border border-slate-200 my-8">
        <ShoppingBag className="w-12 h-12 text-slate-300 mx-auto mb-2" />
        <h2 className="text-lg font-bold text-slate-800">Produk Tidak Ditemukan</h2>
        <p className="text-xs text-slate-400 mt-1">Produk mungkin telah dihapus atau tidak aktif.</p>
        <Link to="/catalog" className="mt-4 inline-block px-5 py-2.5 bg-blue-600 text-white text-xs font-semibold rounded-xl">
          Kembali ke Katalog
        </Link>
      </div>
    );
  }

  const p = product as any;
  const primaryImage = p.primary_image || p.images?.[0]?.file_path || p.images?.[0]?.image_url;
  const currentPrice = selectedVariant ? ((selectedVariant as any).price_amount || selectedVariant.price) : (p.base_price || p.min_price || 0);
  const currentStock = selectedVariant ? ((selectedVariant as any).available_quantity ?? selectedVariant.stock ?? 10) : (product.total_stock || 10);

  return (
    <div className="max-w-7xl mx-auto py-6 space-y-8 pb-20">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-xs text-slate-500">
        <Link to="/" className="hover:text-blue-600">Beranda</Link>
        <ChevronRight className="w-3.5 h-3.5" />
        <Link to="/catalog" className="hover:text-blue-600">Katalog</Link>
        {product.category && (
          <>
            <ChevronRight className="w-3.5 h-3.5" />
            <Link to={`/catalog?category_id=${product.category.id}`} className="hover:text-blue-600">
              {product.category.name}
            </Link>
          </>
        )}
        <ChevronRight className="w-3.5 h-3.5" />
        <span className="text-slate-900 font-medium truncate max-w-xs">{product.name}</span>
      </nav>

      {/* Notifications */}
      {toastMessage && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs text-emerald-800 animate-fade-in">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
            <span className="font-semibold">{toastMessage}</span>
          </div>
          <Link to="/cart" className="underline font-bold hover:text-emerald-900">
            Lihat Keranjang &rarr;
          </Link>
        </div>
      )}

      {errorMessage && (
        <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-2 text-xs text-rose-800">
          <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
          <span>{errorMessage}</span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
        {/* Product Images (5 Cols) */}
        <div className="lg:col-span-5 space-y-4">
          <div className="aspect-square bg-slate-100 rounded-3xl border border-slate-200 overflow-hidden relative shadow-sm">
            {primaryImage ? (
              <img src={primaryImage} alt={product.name} className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full flex flex-col items-center justify-center text-slate-400">
                <ShoppingBag className="w-16 h-16 stroke-[1.2] text-slate-300" />
                <span className="text-xs mt-2 font-medium">BeliIn Product Media</span>
              </div>
            )}
          </div>

          {product.images && product.images.length > 1 && (
            <div className="flex gap-3 overflow-x-auto pb-2">
              {product.images.map((img) => (
                <div key={img.id} className="w-18 h-18 rounded-xl border border-slate-200 overflow-hidden shrink-0">
                  <img src={img.image_url} alt="" className="w-full h-full object-cover" />
                </div>
              ))}
            </div>
          )}

          {/* Store Card */}
          {product.store && (
            <div className="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                  <Store className="w-5 h-5" />
                </div>
                <div>
                  <h4 className="text-xs font-bold text-slate-900">{product.store.name}</h4>
                  <p className="text-[11px] text-slate-500">Kota {product.store.city}</p>
                </div>
              </div>
              <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded-full border border-emerald-200">
                Toko Terverifikasi
              </span>
            </div>
          )}
        </div>

        {/* Product Details (7 Cols) */}
        <div className="lg:col-span-7 space-y-6">
          <div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
              {product.name}
            </h1>
            <div className="mt-3 flex items-center gap-4 text-xs text-slate-500">
              <span>Kategori: <strong className="text-slate-800">{product.category?.name || 'Umum'}</strong></span>
              <span>•</span>
              <span className="text-emerald-600 font-semibold flex items-center gap-1">
                <ShieldCheck className="w-4 h-4" /> 100% Produk Original
              </span>
            </div>
          </div>

          {/* Price Box */}
          <div className="p-5 bg-gradient-to-r from-blue-50 to-indigo-50/50 rounded-2xl border border-blue-100 flex items-baseline gap-3">
            <span className="text-3xl font-black text-blue-800">
              {formatRupiah(currentPrice)}
            </span>
            {selectedVariant && (
              <span className="text-xs font-medium text-slate-500">
                (SKU: <code className="font-mono">{selectedVariant.sku}</code> • {selectedVariant.weight_grams}g)
              </span>
            )}
          </div>

          {/* Variants Selector */}
          {product.variants && product.variants.length > 0 && (
            <div className="space-y-3">
              <label className="text-xs font-bold text-slate-800 uppercase tracking-wider block">
                Pilih Varian Produk:
              </label>
              <div className="flex flex-wrap gap-2.5">
                {product.variants.map((v: any) => {
                  const isSelected = selectedVariant?.id === v.id;
                  const vName = v.variant_name || v.name;
                  const vPrice = v.price_amount || v.price;
                  const vStock = v.available_quantity ?? v.stock ?? 10;
                  const isOut = vStock <= 0;
                  return (
                    <button
                      key={v.id}
                      type="button"
                      disabled={isOut}
                      onClick={() => {
                        setSelectedVariant(v);
                        setQuantity(1);
                      }}
                      className={`px-4 py-2.5 rounded-xl text-xs font-semibold transition-all border ${
                        isSelected
                          ? 'bg-blue-600 text-white border-blue-600 shadow-md ring-2 ring-blue-300 ring-offset-1'
                          : isOut
                          ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed line-through'
                          : 'bg-white text-slate-700 border-slate-300 hover:border-blue-400'
                      }`}
                    >
                      {vName} - {formatRupiah(vPrice)} {isOut && '(Habis)'}
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          {/* Quantity and Stock Counter */}
          <div className="space-y-2">
            <label className="text-xs font-bold text-slate-800 uppercase tracking-wider block">
              Jumlah:
            </label>
            <div className="flex items-center gap-4">
              <div className="inline-flex items-center border border-slate-300 rounded-xl bg-white shadow-xs">
                <button
                  type="button"
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                  disabled={quantity <= 1}
                  className="px-3.5 py-2 text-slate-600 hover:bg-slate-50 rounded-l-xl disabled:opacity-40"
                >
                  -
                </button>
                <span className="px-4 py-2 text-xs font-bold text-slate-900 w-12 text-center">
                  {quantity}
                </span>
                <button
                  type="button"
                  onClick={() => setQuantity(Math.min(currentStock, quantity + 1))}
                  disabled={quantity >= currentStock}
                  className="px-3.5 py-2 text-slate-600 hover:bg-slate-50 rounded-r-xl disabled:opacity-40"
                >
                  +
                </button>
              </div>

              <div className="text-xs text-slate-500">
                Stok tersedia: <strong className="text-slate-900">{currentStock}</strong> unit
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="pt-2 flex flex-col sm:flex-row gap-3">
            <button
              type="button"
              onClick={() => handleAddToCart(false)}
              disabled={addingToCart || currentStock <= 0}
              className="flex-1 py-3.5 px-6 rounded-2xl border-2 border-blue-600 text-blue-700 font-bold hover:bg-blue-50 transition-colors flex items-center justify-center gap-2 text-sm disabled:opacity-50"
            >
              <ShoppingCart className="w-4 h-4" />
              {addingToCart ? 'Menambahkan...' : 'Tambah ke Keranjang'}
            </button>
            <button
              type="button"
              onClick={() => handleAddToCart(true)}
              disabled={addingToCart || currentStock <= 0}
              className="flex-1 py-3.5 px-6 rounded-2xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-md transition-colors flex items-center justify-center gap-2 text-sm disabled:opacity-50"
            >
              <Zap className="w-4 h-4 fill-current" />
              Beli Langsung
            </button>
          </div>

          {/* Logistics Partner AnterIn Highlight */}
          <div className="p-4 bg-orange-50/70 border border-orange-200/80 rounded-2xl space-y-2">
            <div className="flex items-center gap-2">
              <img src="/anterin-logo-dummy.svg" alt="AnterIn" className="h-4 w-auto" />
              <span className="text-xs font-bold text-orange-950">Pengiriman Cepat Didukung AnterIn Express</span>
            </div>
            <p className="text-[11px] text-orange-900 leading-relaxed">
              Tersedia layanan <strong>AnterIn SameDay</strong> dan <strong>AnterIn Reguler</strong> dengan estimasi 1-3 hari kerja. Pelacakan resi real-time langsung di platform BeliIn.
            </p>
          </div>

          {/* Description */}
          <div className="pt-4 border-t border-slate-200 space-y-2">
            <h3 className="text-sm font-bold text-slate-900">Deskripsi Produk</h3>
            <p className="text-xs text-slate-600 whitespace-pre-line leading-relaxed">
              {product.description || 'Tidak ada deskripsi detail untuk produk ini.'}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};
