import PlanCoursListView from '@/views/PlanCours/Personnel/PlanCoursListView.vue'
import PlanCoursEditView from '@/views/PlanCours/Personnel/PlanCoursEditView.vue'

export default [
  {
    path: 'plan-de-cours',
    component: PlanCoursListView,
    meta: {
      permission: 'isPersonnel',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Plans de cours', route: null, icon: 'pi pi-book' }
      ]
    }
  },
  {
    path: 'plan-de-cours/edit/:id',
    component: PlanCoursEditView,
    meta: {
      permission: 'isPersonnel',
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Plans de cours', route: '/intranet/plan-de-cours', icon: 'pi pi-book' },
        { label: 'Édition', route: null }
      ]
    }
  }
]
