import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Sparkles, ArrowRight, Truck, ShieldCheck, Zap, ShoppingBag, ArrowUpRight } from 'lucide-react';
import { catalogApi } from '../api/catalog';
import { Category, Product } from '../types';
import { ProductCard } from '../components/catalog/ProductCard';

export const HomePage: React.FC = () => {
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        const [catsRes, prodsRes] = await Promise.all([
          catalogApi.getCategories(),
          catalogApi.getProducts({ per_page: 8 }),
        ]);
        setCategories(catsRes.data || []);
        setProducts(prodsRes.data || []);
      } catch (err) {
        console.error('Failed to load home data', err);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, []);

  return (
    <div className="space-y-12 pb-16">
      {/* Hero Banner Section */}
      <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-800 to-slate-900 text-white shadow-xl">
        <div className="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div className="relative max-w-7xl mx-auto px-6 py-16 sm:py-24 lg:px-12 flex flex-col md:flex-row items-center justify-between gap-10">
          <div className="space-y-6 max-w-2xl text-center md:text-left">
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-blue-200">
              <Sparkles className="w-4 h-4 text-amber-300" />
              <span>Platform Marketplace Generasi Baru</span>
            </div>

            <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
              Belanja Cerdas, Pengiriman Kilat dengan <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-300">AnterIn Express</span>.
            </h1>

            <p className="text-sm sm:text-base text-blue-100 max-w-xl leading-relaxed">
              Jelajahi jutaan produk dari toko terverifikasi. Transaksi aman, reservasi stok terjamin, dan pengiriman kilat langsung terlacak ke depan pintu rumahmu.
            </p>

            <div className="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-2">
              <Link
                to="/catalog"
                className="px-6 py-3.5 bg-white text-blue-700 hover:bg-blue-50 font-bold rounded-2xl shadow-lg transition-transform transform hover:-translate-y-0.5 flex items-center gap-2 text-sm"
              >
                Mulai Belanja Sekarang
                <ArrowRight className="w-4 h-4" />
              </Link>
              <Link
                to="/tracking"
                className="px-6 py-3.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-2xl border border-white/20 backdrop-blur-md transition-colors flex items-center gap-2 text-sm"
              >
                <Truck className="w-4 h-4 text-orange-400" />
                Lacak Resi AnterIn
              </Link>
            </div>
          </div>

          {/* Logistics Hero Visual Badge */}
          <div className="w-full max-w-sm bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-6 shadow-2xl space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-white/10">
              <span className="text-xs uppercase tracking-wider text-blue-200 font-bold">Kemitraan Resmi</span>
              <span className="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-semibold">Aktif</span>
            </div>
            <div className="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/10">
              <img src="/anterin-logo-dummy.svg" alt="AnterIn" className="h-10 w-auto bg-white rounded-lg p-1" />
              <div>
                <h4 className="text-sm font-bold text-white">AnterIn Express</h4>
                <p className="text-xs text-orange-200">SameDay & Reguler Logistics</p>
              </div>
            </div>
            <div className="space-y-2 text-xs text-blue-100">
              <div className="flex items-center gap-2">
                <Zap className="w-4 h-4 text-amber-300" />
                <span>Pickup otomatis oleh armada kurir</span>
              </div>
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-emerald-300" />
                <span>Asuransi pengiriman & garansi barang tiba</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Category Grid Section */}
      <section className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-slate-900 tracking-tight">Kategori Pilihan</h2>
            <p className="text-xs text-slate-500">Temukan barang yang kamu butuhkan berdasarkan kategori</p>
          </div>
          <Link to="/catalog" className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
            Lihat Semua <ArrowUpRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {categories.map((cat) => (
            <Link
              key={cat.id}
              to={`/catalog?category_id=${cat.id}`}
              className="p-4 bg-white rounded-2xl border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all group text-center flex flex-col items-center justify-center space-y-2"
            >
              <div className="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors flex items-center justify-center font-bold text-base">
                {cat.name.charAt(0)}
              </div>
              <span className="text-xs font-semibold text-slate-800 group-hover:text-blue-600 transition-colors line-clamp-1">
                {cat.name}
              </span>
            </Link>
          ))}
        </div>
      </section>

      {/* Featured Products Section */}
      <section className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-slate-900 tracking-tight">Produk Rekomendasi</h2>
            <p className="text-xs text-slate-500">Pilihan produk terbaik dari toko-toko terpercaya</p>
          </div>
          <Link to="/catalog" className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
            Semua Produk <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {loading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            {[1, 2, 3, 4].map((n) => (
              <div key={n} className="bg-white rounded-2xl border border-slate-200 p-4 h-72 animate-pulse space-y-4">
                <div className="w-full h-40 bg-slate-200 rounded-xl"></div>
                <div className="h-4 bg-slate-200 rounded w-3/4"></div>
                <div className="h-4 bg-slate-200 rounded w-1/2"></div>
              </div>
            ))}
          </div>
        ) : products.length === 0 ? (
          <div className="text-center py-16 bg-white rounded-2xl border border-slate-200">
            <ShoppingBag className="w-12 h-12 text-slate-300 mx-auto mb-2" />
            <h3 className="text-sm font-bold text-slate-700">Belum Ada Produk</h3>
            <p className="text-xs text-slate-400 mt-1">Produk rekomendasi akan segera tampil di sini.</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            {products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        )}
      </section>
    </div>
  );
};
