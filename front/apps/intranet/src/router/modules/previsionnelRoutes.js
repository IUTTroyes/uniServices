import { LayoutComponent } from '@components'

export default [
  {
    path: 'semestre',
    component: () => import('@/views/previsionnel/SemestreView.vue'),
  },
  {
    path: 'personnel',
    component: () => import('@/views/previsionnel/PersonnelView.vue'),
  },
  {
    path: 'matiere',
    component: () => import('@/views/previsionnel/MatiereView.vue'),
  }
]
