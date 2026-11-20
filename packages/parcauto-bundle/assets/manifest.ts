import AppLayout from '@components/components/layout/AppLayout.vue';

import { gestionParcauto } from './permissions';

const pages = [
  { path: 'planning', name: 'parcauto-planning', label: 'Réservations', icon: 'pi pi-calendar' },
  { path: 'vehicules', name: 'parcauto-vehicules', label: 'Véhicules et équipements', icon: 'pi pi-car' },
  { path: 'pilotage', name: 'parcauto-pilotage', label: 'Pilotage', icon: 'pi pi-chart-bar', permission: gestionParcauto },
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
      props: { appName: 'Parc automobile' },
      meta: { title: 'Parc automobile', package: 'parcauto', permission: 'isPersonnel' },
      children: [
        { path: '', name: 'parcauto-home', component: () => import('./App.vue') },
        ...pages.map(({ path, name, label, icon, permission }) => ({
          path,
          name,
          component: () => import('./views/ParcautoPage.vue'),
          props: { page: path, titre: label, icon },
          meta: { title: label, ...(permission ? { permission } : {}) },
        })),
      ],
    },
  ],
};
