import SurveyBuilderView from '@/views/Questionnaire/SurveyBuilderView.vue'
import SurveyListView from '@/views/Questionnaire/SurveyListView.vue'
import SurveyResponseView from '@/views/Questionnaire/SurveyResponseView.vue'
import AnalyticsView from '@/views/Questionnaire/AnalyticsView.vue'
import SurveyTakeView from '@/views/Questionnaire/SurveyTakeView.vue'

export default [
  {
    path: 'qualite/enquetes',
    name: 'questionnaire_qualite-enquetes',
    component: () => import('@/views/DashboardView.vue'),
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: null }
      ]
    },
  },
  {
    path: 'qualite/enquetes/liste',
    name: 'questionnaire_enquetes-liste',
    component: SurveyListView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/questionnaire/qualite/enquetes' },
        { label: 'Liste des questionnaires', route: null }
      ]
    }
  },
  {
    path: 'qualite/enquetes/builder/:id?',
    name: 'questionnaire_builder',
    component: SurveyBuilderView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/questionnaire/qualite/enquetes' },
        { label: 'Liste des questionnaires', route: '/questionnaire/qualite/enquetes/liste' },
        { label: 'Créer/modifier un questionnaire', route: null }
      ]
    }
  },
  {
    path: 'qualite/enquetes/responses/:id',
    name: 'questionnaire_responses',
    component: SurveyResponseView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/questionnaire/qualite/enquetes' },
        { label: 'Liste des questionnaires', route: '/questionnaire/qualite/enquetes/liste' },
        { label: 'Réponses', route: null }
      ]
    }
  },
  {
    path: 'qualite/enquetes/analytics/:id',
    name: 'questionnaire_analytics',
    component: AnalyticsView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Qualité', route: null },
        { label: 'Enquêtes', route: '/questionnaire/qualite/enquetes' },
        { label: 'Liste des questionnaires', route: '/questionnaire/qualite/enquetes/liste' },
        { label: 'Statistiques', route: null }
      ]
    }
  },
  {
    path: 'qualite/enquetes/modeles',
    name: 'questionnaire_modeles',
    component: () => import('@/views/Questionnaire/ModelesView.vue'),
    meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Modèles', route: null }] }
  },
  {
    path: 'qualite/enquetes/comparaison/:id',
    name: 'questionnaire_comparaison',
    component: () => import('@/views/Questionnaire/ComparaisonView.vue'),
    meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Modèles', route: '/questionnaire/qualite/enquetes/modeles' }, { label: 'Comparaison', route: null }] }
  },
  {
    path: 'qualite/enquetes/diffusion/:id',
    name: 'questionnaire_diffusion',
    component: () => import('@/views/Questionnaire/DiffusionView.vue'),
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Liste des questionnaires', route: '/questionnaire/qualite/enquetes/liste' },
        { label: 'Diffusion des résultats', route: null }
      ]
    }
  },
  {
    // Restitution web des résultats (destinataires : leurs sections ; équipe : toutes)
    path: 'qualite/enquetes/resultats/:id',
    name: 'questionnaire_mes_resultats',
    component: () => import('@/views/Questionnaire/MesResultatsView.vue'),
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Mes résultats', route: null }
      ]
    }
  },
  {
    path: 'qualite/enquetes/take/:token',
    name: 'questionnaire_take-survey',
    component: SurveyTakeView,
    meta: {
      breadcrumb: [
        { label: 'Dashboard', route: '/' },
        { label: 'Répondre au questionnaire', route: null }
      ]
    }
  },
  {
    path: 'configuration',
    name: 'questionnaire_configuration',
    component: () => import('@/views/Configuration/QuestionnaireConfigurationView.vue'),
    meta: {
      permission: ['ROLE_QUESTIONNAIRE_MANAGER', 'isQualite', 'SUPER_ADMIN'],
      breadcrumb: [
        { label: 'Dashboard', route: '/questionnaire' },
        { label: 'Configuration', route: null, icon: 'pi pi-sliders-h' }
      ]
    }
  }
]
