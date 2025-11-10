import React from 'react';
import Card from '../ui/Card';
import type { ShipmentMix } from '../../types/dashboard';

interface ShipmentMixPanelProps {
  mix?: ShipmentMix | null;
}

const colorMap: Record<string, string> = {
  groupage: 'bg-mono-black',
  individual: 'bg-mono-gray-700',
};

const ShipmentMixPanel: React.FC<ShipmentMixPanelProps> = ({ mix }) => {
  if (!mix) {
    return (
      <Card className="p-6 border border-mono-gray-200 shadow-sm">
        <div className="animate-pulse space-y-4">
          <div className="h-4 w-24 rounded-full bg-mono-gray-200" />
          <div className="h-8 w-32 rounded-full bg-mono-gray-200" />
          <div className="h-32 rounded-2xl bg-mono-gray-100" />
        </div>
      </Card>
    );
  }

  const total = mix.distribution.reduce((sum, entry) => sum + entry.count, 0);

  return (
    <Card className="p-6 border border-mono-gray-200 shadow-sm">
      <div className="space-y-4">
        <div className="flex flex-col gap-2">
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">
            Shipment Mix
          </p>
          <h3 className="text-2xl font-semibold text-mono-black">
            {total.toLocaleString()} bookings
          </h3>
          <p className="text-sm text-mono-gray-600">
            Window {mix.window.from} → {mix.window.to}
          </p>
        </div>

        <div className="space-y-3">
          {mix.distribution.map((entry) => (
            <div key={entry.mode}>
              <div className="flex items-center justify-between text-sm text-mono-gray-600">
                <span className="font-semibold text-mono-black">{entry.label}</span>
                <span>{entry.percentage?.toFixed(1)}%</span>
              </div>
              <div className="mt-1 h-2 rounded-full bg-mono-gray-100">
                <div
                  className={`${colorMap[entry.mode] ?? 'bg-mono-black'} h-full rounded-full transition-all`}
                  style={{ width: `${entry.percentage ?? 0}%` }}
                />
              </div>
              <div className="mt-1 flex items-center justify-between text-xs text-mono-gray-500">
                <span>{entry.count.toLocaleString()} shipments</span>
                <span>{entry.active.toLocaleString()} active</span>
              </div>
            </div>
          ))}
        </div>

        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500 mb-2">
            7‑Day Trend
          </p>
          <div className="grid gap-2 text-sm text-mono-gray-600 md:grid-cols-2">
            {mix.trend.slice(-6).map((entry) => (
              <div
                key={entry.date}
                className="flex items-center justify-between rounded-2xl border border-mono-gray-200 px-3 py-2"
              >
                <span className="font-medium">{entry.date.slice(5)}</span>
                <div className="text-right">
                  <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Groupage</p>
                  <p className="font-semibold text-mono-black">{entry.groupage}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Individual</p>
                  <p className="font-semibold text-mono-black">{entry.individual}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Card>
  );
};

export default ShipmentMixPanel;
