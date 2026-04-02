import { projects, tickets } from '../data/mockData';
import { MapPin, Bus, Activity, AlertCircle } from 'lucide-react';
import { Link } from 'react-router';

export function Projects() {
  const getProjectStats = (projectId: string) => {
    const projectTickets = tickets.filter(t => t.projectId === projectId);
    return {
      total: projectTickets.length,
      pending: projectTickets.filter(t => t.status === 'En attente de validation').length,
      inProgress: projectTickets.filter(t => t.status === 'En cours').length,
      critical: projectTickets.filter(t => t.priority === 'Critique').length,
    };
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Projets</h1>
          <p className="text-gray-600 mt-1">Gestion des incidents par station de bus</p>
        </div>
        <button className="px-4 py-2 bg-[#2f4c99] text-white rounded-lg hover:bg-[#253a75] transition-colors">
          + Nouveau projet
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {projects.map(project => {
          const stats = getProjectStats(project.id);
          
          return (
            <div
              key={project.id}
              className="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all"
            >
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                      <MapPin className="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-lg">{project.name}</h3>
                      <p className="text-sm text-gray-500">{project.station}</p>
                    </div>
                  </div>
                </div>

                <p className="text-sm text-gray-600 mb-4">{project.description}</p>

                <div className="mb-4">
                  <div className="flex items-center gap-2 mb-2">
                    <Bus className="w-4 h-4 text-gray-500" />
                    <span className="text-sm font-medium text-gray-700">Lignes desservies</span>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {project.busLines.slice(0, 6).map(line => (
                      <span
                        key={line}
                        className="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs font-medium rounded"
                      >
                        {line}
                      </span>
                    ))}
                    {project.busLines.length > 6 && (
                      <span className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                        +{project.busLines.length - 6}
                      </span>
                    )}
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                  <div>
                    <div className="flex items-center gap-1 text-gray-600 mb-1">
                      <Activity className="w-3.5 h-3.5" />
                      <span className="text-xs">Total</span>
                    </div>
                    <p className="text-xl font-semibold">{stats.total}</p>
                  </div>
                  <div>
                    <div className="flex items-center gap-1 text-yellow-600 mb-1">
                      <Activity className="w-3.5 h-3.5" />
                      <span className="text-xs">En attente</span>
                    </div>
                    <p className="text-xl font-semibold">{stats.pending}</p>
                  </div>
                  <div>
                    <div className="flex items-center gap-1 text-blue-600 mb-1">
                      <Activity className="w-3.5 h-3.5" />
                      <span className="text-xs">En cours</span>
                    </div>
                    <p className="text-xl font-semibold">{stats.inProgress}</p>
                  </div>
                  <div>
                    <div className="flex items-center gap-1 text-red-600 mb-1">
                      <AlertCircle className="w-3.5 h-3.5" />
                      <span className="text-xs">Critiques</span>
                    </div>
                    <p className="text-xl font-semibold">{stats.critical}</p>
                  </div>
                </div>
              </div>

              <div className="px-6 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg">
                <Link
                  to="/kanban"
                  className="text-sm font-medium text-[#2f4c99] hover:text-[#253a75]"
                >
                  Voir les tickets →
                </Link>
              </div>
            </div>
          );
        })}
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 className="font-semibold mb-2">📍 Périmètre Hackathon (5 jours)</h3>
        <p className="text-sm text-gray-700">
          Pour le prototype, nous nous concentrons sur 3 stations de bus parisiennes majeures.
          Chaque station représente un projet distinct avec ses propres lignes et incidents à gérer.
          Cette approche permet de démontrer la scalabilité du système à l'ensemble du réseau RATP.
        </p>
      </div>
    </div>
  );
}