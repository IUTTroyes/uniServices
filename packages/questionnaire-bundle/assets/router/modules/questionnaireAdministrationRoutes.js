import SurveyBuilderView from '@/views/Questionnaire/SurveyBuilderView.vue'
import SurveyListView from '@/views/Questionnaire/SurveyListView.vue'
import SurveyResponseView from '@/views/Questionnaire/SurveyResponseView.vue'
import AnalyticsView from '@/views/Questionnaire/AnalyticsView.vue'
import SurveyTakeView from '@/views/Questionnaire/SurveyTakeView.vue'

export default [
  {
    path: '/qualite/enquetes',
    name: 'questionnaire_qualite-enquetes',
    component: () => import('@/views/DashboardView.vue'),
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: null }]
    },
  },
  {
    path: '/qualite/enquetes/liste',
    name: 'questionnaire_enquetes-liste',
    component: SurveyListView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/qualite/enquetes' },
        { label: 'Liste des questionnaires', route: null }
      ]
    }
  },
  {
    path: '/qualite/enquetes/builder/:id?',
    name: 'questionnaire_builder',
    component: SurveyBuilderView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/qualite/enquetes' },
        { label: 'Créer/modifier un questionnaire', route: null }
      ]
    }
  },
  {
    path: '/qualite/enquetes/responses/:id',
    name: 'questionnaire_responses',
    component: SurveyResponseView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/qualite/enquetes' },
        { label: 'Questionnaire xxx', route: null },
        { label: 'Réponses', route: null },
      ]
    }
  },
  {
    path: '/qualite/enquetes/analytics/:id',
    name: 'questionnaire_analytics',
    component: AnalyticsView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/qualite/enquetes' },
        { label: 'Questionnaire xxx', route: null },
        { label: 'Statistiques', route: null },
      ]
    }
  },
  {
    path: '/qualite/enquetes/take/:token',
    name: 'questionnaire_take-survey',
    component: SurveyTakeView
  }
]
