import PedagogieView from '@/views/PedagogieView.vue'
import PlanCoursEditView from '@/views/PlanCours/Personnel/PlanCoursEditView.vue'
import { hasPermission } from '@utils/permissions';

export default [
  {
    path: 'pedagogie',
    component: PedagogieView,
    beforeEnter: (to) => {
      if (to.query.tab === 'enseignements' && !hasPermission('isEnseignant')) {
        return {
          path: '/intranet/pedagogie',
          query: {
            ...to.query,
            tab: 'plans',
          },
        };
      }

      return true;
    },
    meta: {
      permission: 'isPersonnel',
      breadcrumb: [
        { label: 'Dashboard', route: '/intranet' },
        { label: 'Pédagogie', route: null, icon: 'pi pi-book' }
      ]
    }
  },
  {
    path: 'plan-de-cours',
    redirect: to => ({ path: '/intranet/pedagogie', query: to.query }),
  },
  {
    path: 'pedagogie/plans-de-cours/edit/:id',
    alias: 'plan-de-cours/edit/:id',
    component: PlanCoursEditView,
    meta: {
      permission: 'isPersonnel',
      breadcrumb: [
        { label: 'Dashboard', route: '/intranet' },
        { label: 'Pédagogie', route: '/intranet/pedagogie', icon: 'pi pi-book' },
        { label: 'Plans de cours', route: '/intranet/pedagogie?onglet=plans' },
        { label: 'Édition', route: null }
      ]
    }
  }
]
