import { useState } from 'react';
import { DashboardStats } from '../components/DashboardStats';
import { HotSpotsCard } from '../components/HotSpotsCard';
import { TicketCard } from '../components/TicketCard';
import { TicketDetailModal } from '../components/TicketDetailModal';
import { tickets, hotSpots } from '../data/mockData';
import { Ticket, TicketStatus, DashboardStats as DashboardStatsType } from '../types';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { Filter, TrendingUp } from 'lucide-react';

export function Dashboard() {
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
  const [ticketsData, setTicketsData] = useState(tickets);

  const stats: DashboardStatsType = {
    totalTickets: ticketsData.length,
    pendingValidation: ticketsData.filter(t => t.status === 'En attente de validation').length,
    inProgress: ticketsData.filter(t => t.status === 'En cours').length,
    resolved: ticketsData.filter(t => t.status === 'Résolu').length,
    criticalTickets: ticketsData.filter(t => t.priority === 'Critique').length,
    averageConfidenceIndex: Math.round(
      ticketsData.reduce((sum, t) => sum + t.confidenceIndex, 0) / ticketsData.length
    ),
  };

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

  // Data for category chart
  const categoryData = Object.entries(
    ticketsData.reduce((acc, ticket) => {
      acc[ticket.category] = (acc[ticket.category] || 0) + 1;
      return acc;
    }, {} as Record<string, number>)
  ).map(([name, value]) => ({ name, value }));

  // Data for status chart
  const statusData = Object.entries(
    ticketsData.reduce((acc, ticket) => {
      acc[ticket.status] = (acc[ticket.status] || 0) + 1;
      return acc;
    }, {} as Record<string, number>)
  ).map(([name, value]) => ({ name, value }));

  const COLORS = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444', '#6366f1'];

  const recentTickets = ticketsData
    .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime())
    .slice(0, 5);

  const criticalTickets = ticketsData.filter(t => t.priority === 'Critique' || t.priority === 'Haute');

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Tableau de bord</h1>
          <p className="text-gray-600 mt-1">Vue d'ensemble de la gestion des incidents</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-[#2f4c99] text-white rounded-lg hover:bg-[#253a75] transition-colors">
          <Filter className="w-4 h-4" />
          Filtres
        </button>
      </div>

      <DashboardStats stats={stats} />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold mb-4">Tickets par catégorie</h3>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={categoryData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} style={{ fontSize: '12px' }} />
              <YAxis />
              <Tooltip />
              <Bar dataKey="value" fill="#3b82f6" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold mb-4">Répartition par statut</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={statusData}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {statusData.map((entry, index) => (
                  <Cell key={`status-cell-${entry.name}-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold">Tickets récents</h3>
              <span className="text-sm text-gray-500">{recentTickets.length} tickets</span>
            </div>
            <div className="space-y-3">
              {recentTickets.map(ticket => (
                <TicketCard
                  key={ticket.id}
                  ticket={ticket}
                  onClick={() => setSelectedTicket(ticket)}
                />
              ))}
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div className="flex items-center gap-2 mb-4">
              <TrendingUp className="w-5 h-5 text-orange-500" />
              <h3 className="text-lg font-semibold">Tickets prioritaires</h3>
            </div>
            <div className="space-y-3">
              {criticalTickets.slice(0, 3).map(ticket => (
                <TicketCard
                  key={ticket.id}
                  ticket={ticket}
                  onClick={() => setSelectedTicket(ticket)}
                />
              ))}
            </div>
          </div>
        </div>

        <div>
          <HotSpotsCard hotSpots={hotSpots} />
        </div>
      </div>

      <TicketDetailModal
        ticket={selectedTicket}
        onClose={() => setSelectedTicket(null)}
        onStatusChange={handleStatusChange}
      />
    </div>
  );
}