import { HotSpot } from '../types';
import { MapPin, TrendingUp } from 'lucide-react';

interface HotSpotsCardProps {
  hotSpots: HotSpot[];
}

export function HotSpotsCard({ hotSpots }: HotSpotsCardProps) {
  const severityColors = {
    Faible: 'bg-yellow-100 text-yellow-700',
    Moyenne: 'bg-orange-100 text-orange-700',
    Haute: 'bg-red-100 text-red-700',
  };

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div className="flex items-center gap-2 mb-4">
        <TrendingUp className="w-5 h-5 text-red-500" />
        <h3 className="text-lg font-semibold">Points chauds détectés</h3>
      </div>
      <div className="space-y-3">
        {hotSpots.map((spot, index) => (
          <div key={index} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
            <div className="flex items-start justify-between mb-2">
              <div className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-gray-500 flex-shrink-0 mt-0.5" />
                <span className="font-medium text-gray-900">{spot.location}</span>
              </div>
              <span className={`text-xs px-2 py-1 rounded-full font-medium ${severityColors[spot.severity]}`}>
                {spot.severity}
              </span>
            </div>
            <div className="ml-6 text-sm text-gray-600">
              <div className="flex items-center justify-between">
                <span>{spot.timeSlot}</span>
                <span className="font-semibold text-red-600">{spot.incidents} incidents</span>
              </div>
            </div>
          </div>
        ))}
      </div>
      <div className="mt-4 pt-4 border-t border-gray-200">
        <p className="text-sm text-gray-600">
          💡 Ces zones nécessitent une attention particulière aux horaires indiqués.
        </p>
      </div>
    </div>
  );
}
