export type TicketStatus = 
  | 'En attente de validation'
  | 'Validé'
  | 'En cours'
  | 'Classé sans suite'
  | 'Transmis au juridique'
  | 'Résolu';

export type TicketCategory = 
  | 'Accident'
  | 'Retard'
  | 'Bagarre/Agression'
  | 'Objet perdu'
  | 'Comportement suspect'
  | 'Arrêt sauté'
  | 'Plainte client'
  | 'Autre';

export type TicketPriority = 'Faible' | 'Moyenne' | 'Haute' | 'Critique';

export type TicketSource = 
  | 'Caméra bus'
  | 'Appel centrale'
  | 'QR Code'
  | 'Réseaux sociaux'
  | 'Agent infiltré'
  | 'Email'
  | 'Téléphone';

export type UserRole = 'RH' | 'Direction' | 'Stagiaire' | 'Admin';

export interface Ticket {
  id: string;
  title: string;
  description: string;
  status: TicketStatus;
  category: TicketCategory;
  priority: TicketPriority;
  source: TicketSource;
  projectId: string;
  assignedTo?: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
  confidenceIndex: number; // 0-100
  busLine?: string;
  busNumber?: string;
  location?: string;
  attachments?: string[];
  aiSummary?: string;
  reporterName?: string;
  reporterContact?: string;
}

export interface Project {
  id: string;
  name: string;
  description: string;
  station: string;
  busLines: string[];
  createdAt: Date;
}

export interface User {
  id: string;
  name: string;
  role: UserRole;
  email: string;
  avatar?: string;
}

export interface DashboardStats {
  totalTickets: number;
  pendingValidation: number;
  inProgress: number;
  resolved: number;
  criticalTickets: number;
  averageConfidenceIndex: number;
}

export interface HotSpot {
  location: string;
  incidents: number;
  timeSlot: string;
  severity: 'Faible' | 'Moyenne' | 'Haute';
}
