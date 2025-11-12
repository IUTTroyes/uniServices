export default [
  {
    path: ':semestreId/groupes/structure',
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
    path: ':semestreId/groupes/affectation',
    name: 'affectation-groupe',
    component: () => import('@/views/Groupes/AffectationGroupeView.vue'),
    meta: {
      breadcrumb: [{ label: 'Dashboard', route: '/' }, {
        label: 'Administration',
        route: '/administration',
        icon: 'pi pi-wrench'
      },
        { label: 'Semestre', route: null },
        { label: 'Affectation des groupes', route: null }]
    },
  },
  {
    path: ':semestreId/absences/liste',
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
    path: ':semestreId/justificatifs-absences/liste',
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
    path: ':semestreId/evaluations/liste',
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
    path: ':semestreId/rattrapages/liste',
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
    path: ':semestreId/mccc/liste',
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
    path: ':semestreId/sous-commission',
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
