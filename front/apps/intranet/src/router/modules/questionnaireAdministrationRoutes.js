import SurveyBuilderView from '@/views/Questionnaire/SurveyBuilderView.vue'
import SurveyResponseView from '@/views/Questionnaire/SurveyResponseView.vue'
import AnalyticsView from '@/views/Questionnaire/AnalyticsView.vue'
import SurveyTakeView from '@/views/Questionnaire/SurveyTakeView.vue'

export default [
  {
    path: '/administration/qualite/enquetes',
    name: 'qualite-enquetes',
    component: () => import('@/views/Questionnaire/IndexView.vue'),
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: null }]
    },
  },
  {
    path: '/administration/qualite/enquetes/builder/:id?',
    name: 'builder',
    component: SurveyBuilderView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        {
          label: 'Administration',
          route: '/administration',
          icon: 'pi pi-wrench'
        },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/administration/qualite/enquetes' },
        { label: 'Créer/modifier un questionnaire', route: null }
      ]
    }
  },
  {
    path: '/administration/qualite/enquetes/responses/:id',
    name: 'responses',
    component: SurveyResponseView
  },
  {
    path: '/administration/qualite/enquetes/analytics/:id',
    name: 'analytics',
    component: AnalyticsView
  },
  {
    path: '/administration/qualite/enquetes/take/:token',
    name: 'take-survey',
    component: SurveyTakeView
  }
]
