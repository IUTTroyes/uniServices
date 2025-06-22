import {LayoutComponent} from "@components";
import previsionnelRoutes from './previsionnelRoutes.js'
import stageAdministrationRoutes from './stageAdministrationRoutes.js'
import semestreAdministrationRoutes from './semestreAdministrationRoutes.js'

export default [
  {
    path: 'administration',
    component: () => import('@/views/AdministrationView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: null,
        icon: 'pi pi-wrench'
      }]
    },
  },
  {
    path: 'administration/personnels',
    component: () => import('@/views/personnels/PersonnelsView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'Gestion des Personnels', route: null }]
    },
  },
  {
    path: 'administration/etudiants',
    component: () => import('@/views/etudiants/EtudiantsView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'Gestion des Etudiants', route: null }]
    },
  },
  {
    path: 'administration/pn',
    component: () => import('@/views/pn/PnView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'PPN', route: null }]
    },
  },
  {
    path: 'administration/previsionnel',
    component: () => import('@/views/previsionnel/PrevisionnelView.vue'),
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/administration', icon: 'pi pi-wrench' },
        { label: 'Prévisionnel', route: null, icon: 'pi pi-clock' }]
    },
    children: [
      ...previsionnelRoutes
    ]
  },
  {
    path: 'administration/stages',
    // component: () => import('@/views/Stages/AdministrationView.vue'),
    // meta: {
    //   breadcrumb: [
    //     { label: 'Dashboard', route: '/' },
    //     { label: 'Administration', route: '/administration', icon: 'pi pi-wrench' },
    //     { label: 'Stages', route: null, icon: 'pi pi-clock' }]
    // },
    children: [
      ...stageAdministrationRoutes
    ]
  },
  {
    path: 'administration/semestre/',
    children: [
      ...semestreAdministrationRoutes
    ]
  },

];
