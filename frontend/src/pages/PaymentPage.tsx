import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { CreditCard, QrCode, CheckCircle2, Clock, Copy, ArrowRight, ShieldCheck, AlertCircle } from 'lucide-react';
import { paymentApi } from '../api/payment';
import { Payment } from '../types';
import { formatRupiah, formatDate } from '../utils/formatters';

export const PaymentPage: React.FC = () => {
  const { paymentReference } = useParams<{ paymentReference: string }>();

  const [payment, setPayment] = useState<Payment | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [copied, setCopied] = useState(false);
  const [settled, setSettled] = useState(false);
  const [timeLeft, setTimeLeft] = useState<string>('15:00');

  useEffect(() => {
    async function loadPayment() {
      if (!paymentReference) return;
      try {
        const res = await paymentApi.getPayment(paymentReference);
        setPayment(res.data);
        if (res.data?.status === 'settled') {
          setSettled(true);
        }
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }
    loadPayment();
  }, [paymentReference]);

  // Countdown timer calculation
  useEffect(() => {
    if (!payment?.expires_at || settled) return;

    const interval = setInterval(() => {
      const expiry = new Date(payment.expires_at).getTime();
      const now = new Date().getTime();
      const diff = expiry - now;

      if (diff <= 0) {
        setTimeLeft('00:00 (Kedaluwarsa)');
        clearInterval(interval);
      } else {
        const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const secs = Math.floor((diff % (1000 * 60)) / 1000);
        setTimeLeft(`${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`);
      }
    }, 1000);

    return () => clearInterval(interval);
  }, [payment?.expires_at, settled]);

  const handleSimulatePayment = async () => {
    if (!paymentReference) return;
    setProcessing(true);
    try {
      await paymentApi.simulateWebhook({
        payment_reference: paymentReference,
        action: 'settle',
      });
      setSettled(true);
      // Reload payment object
      const res = await paymentApi.getPayment(paymentReference);
      setPayment(res.data);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal memproses simulasi pembayaran.');
    } finally {
      setProcessing(false);
    }
  };

  const handleCopyVa = () => {
    if (payment?.va_number) {
      navigator.clipboard.writeText(payment.va_number);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  if (loading) {
    return (
      <div className="max-w-xl mx-auto py-16 px-4 animate-pulse space-y-4">
        <div className="h-8 bg-slate-200 rounded w-1/2 mx-auto"></div>
        <div className="h-72 bg-slate-200 rounded-3xl"></div>
      </div>
    );
  }

  if (!payment) {
    return (
      <div className="max-w-xl mx-auto py-16 px-4 text-center">
        <AlertCircle className="w-12 h-12 text-rose-500 mx-auto mb-2" />
        <h2 className="text-lg font-bold text-slate-800">Tagihan Pembayaran Tidak Ditemukan</h2>
        <Link to="/" className="mt-4 inline-block text-xs font-bold text-blue-600 underline">
          Kembali ke Beranda
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-xl mx-auto py-8 px-4 space-y-6 pb-20">
      <div className="text-center space-y-1">
        <h1 className="text-2xl font-extrabold text-slate-900">
          {settled ? 'Pembayaran Berhasil!' : 'Selesaikan Pembayaran'}
        </h1>
        <p className="text-xs text-slate-500">
          Ref: <span className="font-mono font-semibold text-slate-800">{payment.payment_reference}</span>
        </p>
      </div>

      {settled ? (
        <div className="bg-white rounded-3xl border border-emerald-200 p-8 shadow-sm text-center space-y-6">
          <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto animate-bounce">
            <CheckCircle2 className="w-10 h-10" />
          </div>

          <div className="space-y-1">
            <h3 className="text-lg font-bold text-slate-900">Transaksi Telah Lunas</h3>
            <p className="text-xs text-slate-500">
              Stok produk telah berhasil dikomit dan pesanan diteruskan ke toko penjual untuk diproses.
            </p>
          </div>

          <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-xs space-y-2">
            <div className="flex justify-between">
              <span className="text-slate-500">Total Dibayar</span>
              <strong className="text-slate-900">{formatRupiah(payment.amount)}</strong>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Waktu Bayar</span>
              <strong className="text-slate-900">{formatDate(payment.paid_at || new Date().toISOString())}</strong>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Metode</span>
              <strong className="text-slate-900">{payment.channel || 'QRIS / VA'}</strong>
            </div>
          </div>

          <div className="pt-2 flex flex-col sm:flex-row gap-3">
            <Link
              to="/orders"
              className="flex-1 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold text-xs shadow-md transition-all flex items-center justify-center gap-1.5"
            >
              Lihat Daftar Pesanan
              <ArrowRight className="w-4 h-4" />
            </Link>
            <Link
              to="/"
              className="py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-bold text-xs transition-all"
            >
              Belanja Lagi
            </Link>
          </div>
        </div>
      ) : (
        <div className="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
          {/* Amount and Timer */}
          <div className="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <span className="text-xs text-slate-500 block">Total Tagihan</span>
              <span className="text-2xl font-black text-blue-700">{formatRupiah(payment.amount)}</span>
            </div>

            <div className="text-right">
              <span className="text-xs text-slate-500 flex items-center gap-1 justify-end">
                <Clock className="w-3.5 h-3.5 text-amber-500" /> Sisa Waktu Bayar
              </span>
              <span className="text-sm font-mono font-bold text-rose-600">{timeLeft}</span>
            </div>
          </div>

          {/* Payment Method Details (QRIS & Virtual Account) */}
          <div className="space-y-4">
            <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-4 text-center">
              <div className="flex items-center justify-center gap-2 text-xs font-bold text-slate-800">
                <QrCode className="w-4 h-4 text-blue-600" />
                <span>Pindai QRIS (Semua E-Wallet & Mobile Banking)</span>
              </div>

              {/* QR Dummy visual */}
              <div className="inline-block p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
                <img
                  src={`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(
                    payment.qr_code || `BELIIN-PAY-${payment.payment_reference}`
                  )}`}
                  alt="QRIS Payment"
                  className="w-44 h-44 object-contain mx-auto"
                />
              </div>

              {/* Virtual Account number */}
              {payment.va_number && (
                <div className="pt-2 border-t border-slate-200">
                  <span className="text-[11px] text-slate-500 block mb-1">Atau transfer melalui Virtual Account:</span>
                  <div className="flex items-center justify-center gap-2">
                    <code className="text-base font-mono font-bold text-slate-900 bg-white px-3 py-1 rounded-lg border border-slate-200">
                      {payment.va_number}
                    </code>
                    <button
                      type="button"
                      onClick={handleCopyVa}
                      className="p-1.5 text-xs text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center gap-1"
                    >
                      <Copy className="w-3.5 h-3.5" />
                      {copied ? 'Tersalin!' : 'Salin'}
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Simulation Action Button */}
            <div className="p-4 bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
              <div className="flex items-center gap-2 text-xs font-bold text-amber-900">
                <CreditCard className="w-4 h-4 text-amber-700" />
                <span>Simulasi Pembayaran (Sandbox Gateway)</span>
              </div>
              <p className="text-[11px] text-amber-800">
                Klik tombol di bawah ini untuk mensimulasikan notifikasi webhook pembayaran lunas seketika dari payment gateway.
              </p>
              <button
                type="button"
                onClick={handleSimulatePayment}
                disabled={processing}
                className="w-full mt-2 py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl shadow-md text-xs transition-all flex items-center justify-center gap-2 disabled:opacity-60"
              >
                {processing ? 'Menjalankan Webhook...' : '⚡ Bayar Sekarang (Simulasi Lunas)'}
              </button>
            </div>
          </div>

          <div className="pt-2 flex items-center justify-center gap-1.5 text-[11px] text-slate-400">
            <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
            <span>Koneksi terenkripsi & transaksi terjamin aman 100%</span>
          </div>
        </div>
      )}
    </div>
  );
};
