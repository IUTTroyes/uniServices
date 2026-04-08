// Removed top-level store call; breadcrumb that needs the store is created lazily
import { useAnneeStore } from "@stores";

export default [
  {
    path: 'groupes/structure',
    name: 'structure-groupe',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: () => {
        const anneeStore = useAnneeStore();
        const selectedAnnee = anneeStore.annee;
        return [{ label: 'Dashboard', route: '/' }, {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
          { label: selectedAnnee?.libelle ?? 'Année', route: null },
          { label: 'Structure des groupes', route: null }];
      }
    },
  },
  {
    path: 'groupes/affectation',
    name: 'affectation-groupe',
    component: () => import('@/views/Groupes/AffectationGroupeView.vue'),
    // breadcrumb is a function so we can read the store at render time (not at module import)
    meta: {
      breadcrumb: () => {
        const anneeStore = useAnneeStore();
        const selectedAnnee = anneeStore.annee;
        return [{ label: 'Dashboard', route: '/' }, {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
        { label: selectedAnnee?.libelle ?? 'Année', route: null },
        { label: 'Affectation des groupes', route: null }];
      }
    },
  },
  {
    path: 'absences/liste',
    name: 'liste-absences',
    component: () => import('@/views/Absence/AbsenceListeView.vue'),
    meta: {
      permission: 'isPersonnel',
      breadcrumb: () => {
        const anneeStore = useAnneeStore();
        const selectedAnnee = anneeStore.annee;
        return [{ label: 'Dashboard', route: '/' }, {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
          { label: selectedAnnee?.libelle ?? 'Année', route: null },
          { label: 'Liste des absences', route: null }];
      }
    },
  },
  {
    path: 'absence/new',
    name: 'new-absence',
    component: () => import('@/views/Absence/AbsenceNewView.vue'),
    meta: {
      permission: 'isPersonnel',
      breadcrumb: () => {
        const anneeStore = useAnneeStore();
        const selectedAnnee = anneeStore.annee;
        return [{ label: 'Dashboard', route: '/' }, {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
          { label: selectedAnnee?.libelle ?? 'Année', route: null },
          { label: 'Saisir des absences', route: null }];
      }
    },
  },
  {
    path: 'justificatifs-absences/liste',
    name: 'liste-justificatifs-absences',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Année', route: null },
        { label: 'Liste des justificatifs d\'absences', route: null }]
    },
  },
  {
    path: 'evaluations/liste',
    name: 'liste-evaluations',
    component: () => import('@/views/Evaluations/EvaluationsView.vue'),
    meta: {
      breadcrumb: () => {
        const anneeStore = useAnneeStore();
        const selectedAnnee = anneeStore.annee;
        return [{ label: 'Dashboard', route: '/' }, {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
          { label: selectedAnnee?.libelle ?? 'Année', route: null },
          { label: 'Évaluations', route: null }];
      }
    },
  },
  {
    path: 'rattrapages/liste',
    name: 'liste-rattrapages',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Année', route: null },
        { label: 'Rattrapages', route: null }]
    },
  },
  {
    path: 'mccc/liste',
    name: 'liste-mccc',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Année', route: null },
        { label: 'MCCC', route: null }]
    },
  },
  {
    path: 'sous-commission',
    name: 'sous-commission',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Année', route: null },
        { label: 'Sous commission', route: null }]
    },
  },
]
