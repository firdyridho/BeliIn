import React from 'react';
import { Link } from 'react-router-dom';
import { ShieldCheck, Truck, CreditCard, Headphones, Award } from 'lucide-react';

export const Footer: React.FC = () => {
  return (
    <footer className="bg-slate-900 text-slate-400 mt-20 border-t border-slate-800">
      {/* Key Guarantees */}
      <div className="border-b border-slate-800/80 py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-2 md:grid-cols-4 gap-6">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center shrink-0">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-white">100% Original & Aman</h4>
              <p className="text-xs text-slate-400">Garansi uang kembali penuh</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center shrink-0">
              <Truck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-white">Didukung AnterIn</h4>
              <p className="text-xs text-slate-400">Pengiriman kilat & aman</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0">
              <CreditCard className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-white">Pembayaran Praktis</h4>
              <p className="text-xs text-slate-400">QRIS, VA Bank & E-Wallet</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center shrink-0">
              <Headphones className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-white">Layanan 24/7</h4>
              <p className="text-xs text-slate-400">Bantuan kapan saja Anda butuhkan</p>
            </div>
          </div>
        </div>
      </div>

      {/* Main Links */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-8">
          <div className="md:col-span-2 space-y-4">
            <div className="flex items-center gap-3">
              <img src="/beliin-logo-dummy.svg" alt="BeliIn" className="h-9 w-auto bg-white rounded-lg p-1" />
            </div>
            <p className="text-xs text-slate-400 max-w-sm leading-relaxed">
              BeliIn adalah platform marketplace modern yang menghubungkan pembeli cerdas dengan ribuan penjual terpercaya di seluruh Indonesia.
            </p>
            <div className="pt-2">
              <div className="text-xs text-slate-300 font-medium mb-1.5 flex items-center gap-2">
                <span>Mitra Logistik Utama:</span>
              </div>
              <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700">
                <img src="/anterin-logo-dummy.svg" alt="AnterIn" className="h-5 w-auto" />
                <span className="text-[11px] font-semibold text-orange-400">Official Logistics Partner</span>
              </div>
            </div>
          </div>

          <div>
            <h4 className="text-xs font-bold text-white uppercase tracking-wider mb-3">Jelajahi BeliIn</h4>
            <ul className="space-y-2 text-xs">
              <li><Link to="/catalog" className="hover:text-white transition-colors">Semua Kategori</Link></li>
              <li><Link to="/catalog?category=gadget-elektronik" className="hover:text-white transition-colors">Gadget & Elektronik</Link></li>
              <li><Link to="/catalog?category=fashion-pria" className="hover:text-white transition-colors">Fashion</Link></li>
              <li><Link to="/tracking" className="hover:text-white transition-colors">Lacak Pengiriman AnterIn</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="text-xs font-bold text-white uppercase tracking-wider mb-3">Untuk Penjual</h4>
            <ul className="space-y-2 text-xs">
              <li><Link to="/seller/dashboard" className="hover:text-white transition-colors">Buka Toko Gratis</Link></li>
              <li><Link to="/seller/dashboard" className="hover:text-white transition-colors">Panduan Berjualan</Link></li>
              <li><Link to="/seller/dashboard" className="hover:text-white transition-colors">Pengiriman AnterIn</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="text-xs font-bold text-white uppercase tracking-wider mb-3">Bantuan</h4>
            <ul className="space-y-2 text-xs">
              <li><span className="hover:text-white cursor-pointer">Syarat & Ketentuan</span></li>
              <li><span className="hover:text-white cursor-pointer">Kebijakan Privasi</span></li>
              <li><span className="hover:text-white cursor-pointer">Pusat Resolusi</span></li>
              <li><span className="hover:text-white cursor-pointer">Hubungi Kami</span></li>
            </ul>
          </div>
        </div>

        <div className="border-t border-slate-800 mt-10 pt-6 flex flex-col md:flex-row items-center justify-between text-xs text-slate-500">
          <p>&copy; {new Date().getFullYear()} BeliIn Marketplace. Seluruh hak cipta dilindungi undang-undang.</p>
          <div className="flex items-center gap-2 mt-2 md:mt-0">
            <Award className="w-4 h-4 text-emerald-500" />
            <span>Marketplace Terverifikasi & Terintegrasi AnterIn Logistics</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
