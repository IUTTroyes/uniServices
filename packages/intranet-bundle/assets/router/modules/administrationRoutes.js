import previsionnelRoutes from './previsionnelRoutes.js';
import stageAdministrationRoutes from './stageAdministrationRoutes.js';
import anneeAdministrationRoutes from './anneeAdministrationRoutes.js';

export default [
  {
    path: 'configuration',
    name: 'intranet-configuration',
    component: () => import('@/views/Administration/Configuration/IntranetConfigurationView.vue'),
    meta: {
      permission: 'isChefDepartement',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/intranet/administration' },
        { label: 'Configuration', route: null, icon: 'pi pi-sliders-h' }
      ]
    }
  },
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
    path: 'administration/personnels',
    component: () => import('@/views/Personnels/PersonnelsListeView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/intranet/administration'
      }, { label: 'Gestion des Personnels', route: null }]
    },
  },
  {
    path: 'administration/etudiants',
    component: () => import('@/views/Etudiants/EtudiantsListeView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/intranet/administration', icon: 'pi pi-wrench' },
        { label: 'Étudiants & Applications', route: null }
      ]
    },
  },
  {
    path: 'administration/etudiant',
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/intranet/administration'
      }]
    },
    children: [
      {
        path: 'ajout',
        component: () => import('@/views/Etudiants/EtudiantsAjoutView.vue'),
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/intranet/administration'
          }, { label: 'Ajouter des Etudiants', route: null }]
        },
        children: [
          {
            path: 'apogee',
            component: () => import('@/components/Etudiant/EtudiantAddApogee.vue'),
          },
          {
            path: 'manuel',
            component: () => import('@/components/Etudiant/EtudiantAddManuel.vue'),
          },
          {
            path: 'sans-scolarite',
            name: 'ajout-etudiants-sans-scolarite',
            component: () => import('@/components/Etudiant/EtudiantsSansScolarite.vue'),
          },
        ]
      },
      {
        path: 'ajout/result',
        component: () => import('@/views/Etudiants/EtudiantsAjoutResultView.vue'),
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/intranet/administration'
          }, { label: 'Résultat de l\'import', route: null }]
        },
        props: true
      }
    ]
  },
  {
    path: 'administration/pn',
    component: () => import('@/views/Pn/PnView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/intranet/administration'
      }, { label: 'PPN', route: null }]
    },
  },
  {
    path: 'administration/referentiels-competences',
    component: () => import('@/views/Ref-Competences/IndexView.vue'),
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/intranet/administration'
      }, { label: 'Référentiels de compétences', route: null }]
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
      ...stageAdministrationRoutes
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
      ...anneeAdministrationRoutes
    ]
  },
  {
    path: 'administration/qualite',
    meta: {
      permission: 'canViewAdministration',
    },
  }
];
