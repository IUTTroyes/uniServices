export default [
  {
    path: '/previsionnel/',
    component: () => import('@/views/previsionnel/Previsionnel.vue'),
    name: 'Previsionnel',
    children: [
      {
        path: 'intervenant',
        component: () => import('@/views/previsionnel/Intervenant.vue'),
        name: 'PrevisionnelIntervenant',
      }]
  }
]
