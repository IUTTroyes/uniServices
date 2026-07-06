export default [
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
