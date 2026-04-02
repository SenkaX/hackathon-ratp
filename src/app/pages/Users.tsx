import { users, tickets } from '../data/mockData';
import { User, Mail, Shield, Activity } from 'lucide-react';

export function Users() {
  const getUserStats = (userId: string) => {
    const userTickets = tickets.filter(t => t.assignedTo === userId);
    const createdTickets = tickets.filter(t => t.createdBy === userId);
    
    return {
      assigned: userTickets.length,
      created: createdTickets.length,
      pending: userTickets.filter(t => t.status === 'En attente de validation').length,
      inProgress: userTickets.filter(t => t.status === 'En cours').length,
    };
  };

  const roleColors = {
    Direction: 'bg-purple-100 text-purple-700 border-purple-300',
    RH: 'bg-blue-100 text-blue-700 border-blue-300',
    Stagiaire: 'bg-green-100 text-green-700 border-green-300',
    Admin: 'bg-red-100 text-red-700 border-red-300',
  };

  const roleDescriptions = {
    Direction: 'Accès complet - Validation et décisions stratégiques',
    RH: 'Gestion des incidents RH et comportements',
    Stagiaire: 'Consultation et assistance limitée',
    Admin: 'Accès système et IA',
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Utilisateurs</h1>
          <p className="text-gray-600 mt-1">Gestion des accès et rôles</p>
        </div>
        <button className="px-4 py-2 bg-[#2f4c99] text-white rounded-lg hover:bg-[#253a75] transition-colors">
          + Nouvel utilisateur
        </button>
      </div>

      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div className="flex items-center gap-2 mb-2">
          <Shield className="w-5 h-5 text-yellow-700" />
          <h3 className="font-semibold text-yellow-900">Accès différenciés par rôle</h3>
        </div>
        <p className="text-sm text-yellow-800">
          Chaque rôle dispose de permissions spécifiques pour garantir la confidentialité des données
          et le respect du RGPD. Les stagiaires ont un accès limité, tandis que la direction peut
          valider les actions sensibles.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {users.map(user => {
          const stats = getUserStats(user.id);
          
          return (
            <div
              key={user.id}
              className="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all"
            >
              <div className="p-6">
                <div className="flex items-start gap-4 mb-4">
                  <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <User className="w-8 h-8 text-white" />
                  </div>
                  <div className="flex-1">
                    <h3 className="font-semibold text-lg mb-1">{user.name}</h3>
                    <div className="flex items-center gap-2 mb-2">
                      <span className={`text-xs px-2.5 py-1 rounded-full font-medium border ${roleColors[user.role]}`}>
                        {user.role}
                      </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <Mail className="w-4 h-4" />
                      <span>{user.email}</span>
                    </div>
                  </div>
                </div>

                <div className="bg-gray-50 rounded-lg p-3 mb-4">
                  <p className="text-xs text-gray-600">{roleDescriptions[user.role]}</p>
                </div>

                {user.role !== 'Admin' && (
                  <div className="grid grid-cols-2 gap-3">
                    <div className="bg-blue-50 rounded-lg p-3">
                      <div className="flex items-center gap-1 text-blue-700 mb-1">
                        <Activity className="w-3.5 h-3.5" />
                        <span className="text-xs font-medium">Assignés</span>
                      </div>
                      <p className="text-2xl font-semibold text-blue-900">{stats.assigned}</p>
                    </div>
                    <div className="bg-green-50 rounded-lg p-3">
                      <div className="flex items-center gap-1 text-green-700 mb-1">
                        <Activity className="w-3.5 h-3.5" />
                        <span className="text-xs font-medium">Créés</span>
                      </div>
                      <p className="text-2xl font-semibold text-green-900">{stats.created}</p>
                    </div>
                    <div className="bg-yellow-50 rounded-lg p-3">
                      <div className="flex items-center gap-1 text-yellow-700 mb-1">
                        <Activity className="w-3.5 h-3.5" />
                        <span className="text-xs font-medium">En attente</span>
                      </div>
                      <p className="text-2xl font-semibold text-yellow-900">{stats.pending}</p>
                    </div>
                    <div className="bg-purple-50 rounded-lg p-3">
                      <div className="flex items-center gap-1 text-purple-700 mb-1">
                        <Activity className="w-3.5 h-3.5" />
                        <span className="text-xs font-medium">En cours</span>
                      </div>
                      <p className="text-2xl font-semibold text-purple-900">{stats.inProgress}</p>
                    </div>
                  </div>
                )}

                {user.role === 'Admin' && (
                  <div className="bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg p-4 border border-blue-100">
                    <p className="text-sm text-gray-700">
                      🤖 Compte système pour l'assistant IA qui crée automatiquement les tickets
                      détectés par les caméras, appels centrale, et autres sources.
                    </p>
                  </div>
                )}
              </div>

              <div className="px-6 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg flex justify-between">
                <button className="text-sm font-medium text-[#2f4c99] hover:text-[#253a75]">
                  Modifier les permissions
                </button>
                <button className="text-sm font-medium text-gray-600 hover:text-gray-700">
                  Voir l'activité
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}