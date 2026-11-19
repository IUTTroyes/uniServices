export default [
  {
    path: '',
    redirect: { name: 'previsionnel-semestre' },
  },
  {
    path: 'semestre',
    name: 'previsionnel-semestre',
    component: () => import("@/views/Previsionnel/PrevisionnelSemestreView.vue"),
  },
  {
    path: 'semestre_test',
    name: 'previsionnel-synthese',
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
    component: () => import("@/views/Previsionnel/PrevisionnelPersonnelView.vue"),
    props: { gestionHrs: true },
  },
  {
    path: 'actions',
    component: () => import('@/views/Previsionnel/PrevisionnelActionsView.vue'),
  },
];
