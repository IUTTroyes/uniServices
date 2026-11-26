import previsionnelRoutes from './administration/previsionnelRoutes.js';
import anneeRoutes from './administration/anneeRoutes.js';

export default [
  {
    path: 'administration',
    component: () => import('@/views/Administration/AdministrationView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: null,
        icon: 'pi pi-wrench'
      }]
    },
  },
  {
    path: 'administration/plans-cours',
    component: () => import('@/views/PlanCours/Administration/PlanCoursAdminView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/intranet/administration' },
        { label: 'Plans de cours', route: null, icon: 'pi pi-file' }
      ]
    },
  },
  {
    path: 'administration/previsionnel',
    component: () => import('@/views/Previsionnel/PrevisionnelView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/intranet/administration', icon: 'pi pi-wrench' },
        { label: 'Prévisionnel', route: null, icon: 'pi pi-clock' }]
    },
    children: [
      ...previsionnelRoutes
    ]
  },
  {
    path: 'administration/stages',
    meta: {
      permission: 'canViewAdministration',
    },
    children: [
      {
        path: 'gestion-periodes',
        component: () => import('@/views/Stages/GestionPeriodeView.vue'),
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/intranet/administration',
            icon: 'pi pi-wrench'
          },
          { label: 'Stages', route: null },
          { label: 'Gestion des périodes', route: null }]
        },
      },
    ]
  },
  {
    path: 'administration/annee/:anneeId',
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/intranet/administration', icon: 'pi pi-wrench' },
        { label: 'Année', route: null }
      ]
    },
    children: [
      ...anneeRoutes
    ]
  },
  {
    path: 'administration/qualite',
    meta: {
      permission: 'canViewAdministration',
    },
  }
];
