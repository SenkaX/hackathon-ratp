import { useState } from 'react';
import { KanbanBoard } from '../components/KanbanBoard';
import { TicketDetailModal } from '../components/TicketDetailModal';
import { tickets as initialTickets, projects } from '../data/mockData';
import { Ticket, TicketStatus } from '../types';
import { Filter, Plus } from 'lucide-react';

export function KanbanView() {
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
  const [ticketsData, setTicketsData] = useState(initialTickets);
  const [selectedProject, setSelectedProject] = useState<string>('all');

  const handleStatusChange = (ticketId: string, newStatus: TicketStatus) => {
    setTicketsData(prevTickets =>
      prevTickets.map(ticket =>
        ticket.id === ticketId
          ? { ...ticket, status: newStatus, updatedAt: new Date() }
          : ticket
      )
    );
    if (selectedTicket?.id === ticketId) {
      setSelectedTicket(prev => prev ? { ...prev, status: newStatus } : null);
    }
  };

  const filteredTickets = selectedProject === 'all'
    ? ticketsData
    : ticketsData.filter(ticket => ticket.projectId === selectedProject);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Vue Kanban</h1>
          <p className="text-gray-600 mt-1">Glissez-déposez les tickets pour changer leur statut</p>
        </div>
        <div className="flex items-center gap-3">
          <select
            value={selectedProject}
            onChange={(e) => setSelectedProject(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="all">Tous les projets</option>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>
          <button className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <Filter className="w-4 h-4" />
            Filtres
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-[#2f4c99] text-white rounded-lg hover:bg-[#253a75] transition-colors">
            <Plus className="w-4 h-4" />
            Nouveau ticket
          </button>
        </div>
      </div>

      <div className="bg-gray-50 rounded-lg p-4">
        <div className="flex items-center justify-between text-sm">
          <span className="text-gray-600">
            Total: <span className="font-semibold text-gray-900">{filteredTickets.length}</span> tickets
          </span>
          <span className="text-gray-600">
            Projet sélectionné: <span className="font-semibold text-gray-900">
              {selectedProject === 'all' 
                ? 'Tous les projets' 
                : projects.find(p => p.id === selectedProject)?.name}
            </span>
          </span>
        </div>
      </div>

      <KanbanBoard
        tickets={filteredTickets}
        onTicketClick={setSelectedTicket}
        onStatusChange={handleStatusChange}
      />

      <TicketDetailModal
        ticket={selectedTicket}
        onClose={() => setSelectedTicket(null)}
        onStatusChange={handleStatusChange}
      />
    </div>
  );
}