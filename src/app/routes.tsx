import { createBrowserRouter } from 'react-router';
import { RootLayout } from './layouts/RootLayout';
import { Dashboard } from './pages/Dashboard';
import { KanbanView } from './pages/KanbanView';
import { Projects } from './pages/Projects';
import { Users } from './pages/Users';
import { Settings } from './pages/Settings';

export const router = createBrowserRouter([
  {
    path: '/',
    Component: RootLayout,
    children: [
      { index: true, Component: Dashboard },
      { path: 'kanban', Component: KanbanView },
      { path: 'projects', Component: Projects },
      { path: 'users', Component: Users },
      { path: 'settings', Component: Settings },
    ],
  },
]);
