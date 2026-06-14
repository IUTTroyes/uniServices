import SurveyBuilderView from '@/views/Questionnaire/SurveyBuilderView.vue'
import SurveyListView from '@/views/Questionnaire/SurveyListView.vue'
import SurveyResponseView from '@/views/Questionnaire/SurveyResponseView.vue'
import AnalyticsView from '@/views/Questionnaire/AnalyticsView.vue'
import SurveyTakeView from '@/views/Questionnaire/SurveyTakeView.vue'

export default [
  {
    path: '/qualite',
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
    path: '/qualite/liste',
    name: 'enquetes-liste',
    component: SurveyListView,
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
        { label: 'Liste des questionnaires', route: null }
      ]
    }
  },
  {
    path: '/qualite/builder/:id?',
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
    path: '/qualite/responses/:id',
    name: 'responses',
    component: SurveyResponseView
  },
  {
    path: '/qualite/analytics/:id',
    name: 'analytics',
    component: AnalyticsView
  },
  {
    path: '/qualite/take/:token',
    name: 'take-survey',
    component: SurveyTakeView
  }
]
