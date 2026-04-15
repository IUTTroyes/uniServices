export default [
  {
    path: 'semestre',
    component: () => import('@/views/previsionnel/PrevisionnelSemestreView.vue'),
  },
  {
    path: 'semestre_test',
    component: () => import('@/views/previsionnel/PrevisionnelSemestreTestView.vue'),
  },
  {
    path: 'personnel',
    component: () => import('@/views/previsionnel/PrevisionnelPersonnelView.vue'),
  },
  {
    path: 'matiere',
    component: () => import('@/views/previsionnel/PrevisionnelEnseignementView.vue'),
  },
  {
    path: 'primes',
    component: () => import('@/views/previsionnel/PrevisionnelPrimesView.vue'),
  },
  {
    path: 'actions',
    component: () => import('@/views/previsionnel/PrevisionnelActionsView.vue'),
  }
]
