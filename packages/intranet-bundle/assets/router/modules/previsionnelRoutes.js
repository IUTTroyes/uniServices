import PrevisionnelSemestreView from "@/views/Previsionnel/PrevisionnelSemestreView.vue";
import PrevisionnelSemestreTestView from "@/views/Previsionnel/PrevisionnelSemestreTestView.vue";
import PrevisionnelPersonnelView from "@/views/Previsionnel/PrevisionnelPersonnelView.vue";
import PrevisionnelEnseignementView from "@/views/Previsionnel/PrevisionnelEnseignementView.vue";
import PrevisionnelPrimesView from "@/views/Previsionnel/PrevisionnelPrimesView.vue";
import PrevisionnelActionsView from "@/views/Previsionnel/PrevisionnelActionsView.vue";

export default [
  {
    path: 'semestre',
    component: PrevisionnelSemestreView,
  },
  {
    path: 'semestre_test',
    component: PrevisionnelSemestreTestView,
  },
  {
    path: 'personnel',
    component: PrevisionnelPersonnelView,
  },
  {
    path: 'matiere',
    component: PrevisionnelEnseignementView,
  },
  {
    path: 'primes',
    component: PrevisionnelPrimesView,
  },
  {
    path: 'actions',
    component: PrevisionnelActionsView,
  }
]
