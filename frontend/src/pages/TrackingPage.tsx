import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Truck, Search, Send, AlertCircle } from 'lucide-react';
import { trackingApi } from '../api/tracking';
import { Shipment } from '../types';
import { TrackingTimeline } from '../components/tracking/TrackingTimeline';

export const TrackingPage: React.FC = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [trackingNumber, setTrackingNumber] = useState(searchParams.get('tracking_number') || '');
  const [shipment, setShipment] = useState<Shipment | null>(null);
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Simulation state
  const [simLocation, setSimLocation] = useState('Hub Transit Jakarta Selatan');
  const [simDescription, setSimDescription] = useState('Paket telah tiba di fasilitas penyortiran AnterIn.');
  const [simStatus, setSimStatus] = useState('in_transit');
  const [isSimulating, setIsSimulating] = useState(false);

  useEffect(() => {
    const q = searchParams.get('tracking_number');
    if (q) {
      setTrackingNumber(q);
      handleTrack(q);
    }
  }, [searchParams]);

  const handleTrack = async (awb: string) => {
    if (!awb.trim()) return;
    setLoading(true);
    setErrorMsg(null);
    try {
      const res = await trackingApi.trackShipment(awb.trim());
      setShipment(res.data);
    } catch (err: any) {
      setErrorMsg(err.response?.data?.message || 'Nomor resi tidak ditemukan. Pastikan nomor resi benar.');
      setShipment(null);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (trackingNumber.trim()) {
      setSearchParams({ tracking_number: trackingNumber.trim() });
      handleTrack(trackingNumber.trim());
    }
  };

  const handleAdvanceSimulation = async () => {
    if (!shipment?.tracking_number) return;
    setIsSimulating(true);
    try {
      const res = await trackingApi.advanceEvent(shipment.tracking_number, {
        status: simStatus,
        location: simLocation,
        description: simDescription,
      });
      setShipment(res.data);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Gagal menambahkan event simulasi kurir.');
    } finally {
      setIsSimulating(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto py-8 space-y-8 pb-20">
      {/* Brand Logistics Banner */}
      <div className="bg-gradient-to-r from-orange-600 via-amber-600 to-orange-700 text-white rounded-3xl p-6 sm:p-10 shadow-lg relative overflow-hidden">
        <div className="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-6">
          <div className="space-y-3 text-center sm:text-left">
            <div className="inline-flex items-center gap-2 px-3 py-1 bg-white/20 rounded-full text-xs font-semibold backdrop-blur-xs">
              <Truck className="w-4 h-4 text-white" />
              <span>Layanan Pelacakan Resmi</span>
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
              Lacak Pengiriman AnterIn Express
            </h1>
            <p className="text-xs sm:text-sm text-orange-100 max-w-md leading-relaxed">
              Ketahui posisi dan riwayat perjalanan paket belanjaanmu secara akurat dan transparan.
            </p>
          </div>
          <img src="/anterin-logo-dummy.svg" alt="AnterIn" className="h-16 w-auto bg-white rounded-2xl p-2 shadow-md shrink-0" />
        </div>
      </div>

      {/* Search Bar */}
      <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <input
              type="text"
              placeholder="Masukkan Nomor Resi (Contoh: ANTERIN-EXP-...) ..."
              value={trackingNumber}
              onChange={(e) => setTrackingNumber(e.target.value)}
              className="w-full pl-11 pr-4 py-3.5 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono"
            />
            <Search className="absolute left-3.5 top-3.5 w-4 h-4 text-slate-400" />
          </div>
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-3.5 bg-orange-600 hover:bg-orange-700 text-white text-xs sm:text-sm font-bold rounded-xl shadow-md transition-colors flex items-center justify-center gap-2"
          >
            {loading ? 'Mencari...' : 'Lacak Resi'}
          </button>
        </form>

        {errorMsg && (
          <div className="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2 text-xs text-rose-800">
            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
            <span>{errorMsg}</span>
          </div>
        )}
      </div>

      {/* Shipment Details & Timeline */}
      {shipment && (
        <div className="space-y-6">
          <TrackingTimeline shipment={shipment} />

          {/* Simulation Controls for testing courier events */}
          <div className="bg-slate-900 text-white rounded-2xl p-6 shadow-lg space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-800">
              <div className="flex items-center gap-2 text-xs font-bold text-orange-400 uppercase tracking-wider">
                <Truck className="w-4 h-4" />
                <span>Simulasi Kurir AnterIn (Sandbox Testing)</span>
              </div>
              <span className="text-[10px] bg-slate-800 text-slate-400 px-2.5 py-0.5 rounded-full">
                Developer & QA Tool
              </span>
            </div>

            <p className="text-xs text-slate-300">
              Uji coba perpindahan status paket dan pembaruan riwayat lokasi kurir secara real-time:
            </p>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-[11px] text-slate-400 block mb-1">Status Kurir</label>
                <select
                  value={simStatus}
                  onChange={(e) => setSimStatus(e.target.value)}
                  className="w-full text-xs p-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white focus:outline-none focus:ring-1 focus:ring-orange-500"
                >
                  <option value="picked_up">Kurir Telah Pickup (picked_up)</option>
                  <option value="in_transit">Sedang Transit (in_transit)</option>
                  <option value="out_for_delivery">Kurir Mengantar (out_for_delivery)</option>
                  <option value="delivered">Terkirim ke Penerima (delivered)</option>
                </select>
              </div>

              <div>
                <label className="text-[11px] text-slate-400 block mb-1">Lokasi Hub / Titik</label>
                <input
                  type="text"
                  value={simLocation}
                  onChange={(e) => setSimLocation(e.target.value)}
                  className="w-full text-xs p-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white focus:outline-none focus:ring-1 focus:ring-orange-500"
                />
              </div>

              <div>
                <label className="text-[11px] text-slate-400 block mb-1">Keterangan Aktivitas</label>
                <input
                  type="text"
                  value={simDescription}
                  onChange={(e) => setSimDescription(e.target.value)}
                  className="w-full text-xs p-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white focus:outline-none focus:ring-1 focus:ring-orange-500"
                />
              </div>
            </div>

            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={handleAdvanceSimulation}
                disabled={isSimulating}
                className="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors flex items-center gap-2 disabled:opacity-50"
              >
                <Send className="w-3.5 h-3.5" />
                {isSimulating ? 'Memperbarui...' : 'Kirim Update Event Kurir'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
