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
]
