import { TrendingUp, AlertCircle, Clock, CheckCircle2, Activity, Shield } from 'lucide-react';
import { DashboardStats as DashboardStatsType } from '../types';

interface DashboardStatsProps {
  stats: DashboardStatsType;
}

export function DashboardStats({ stats }: DashboardStatsProps) {
  const statItems = [
    {
      title: 'Total Tickets',
      value: stats.totalTickets,
      icon: Activity,
      color: 'bg-blue-500',
    },
    {
      title: 'En attente validation',
      value: stats.pendingValidation,
      icon: Clock,
      color: 'bg-yellow-500',
    },
    {
      title: 'En cours',
      value: stats.inProgress,
      icon: TrendingUp,
      color: 'bg-purple-500',
    },
    {
      title: 'Résolus',
      value: stats.resolved,
      icon: CheckCircle2,
      color: 'bg-green-500',
    },
    {
      title: 'Tickets critiques',
      value: stats.criticalTickets,
      icon: AlertCircle,
      color: 'bg-red-500',
    },
    {
      title: 'Indice confiance moy.',
      value: `${stats.averageConfidenceIndex}%`,
      icon: Shield,
      color: 'bg-indigo-500',
    },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
      {statItems.map((stat, index) => (
        <div
          key={index}
          className="bg-white rounded-lg p-6 shadow-sm border border-gray-200 hover:shadow-md transition-shadow"
        >
          <div className="flex items-center justify-between mb-3">
            <div className={`${stat.color} p-2 rounded-lg`}>
              <stat.icon className="w-5 h-5 text-white" />
            </div>
          </div>
          <div className="text-2xl font-semibold mb-1">{stat.value}</div>
          <div className="text-sm text-gray-600">{stat.title}</div>
        </div>
      ))}
    </div>
  );
}
