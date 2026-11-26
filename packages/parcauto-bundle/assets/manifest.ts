import AppLayout from '@components/components/layout/AppLayout.vue';


const pages = [
  { component: () => import('./views/PlanningView.vue'), path: 'planning', name: 'parcauto-planning', label: 'Réservations', icon: 'pi pi-calendar' },
  { component: () => import('./views/VehiculesView.vue'), path: 'vehicules', name: 'parcauto-vehicules', label: 'Véhicules et équipements', icon: 'pi pi-car' },
  { component: () => import('./views/PilotageView.vue'), path: 'pilotage', name: 'parcauto-pilotage', label: 'Pilotage', icon: 'pi pi-chart-bar', permission: 'canManageParcauto' },
  { component: () => import('./views/ReferentielsView.vue'), path: 'referentiels', name: 'parcauto-referentiels', label: 'Référentiels', icon: 'pi pi-list', permission: 'canManageParcauto' },
];

export default {
  name: 'parcauto',
  menu: {
    label: 'Parc automobile',
    icon: 'pi pi-car',
    items: [
      { label: 'Tableau de bord', icon: 'pi pi-home', to: '/parcauto' },
      ...pages.map(({ label, icon, path, permission }) => ({ label, icon, to: '/parcauto/' + path, permission })),
    ],
  },
  routes: [
    {
      path: '/parcauto',
      component: AppLayout,
      props: { appName: 'UniFlotte' },
      meta: { title: 'Parc automobile', package: 'parcauto', permission: 'isPersonnel' },
      children: [
        { path: '', name: 'parcauto-home', component: () => import('./views/DashboardView.vue') },
        ...pages.map(({ path, name, label, component, permission }) => ({
          path,
          name,
          component,
          meta: { title: label, ...(permission ? { permission } : {}) },
        })),
      ],
    },
  ],
};
