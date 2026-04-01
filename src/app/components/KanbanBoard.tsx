import { useState } from 'react';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { Ticket, TicketStatus } from '../types';
import { TicketCard } from './TicketCard';
import { AlertCircle, Clock, CheckCircle, XCircle, Scale, CheckCheck } from 'lucide-react';

interface KanbanBoardProps {
  tickets: Ticket[];
  onTicketClick: (ticket: Ticket) => void;
  onStatusChange: (ticketId: string, newStatus: TicketStatus) => void;
}

interface DraggableTicketProps {
  ticket: Ticket;
  onTicketClick: (ticket: Ticket) => void;
}

const ItemType = {
  TICKET: 'ticket',
};

function DraggableTicket({ ticket, onTicketClick }: DraggableTicketProps) {
  const [{ isDragging }, drag] = useDrag(() => ({
    type: ItemType.TICKET,
    item: { ticket },
    collect: (monitor) => ({
      isDragging: !!monitor.isDragging(),
    }),
  }));

  return (
    <div
      ref={drag}
      style={{ opacity: isDragging ? 0.5 : 1 }}
      className="mb-3"
    >
      <TicketCard ticket={ticket} onClick={() => onTicketClick(ticket)} />
    </div>
  );
}

interface KanbanColumnProps {
  status: TicketStatus;
  tickets: Ticket[];
  onTicketClick: (ticket: Ticket) => void;
  onDrop: (ticketId: string, newStatus: TicketStatus) => void;
  icon: React.ElementType;
  color: string;
}

function KanbanColumn({ status, tickets, onTicketClick, onDrop, icon: Icon, color }: KanbanColumnProps) {
  const [{ isOver }, drop] = useDrop(() => ({
    accept: ItemType.TICKET,
    drop: (item: { ticket: Ticket }) => {
      if (item.ticket.status !== status) {
        onDrop(item.ticket.id, status);
      }
    },
    collect: (monitor) => ({
      isOver: !!monitor.isOver(),
    }),
  }));

  return (
    <div className="flex-1 min-w-[300px]">
      <div className={`bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col`}>
        <div className={`${color} p-4 rounded-t-lg flex items-center justify-between`}>
          <div className="flex items-center gap-2 text-white">
            <Icon className="w-5 h-5" />
            <h3 className="font-semibold">{status}</h3>
          </div>
          <span className="bg-white bg-opacity-30 text-white px-2.5 py-1 rounded-full text-sm font-medium">
            {tickets.length}
          </span>
        </div>
        <div
          ref={drop}
          className={`flex-1 p-4 overflow-y-auto ${isOver ? 'bg-blue-50' : 'bg-gray-50'} transition-colors`}
          style={{ minHeight: '500px' }}
        >
          {tickets.map((ticket) => (
            <DraggableTicket key={ticket.id} ticket={ticket} onTicketClick={onTicketClick} />
          ))}
          {tickets.length === 0 && (
            <div className="text-center text-gray-400 mt-8">
              Aucun ticket
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export function KanbanBoard({ tickets, onTicketClick, onStatusChange }: KanbanBoardProps) {
  const columns: { status: TicketStatus; icon: React.ElementType; color: string }[] = [
    { status: 'En attente de validation', icon: Clock, color: 'bg-yellow-500' },
    { status: 'Validé', icon: CheckCircle, color: 'bg-green-500' },
    { status: 'En cours', icon: AlertCircle, color: 'bg-blue-500' },
    { status: 'Classé sans suite', icon: XCircle, color: 'bg-gray-500' },
    { status: 'Transmis au juridique', icon: Scale, color: 'bg-purple-500' },
    { status: 'Résolu', icon: CheckCheck, color: 'bg-emerald-500' },
  ];

  const getTicketsByStatus = (status: TicketStatus) => {
    return tickets.filter((ticket) => ticket.status === status);
  };

  return (
    <DndProvider backend={HTML5Backend}>
      <div className="flex gap-4 overflow-x-auto pb-4">
        {columns.map((column) => (
          <KanbanColumn
            key={column.status}
            status={column.status}
            tickets={getTicketsByStatus(column.status)}
            onTicketClick={onTicketClick}
            onDrop={onStatusChange}
            icon={column.icon}
            color={column.color}
          />
        ))}
      </div>
    </DndProvider>
  );
}
