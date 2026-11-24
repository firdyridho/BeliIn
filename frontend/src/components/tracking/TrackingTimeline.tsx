import React from 'react';
import { CheckCircle2, Clock, Truck, Package, MapPin, AlertCircle } from 'lucide-react';
import { Shipment } from '../../types';
import { formatDate } from '../../utils/formatters';

interface TrackingTimelineProps {
  shipment: Shipment;
}

export const TrackingTimeline: React.FC<TrackingTimelineProps> = ({ shipment }) => {
  const s = shipment as any;
  const events = s.events || s.tracking_events || [];
  const providerName = s.provider_name || s.provider?.name || 'AnterIn Express';
  const serviceName = s.service_name || s.service?.service_name || 'Reguler';

  const getStatusIcon = (status: string, isLatest: boolean) => {
    switch (status) {
      case 'delivered':
        return <CheckCircle2 className={`w-5 h-5 ${isLatest ? 'text-emerald-600' : 'text-slate-400'}`} />;
      case 'out_for_delivery':
      case 'in_transit':
        return <Truck className={`w-5 h-5 ${isLatest ? 'text-orange-600 animate-bounce' : 'text-slate-400'}`} />;
      case 'picked_up':
        return <Package className={`w-5 h-5 ${isLatest ? 'text-blue-600' : 'text-slate-400'}`} />;
      case 'failed':
        return <AlertCircle className="w-5 h-5 text-rose-600" />;
      default:
        return <Clock className={`w-5 h-5 ${isLatest ? 'text-amber-600' : 'text-slate-400'}`} />;
    }
  };

  return (
    <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
      <div className="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
            <Truck className="w-5 h-5 text-orange-600" />
          </div>
          <div>
            <h4 className="text-sm font-bold text-slate-800">
              {providerName} - {serviceName}
            </h4>
            <p className="text-xs text-slate-500">
              No. Resi: <span className="font-mono font-semibold text-slate-900">{shipment.tracking_number}</span>
            </p>
          </div>
        </div>
        <div className="text-right">
          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
            {shipment.status.replace('_', ' ').toUpperCase()}
          </span>
        </div>
      </div>

      {events.length === 0 ? (
        <div className="text-center py-8 text-slate-400">
          <Clock className="w-8 h-8 mx-auto mb-2 opacity-50" />
          <p className="text-xs">Belum ada riwayat pergerakan untuk paket ini.</p>
        </div>
      ) : (
        <div className="relative pl-6 space-y-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
          {events.map((event: any, idx: number) => {
            const isLatest = idx === events.length - 1;
            const evStatus = event.event_code || event.status || 'in_transit';
            const evLocation = event.location || 'Hub Transit AnterIn';
            const evTime = event.event_at || event.event_time;
            const evDesc = event.description || event.event_description || 'Paket dalam proses pengantaran';

            return (
              <div key={event.id || idx} className="relative flex items-start gap-4">
                <div className={`absolute -left-[23px] top-0 w-6 h-6 rounded-full flex items-center justify-center bg-white border-2 ${isLatest ? 'border-orange-500' : 'border-slate-300'}`}>
                  {getStatusIcon(evStatus, isLatest)}
                </div>
                <div className="flex-1 bg-slate-50 rounded-xl p-3 border border-slate-100">
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xs font-bold text-slate-900 flex items-center gap-1">
                      <MapPin className="w-3.5 h-3.5 text-slate-500" />
                      {evLocation}
                    </span>
                    <span className="text-[11px] text-slate-400 font-medium">
                      {formatDate(evTime)}
                    </span>
                  </div>
                  <p className="text-xs text-slate-600 leading-relaxed">
                    {evDesc}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};
