import { Link, useLocation } from 'react-router';
import { LayoutDashboard, Kanban, FolderKanban, Settings, Users } from 'lucide-react';

export function Sidebar() {
  const location = useLocation();

  const menuItems = [
    { path: '/', label: 'Tableau de bord', icon: LayoutDashboard },
    { path: '/kanban', label: 'Vue Kanban', icon: Kanban },
    { path: '/projects', label: 'Projets', icon: FolderKanban },
    { path: '/users', label: 'Utilisateurs', icon: Users },
    { path: '/settings', label: 'Paramètres', icon: Settings },
  ];

  return (
    <aside className="w-64 bg-white border-r border-gray-200 min-h-screen p-6">
      <div className="mb-8">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
            <span className="text-white font-bold text-xl">R</span>
          </div>
          <div>
            <h1 className="font-bold text-lg">RATP Bus</h1>
            <p className="text-xs text-gray-500">Gestion des incidents</p>
          </div>
        </div>
      </div>

      <nav className="space-y-1">
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.path;
          
          return (
            <Link
              key={item.path}
              to={item.path}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-colors ${
                isActive
                  ? 'bg-[#e8ecf7] text-[#2f4c99] font-medium'
                  : 'text-gray-700 hover:bg-gray-50'
              }`}
            >
              <Icon className="w-5 h-5" />
              <span>{item.label}</span>
            </Link>
          );
        })}
      </nav>

      <div className="mt-8 pt-8 border-t border-gray-200">
        <div className="bg-gradient-to-br from-[#e8ecf7] to-purple-50 rounded-lg p-4 border border-[#c5cfe8]">
          <h3 className="font-semibold text-sm mb-2">🤖 Assistant IA</h3>
          <p className="text-xs text-gray-600 mb-3">
            L'IA détecte automatiquement les incidents et pré-remplit les tickets.
          </p>
          <button className="w-full bg-[#2f4c99] text-white text-xs py-2 rounded-lg hover:bg-[#253a75] transition-colors">
            En savoir plus
          </button>
        </div>
      </div>
    </aside>
  );
}