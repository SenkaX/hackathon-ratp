import { Ticket } from '../types';
import { Clock, User, MapPin, Shield, Camera, Phone, QrCode, Twitter, Mail, UserCheck } from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

interface TicketCardProps {
  ticket: Ticket;
  onClick?: () => void;
}

export function TicketCard({ ticket, onClick }: TicketCardProps) {
  const priorityColors = {
    Faible: 'bg-gray-100 text-gray-700 border-gray-300',
    Moyenne: 'bg-blue-100 text-blue-700 border-blue-300',
    Haute: 'bg-orange-100 text-orange-700 border-orange-300',
    Critique: 'bg-red-100 text-red-700 border-red-300',
  };

  const categoryColors = {
    'Accident': 'bg-red-50 text-red-700',
    'Retard': 'bg-yellow-50 text-yellow-700',
    'Bagarre/Agression': 'bg-red-50 text-red-700',
    'Objet perdu': 'bg-blue-50 text-blue-700',
    'Comportement suspect': 'bg-purple-50 text-purple-700',
    'Arrêt sauté': 'bg-orange-50 text-orange-700',
    'Plainte client': 'bg-pink-50 text-pink-700',
    'Autre': 'bg-gray-50 text-gray-700',
  };

  const sourceIcons = {
    'Caméra bus': Camera,
    'Appel centrale': Phone,
    'QR Code': QrCode,
    'Réseaux sociaux': Twitter,
    'Agent infiltré': UserCheck,
    'Email': Mail,
    'Téléphone': Phone,
  };

  const SourceIcon = sourceIcons[ticket.source];

  const confidenceColor = 
    ticket.confidenceIndex >= 80 ? 'text-green-600' :
    ticket.confidenceIndex >= 60 ? 'text-yellow-600' :
    'text-red-600';

  return (
    <div
      onClick={onClick}
      className="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-all cursor-pointer group"
    >
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-2">
            <span className="text-xs font-mono text-gray-500">{ticket.id}</span>
            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${priorityColors[ticket.priority]} border`}>
              {ticket.priority}
            </span>
          </div>
          <h3 className="font-medium text-gray-900 group-hover:text-[#2f4c99] transition-colors line-clamp-2">
            {ticket.title}
          </h3>
        </div>
      </div>

      <div className="flex items-center gap-2 mb-3">
        <span className={`text-xs px-2 py-1 rounded ${categoryColors[ticket.category]}`}>
          {ticket.category}
        </span>
        {ticket.busLine && (
          <span className="text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-700 font-medium">
            Ligne {ticket.busLine}
          </span>
        )}
      </div>

      {ticket.description && (
        <p className="text-sm text-gray-600 mb-3 line-clamp-2">
          {ticket.description}
        </p>
      )}

      <div className="space-y-2 mb-3">
        {ticket.location && (
          <div className="flex items-center gap-2 text-xs text-gray-600">
            <MapPin className="w-3.5 h-3.5" />
            <span className="truncate">{ticket.location}</span>
          </div>
        )}
        <div className="flex items-center gap-2 text-xs text-gray-600">
          <SourceIcon className="w-3.5 h-3.5" />
          <span>{ticket.source}</span>
        </div>
        <div className="flex items-center gap-2 text-xs text-gray-600">
          <Clock className="w-3.5 h-3.5" />
          <span>{format(ticket.createdAt, 'dd MMM yyyy HH:mm', { locale: fr })}</span>
        </div>
      </div>

      <div className="flex items-center justify-between pt-3 border-t border-gray-100">
        <div className="flex items-center gap-2">
          <Shield className={`w-4 h-4 ${confidenceColor}`} />
          <span className={`text-xs font-medium ${confidenceColor}`}>
            Confiance: {ticket.confidenceIndex}%
          </span>
        </div>
        {ticket.assignedTo && (
          <div className="flex items-center gap-1 text-xs text-gray-500">
            <User className="w-3.5 h-3.5" />
            <span>Assigné</span>
          </div>
        )}
      </div>
    </div>
  );
}