import { LayoutComponent } from '@components'

export default [
  {
    path: 'semestre',
    component: () => import('@/views/previsionnel/PrevisionnelSemestreView.vue'),
  },
  {
    path: 'personnel',
    component: () => import('@/views/previsionnel/PrevisionnelPersonnelView.vue'),
  },
  {
    path: 'matiere',
    component: () => import('@/views/previsionnel/PrevisionnelMatiereView.vue'),
  },
  {
    path: 'semestre/edit',
    component: () => import('@/views/previsionnel/PrevisionnelForm/PrevisionnelSemestreFormView.vue'),
  }
]
