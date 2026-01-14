// Removed top-level store call; breadcrumb that needs the store is created lazily
import { useSemestreStore } from "@stores";

export default [
  {
    path: 'groupes/structure',
    name: 'structure-groupe',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
      { label: 'Semestre', route: null },
      { label: 'Structure des groupes', route: null }]
    },
  },
  {
    path: 'groupes/affectation',
    name: 'affectation-groupe',
    component: () => import('@/views/Groupes/AffectationGroupeView.vue'),
    // breadcrumb is a function so we can read the store at render time (not at module import)
    meta: {
      breadcrumb: () => {
        const semestreStore = useSemestreStore();
        const selectedSemestre = semestreStore.semestre;
        return [{ label: 'Dashboard', route: '/' }, {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
        { label: selectedSemestre?.libelle ?? 'Semestre', route: null },
        { label: 'Affectation des groupes', route: null }];
      }
    },
  },
  {
    path: 'absences/liste',
    name: 'liste-absences',
    component: () => import('@/views/Groupes/StructureGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Semestre', route: null },
        { label: 'Liste des absences du semestre', route: null }]
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
        { label: 'Semestre', route: null },
        { label: 'Liste des justificatifs d\'absences du semestre', route: null }]
    },
  },
  {
    path: 'evaluations/liste',
    name: 'liste-evaluations',
    component: () => import('@/views/Evaluations/EvaluationsView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Semestre', route: null },
        { label: 'Evaluations du semestre', route: null }]
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
        { label: 'Semestre', route: null },
        { label: 'Rattrapages du semestre', route: null }]
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
        { label: 'Semestre', route: null },
        { label: 'MCCC du semestre', route: null }]
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
        { label: 'Semestre', route: null },
        { label: 'Sous commission du semestre', route: null }]
    },
  },
]
