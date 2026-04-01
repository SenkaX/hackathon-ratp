import { Ticket, TicketStatus } from '../types';
import { X, Calendar, User, MapPin, Shield, Tag, AlertCircle, FileText, Paperclip } from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { users } from '../data/mockData';

interface TicketDetailModalProps {
  ticket: Ticket | null;
  onClose: () => void;
  onStatusChange: (ticketId: string, newStatus: TicketStatus) => void;
}

export function TicketDetailModal({ ticket, onClose, onStatusChange }: TicketDetailModalProps) {
  if (!ticket) return null;

  const assignedUser = ticket.assignedTo 
    ? users.find(u => u.id === ticket.assignedTo)
    : null;

  const createdByUser = users.find(u => u.id === ticket.createdBy);

  const statusOptions: TicketStatus[] = [
    'En attente de validation',
    'Validé',
    'En cours',
    'Classé sans suite',
    'Transmis au juridique',
    'Résolu',
  ];

  const priorityColors = {
    Faible: 'bg-gray-100 text-gray-700',
    Moyenne: 'bg-blue-100 text-blue-700',
    Haute: 'bg-orange-100 text-orange-700',
    Critique: 'bg-red-100 text-red-700',
  };

  const confidenceColor = 
    ticket.confidenceIndex >= 80 ? 'text-green-600 bg-green-50' :
    ticket.confidenceIndex >= 60 ? 'text-yellow-600 bg-yellow-50' :
    'text-red-600 bg-red-50';

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white border-b border-gray-200 p-6 flex items-center justify-between">
          <div>
            <span className="text-sm font-mono text-gray-500">{ticket.id}</span>
            <h2 className="text-2xl font-semibold mt-1">{ticket.title}</h2>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        <div className="p-6 space-y-6">
          {/* Status and Priority */}
          <div className="flex gap-3 flex-wrap">
            <div>
              <label className="text-sm font-medium text-gray-700 block mb-2">Statut</label>
              <select
                value={ticket.status}
                onChange={(e) => onStatusChange(ticket.id, e.target.value as TicketStatus)}
                className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                {statusOptions.map((status) => (
                  <option key={status} value={status}>
                    {status}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-gray-700 block mb-2">Priorité</label>
              <span className={`inline-flex items-center px-3 py-2 rounded-lg font-medium ${priorityColors[ticket.priority]}`}>
                {ticket.priority}
              </span>
            </div>
            <div>
              <label className="text-sm font-medium text-gray-700 block mb-2">Catégorie</label>
              <span className="inline-flex items-center px-3 py-2 rounded-lg bg-purple-100 text-purple-700 font-medium">
                {ticket.category}
              </span>
            </div>
          </div>

          {/* Confidence Index */}
          <div className={`p-4 rounded-lg ${confidenceColor} border-l-4 ${ticket.confidenceIndex >= 80 ? 'border-green-500' : ticket.confidenceIndex >= 60 ? 'border-yellow-500' : 'border-red-500'}`}>
            <div className="flex items-center gap-2">
              <Shield className="w-5 h-5" />
              <span className="font-semibold">Indice de confiance : {ticket.confidenceIndex}%</span>
            </div>
            <p className="text-sm mt-1 opacity-90">
              {ticket.confidenceIndex >= 80 
                ? 'Source fiable - Priorisation haute recommandée'
                : ticket.confidenceIndex >= 60
                ? 'Source modérément fiable - Vérification conseillée'
                : 'Source peu fiable - Vérification approfondie nécessaire'}
            </p>
          </div>

          {/* Description */}
          <div>
            <div className="flex items-center gap-2 mb-2">
              <FileText className="w-5 h-5 text-gray-500" />
              <h3 className="font-semibold">Description</h3>
            </div>
            <p className="text-gray-700 bg-gray-50 p-4 rounded-lg">{ticket.description}</p>
          </div>

          {/* AI Summary */}
          {ticket.aiSummary && (
            <div>
              <div className="flex items-center gap-2 mb-2">
                <AlertCircle className="w-5 h-5 text-blue-500" />
                <h3 className="font-semibold">Résumé IA et recommandations</h3>
              </div>
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-gray-700">{ticket.aiSummary}</p>
              </div>
            </div>
          )}

          {/* Details */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <Tag className="w-4 h-4 text-gray-500" />
                <span className="text-sm font-medium text-gray-700">Source</span>
              </div>
              <p className="text-gray-900">{ticket.source}</p>
            </div>
            {ticket.busLine && (
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <Tag className="w-4 h-4 text-gray-500" />
                  <span className="text-sm font-medium text-gray-700">Ligne de bus</span>
                </div>
                <p className="text-gray-900">Ligne {ticket.busLine} {ticket.busNumber && `- Bus ${ticket.busNumber}`}</p>
              </div>
            )}
            {ticket.location && (
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <MapPin className="w-4 h-4 text-gray-500" />
                  <span className="text-sm font-medium text-gray-700">Localisation</span>
                </div>
                <p className="text-gray-900">{ticket.location}</p>
              </div>
            )}
            <div>
              <div className="flex items-center gap-2 mb-2">
                <Calendar className="w-4 h-4 text-gray-500" />
                <span className="text-sm font-medium text-gray-700">Créé le</span>
              </div>
              <p className="text-gray-900">{format(ticket.createdAt, 'dd MMMM yyyy à HH:mm', { locale: fr })}</p>
            </div>
            {createdByUser && (
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <User className="w-4 h-4 text-gray-500" />
                  <span className="text-sm font-medium text-gray-700">Créé par</span>
                </div>
                <p className="text-gray-900">{createdByUser.name} ({createdByUser.role})</p>
              </div>
            )}
            {assignedUser && (
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <User className="w-4 h-4 text-gray-500" />
                  <span className="text-sm font-medium text-gray-700">Assigné à</span>
                </div>
                <p className="text-gray-900">{assignedUser.name} ({assignedUser.role})</p>
              </div>
            )}
          </div>

          {/* Reporter Info */}
          {(ticket.reporterName || ticket.reporterContact) && (
            <div className="bg-gray-50 rounded-lg p-4">
              <h3 className="font-semibold mb-3">Informations du rapporteur</h3>
              <div className="space-y-2">
                {ticket.reporterName && (
                  <div>
                    <span className="text-sm text-gray-600">Nom : </span>
                    <span className="text-gray-900">{ticket.reporterName}</span>
                  </div>
                )}
                {ticket.reporterContact && (
                  <div>
                    <span className="text-sm text-gray-600">Contact : </span>
                    <span className="text-gray-900">{ticket.reporterContact}</span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Attachments */}
          {ticket.attachments && ticket.attachments.length > 0 && (
            <div>
              <div className="flex items-center gap-2 mb-2">
                <Paperclip className="w-5 h-5 text-gray-500" />
                <h3 className="font-semibold">Pièces jointes ({ticket.attachments.length})</h3>
              </div>
              <div className="space-y-2">
                {ticket.attachments.map((attachment, index) => (
                  <div key={index} className="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <Paperclip className="w-4 h-4 text-gray-500" />
                    <span className="text-sm text-gray-700">{attachment}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        <div className="sticky bottom-0 bg-gray-50 border-t border-gray-200 p-6 flex justify-end gap-3">
          <button
            onClick={onClose}
            className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"
          >
            Fermer
          </button>
          <button
            className="px-4 py-2 bg-[#2f4c99] text-white rounded-lg hover:bg-[#253a75] transition-colors"
          >
            Sauvegarder les modifications
          </button>
        </div>
      </div>
    </div>
  );
}