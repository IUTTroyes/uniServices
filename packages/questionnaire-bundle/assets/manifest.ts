import './assets/forms.css';
import dashboardRoutes from './router/modules/dashboardRoutes.js';
import qualiteRoutes from './router/modules/questionnaireAdministrationRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { registerWidgets } from './widgets/registerWidgets';

const GESTIONNAIRES = ['ROLE_QUESTIONNAIRE_MANAGER', 'isQualite', 'SUPER_ADMIN'];

const questionnaireMenu = {
  label: 'Questionnaires',
  icon: 'pi pi-fw pi-check-square',
  items: [
    { label: 'Tableau de bord', icon: 'pi pi-fw pi-home', to: '/questionnaire/' },
    // Gestion : mêmes rôles que côté serveur (QuestionnaireAccess), dans le département actif.
    { label: 'Tous les questionnaires', icon: 'pi pi-fw pi-list', to: '/questionnaire/qualite/enquetes/liste', permission: GESTIONNAIRES },
    { label: 'Créer un questionnaire', icon: 'pi pi-fw pi-plus', to: '/questionnaire/qualite/enquetes/builder/new', permission: GESTIONNAIRES },
    { label: 'Modèles', icon: 'pi pi-fw pi-bookmark', to: '/questionnaire/qualite/enquetes/modeles', permission: GESTIONNAIRES },
  ],
  bottomItems: [
    {
      label: 'Configuration',
      icon: 'pi pi-fw pi-sliders-h',
      to: '/questionnaire/configuration',
      permission: GESTIONNAIRES
    }
  ]
};

export default { 
  name: 'questionnaire',
  primaryColor: 'green',
  registerWidgets,
  routes: [
    // Réponse par le lien personnel reçu par e-mail : public (les invitations peuvent viser des adresses sans compte).
    {
      path: '/questionnaire-reponse/:token',
      name: 'questionnaire_reponse_publique',
      component: () => import('./views/Questionnaire/SurveyTakeView.vue'),
      meta: { public: true, title: 'Répondre au questionnaire' }
    },
    {
      path: '/questionnaire',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Questionnaires',
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [])
      }),
      children: [
        ...dashboardRoutes,
        ...qualiteRoutes,
      ]
    }
  ],
  menu: questionnaireMenu
};
