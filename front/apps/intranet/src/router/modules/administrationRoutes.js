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
    path: 'administration/etudiant',
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }]
    },
    children: [
      {
        path: '',
        component: () => import('@/views/etudiants/EtudiantsListeView.vue'),
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/administration'
          }, { label: 'Liste des Etudiants', route: null }]
        },
      },
      {
        path: 'ajout',
        component: () => import('@/views/etudiants/EtudiantsAjoutView.vue'),
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/administration'
          }, { label: 'Ajouter des Etudiants', route: null }]
        },
        children: [
          {
            path: 'apogee',
            component: () => import('@/components/Administration/etudiant/EtudiantAddApogee.vue'),
          },{
            path: 'manuel',
            component: () => import('@/components/Administration/etudiant/EtudiantAddManuel.vue'),
          },
        ]
      },
      {
        path: 'ajout/result',
        component: () => import('@/views/Etudiants/EtudiantsAjoutResult.vue'),
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/administration'
          }, { label: 'Résultat de l\'import', route: null }]
        },
        props: true
      }
    ]
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
    path: 'administration/referentiels-competences',
    component: () => import('@/views/Ref-Competences/IndexView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'Référentiels de compétences', route: null }]
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
