import previsionnelRoutes from './previsionnelRoutes.js'
import stageAdministrationRoutes from './stageAdministrationRoutes.js'
import semestreAdministrationRoutes from './anneeAdministrationRoutes.js'
import questionnaireAdministrationRoutes from './questionnaireAdministrationRoutes.js'
import PersonnelsView from "@/views/Personnels/PersonnelsView.vue";
import EtudiantsListeView from "@/views/Administration/Etudiants/EtudiantsListeView.vue";
import EtudiantsAjoutView from "@/views/Administration/Etudiants/EtudiantsAjoutView.vue";
import AdministrationView from '@/views/Administration/AdministrationView.vue';
import EtudiantAddApogee from '@/components/Administration/etudiant/EtudiantAddApogee.vue';
import EtudiantAddManuel from '@/components/Administration/etudiant/EtudiantAddManuel.vue';
import EtudiantsAjoutResultView from '@/views/Administration/Etudiants/EtudiantsAjoutResultView.vue';
import PnView from '@/views/Pn/PnView.vue';
import RefCompetencesIndexView from '@/views/Ref-Competences/IndexView.vue';
import PrevisionnelView from '@/views/Previsionnel/PrevisionnelView.vue';

export default [
  {
    path: 'administration',
    component: AdministrationView,
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
    component: PersonnelsView,
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'Gestion des Personnels', route: null }]
    },
  },
  {
    path: 'administration/etudiant',
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }]
    },
    children: [
      {
        path: '',
        component: EtudiantsListeView,
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/administration'
          }, { label: 'Liste des Etudiants', route: null }]
        },
      },
      {
        path: 'ajout',
        component: EtudiantsAjoutView,
        meta: {
          breadcrumb: [{ label: 'Dashboard', route: '/' }, {
            label: 'Administration',
            route: '/administration'
          }, { label: 'Ajouter des Etudiants', route: null }]
        },
        children: [
          {
            path: 'apogee',
            component: EtudiantAddApogee,
          },{
            path: 'manuel',
            component: EtudiantAddManuel,
          },
        ]
      },
      {
        path: 'ajout/result',
        component: EtudiantsAjoutResultView,
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
    component: PnView,
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'PPN', route: null }]
    },
  },
  {
    path: 'administration/referentiels-competences',
    component: RefCompetencesIndexView,
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration'
      }, { label: 'Référentiels de compétences', route: null }]
    },
  },
  {
    path: 'administration/previsionnel',
    component: PrevisionnelView,
    meta: {
      permission: 'canViewAdministration',
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
    meta: {
        permission: 'canViewAdministration',
    },
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
    path: 'administration/annee/:anneeId',
    meta: {
      permission: 'canViewAdministration',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Administration', route: '/administration', icon: 'pi pi-wrench' },
        { label: 'Année', route: null }
      ]
    },
    children: [
      ...semestreAdministrationRoutes
    ]
  },
  {
    path: 'administration/qualite',
    meta: {
        permission: 'canViewAdministration',
    },
    children: [
      ...questionnaireAdministrationRoutes
    ]
  },

];
