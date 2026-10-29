export default [
  {
    path: '',
    redirect: 'semestre',
  },
  {
    path: 'semestre',
    component: () => import("@/views/Previsionnel/PrevisionnelSemestreView.vue"),
  },
  {
    path: 'semestre_test',
    component: () => import("@/views/Previsionnel/PrevisionnelSemestreTestView.vue"),
  },
  {
    path: 'personnel',
    component: () => import("@/views/Previsionnel/PrevisionnelPersonnelView.vue"),
  },
  {
    path: 'matiere',
    component: () => import("@/views/Previsionnel/PrevisionnelEnseignementView.vue"),
  },
  {
    path: 'primes',
    component: () => import("@/views/Previsionnel/PrevisionnelPrimesView.vue"),
  },
  {
    path: 'actions',
    component: () => import("@/views/Previsionnel/PrevisionnelActionsView.vue"),
  },
];
