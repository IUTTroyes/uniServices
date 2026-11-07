/**
 * Sections fonctionnelles d'UniFolio. Source unique pour le menu, les routes
 * et l'accueil.
 *  - `permission` : contrôle front (shared/utils/permissions.js) pour le menu et le routeur ;
 *  - `capacite`   : capacité renvoyée par /api/unifolio/contexte (voter back, fait foi).
 *  - `lot`        : lot du plan de refonte qui livre la section (docs/PLAN_REFONTE.md).
 *  - `component`  : vue de la section ; absente = page « en construction ».
 *  - `sousRoutes` : pages filles (même permission), hors menu.
 */
export const unifolioSections = [
  {
    titre: 'Mon portfolio',
    items: [
      {
        path: 'traces',
        label: 'Mes traces',
        icon: 'pi pi-fw pi-images',
        description: 'Déposer vos preuves et les rattacher au référentiel.',
        permission: 'isEtudiant',
        capacite: 'etudiant',
        lot: 1,
        component: () => import('./views/TracesView.vue'),
        sousRoutes: [
          { path: 'traces/nouvelle', label: 'Nouvelle trace', icon: 'pi pi-plus', component: () => import('./views/TraceEditView.vue') },
          { path: 'traces/:uuid', label: 'Trace', icon: 'pi pi-eye', component: () => import('./views/TraceDetailView.vue') },
          { path: 'traces/:uuid/modifier', label: 'Modifier la trace', icon: 'pi pi-pencil', component: () => import('./views/TraceEditView.vue') },
        ],
      },
      {
        path: 'auto-evaluation',
        label: 'Auto-évaluation',
        icon: 'pi pi-fw pi-chart-line',
        description: 'Vous positionner sur les compétences de votre année.',
        permission: 'isEtudiant',
        capacite: 'etudiant',
        lot: 3,
        component: () => import('./views/AutoEvaluationView.vue'),
      },
      {
        path: 'progression',
        label: 'Ma progression',
        icon: 'pi pi-fw pi-chart-bar',
        description: 'Éléments du référentiel couverts, année par année.',
        permission: 'isEtudiant',
        capacite: 'etudiant',
        lot: 7,
        component: () => import('./views/ProgressionView.vue'),
      },
      {
        path: 'portfolios',
        label: 'Mes portfolios',
        icon: 'pi pi-fw pi-book',
        description: 'Portfolio académique de l\'année et portfolio vitrine.',
        permission: 'isEtudiant',
        capacite: 'etudiant',
        lot: 5,
        component: () => import('./views/PortfoliosView.vue'),
        sousRoutes: [
          { path: 'portfolios/:uuid', label: 'Portfolio', icon: 'pi pi-book', component: () => import('./views/PortfolioEditView.vue') },
        ],
      },
      {
        path: 'cv',
        label: 'Mon CV',
        icon: 'pi pi-fw pi-id-card',
        description: 'Expériences, formations et compétences.',
        permission: 'isEtudiant',
        capacite: 'etudiant',
        lot: 5,
        component: () => import('./views/CvView.vue'),
      },
    ],
  },
  {
    titre: 'Évaluation',
    items: [
      {
        path: 'evaluations',
        label: 'À évaluer',
        icon: 'pi pi-fw pi-check-square',
        description: 'Traces et portfolios des étudiants qui vous sont affectés.',
        permission: ['isEnseignant', 'isReferentPortfolio', 'ROLE_PORTFOLIO_CORRECTOR'],
        capacite: 'correcteur',
        lot: 4,
        component: () => import('./views/EvaluationsView.vue'),
        sousRoutes: [
          { path: 'evaluations/etudiants/:campagne/:etudiant', label: 'Suivi d\'un étudiant', icon: 'pi pi-user', component: () => import('./views/EvaluationEtudiantView.vue') },
          { path: 'evaluations/bilans/:campagne/:etudiant', label: 'Bilans d\'un étudiant', icon: 'pi pi-check-square', component: () => import('./views/EvaluationBilansView.vue') },
          { path: 'evaluations/traces/:uuid', label: 'Évaluation d\'une trace', icon: 'pi pi-images', component: () => import('./views/EvaluationTraceView.vue') },
        ],
      },
      {
        path: 'suivi',
        label: 'Suivi des étudiants',
        icon: 'pi pi-fw pi-users',
        description: 'Progression par compétence, promo par promo.',
        permission: ['isEnseignant', 'isReferentPortfolio', 'ROLE_PORTFOLIO_CORRECTOR'],
        capacite: 'correcteur',
        lot: 7,
        component: () => import('./views/SuiviView.vue'),
      },
    ],
  },
  {
    titre: 'Administration',
    items: [
      {
        path: 'admin/formations',
        label: 'Paramétrage des formations',
        icon: 'pi pi-fw pi-sliders-h',
        description: 'Granularité d\'évaluation et grille par défaut.',
        permission: 'isReferentPortfolio',
        capacite: 'referent',
        lot: 2,
        component: () => import('./views/admin/FormationsView.vue'),
      },
      {
        path: 'admin/grilles',
        label: 'Grilles d\'évaluation',
        icon: 'pi pi-fw pi-table',
        description: 'Critères, échelles et versions.',
        permission: 'isReferentPortfolio',
        capacite: 'referent',
        lot: 2,
        component: () => import('./views/admin/GrillesView.vue'),
        sousRoutes: [
          { path: 'admin/grilles/:uuid', label: 'Grille', icon: 'pi pi-table', component: () => import('./views/admin/GrilleEditView.vue') },
        ],
      },
      {
        path: 'admin/campagnes',
        label: 'Campagnes',
        icon: 'pi pi-fw pi-calendar',
        description: 'Calendrier de dépôt et d\'évaluation, affectations.',
        permission: 'isReferentPortfolio',
        capacite: 'referent',
        lot: 4,
        component: () => import('./views/admin/CampagnesView.vue'),
        sousRoutes: [
          { path: 'admin/campagnes/:uuid', label: 'Campagne', icon: 'pi pi-calendar', component: () => import('./views/admin/CampagneEditView.vue') },
        ],
      },
      {
        path: 'admin/modeles',
        label: 'Modèles de portfolio',
        icon: 'pi pi-fw pi-clone',
        description: 'Pages et blocs imposés du portfolio académique.',
        permission: 'isReferentPortfolio',
        capacite: 'referent',
        lot: 5,
        component: () => import('./views/admin/ModelesView.vue'),
        sousRoutes: [
          { path: 'admin/modeles/:uuid', label: 'Modèle', icon: 'pi pi-clone', component: () => import('./views/admin/ModeleEditView.vue') },
        ],
      },
      {
        path: 'moderation',
        label: 'Modération',
        icon: 'pi pi-fw pi-eye',
        description: 'Portfolios publiés.',
        permission: ['isReferentPortfolio', 'ROLE_PORTFOLIO_PUBLISHER'],
        capacite: 'moderateur',
        lot: 6,
        component: () => import('./views/admin/ModerationView.vue'),
      },
    ],
  },
  {
    titre: 'Préférences',
    items: [
      {
        path: 'notifications',
        label: 'Notifications',
        icon: 'pi pi-fw pi-bell',
        description: 'Choisir ce que vous recevez par e-mail, et quand.',
        permission: ['isEtudiant', 'isEnseignant', 'isReferentPortfolio', 'ROLE_PORTFOLIO_CORRECTOR', 'ROLE_PORTFOLIO_PUBLISHER'],
        capacite: 'acces',
        lot: 9,
        component: () => import('./views/NotificationsView.vue'),
      },
    ],
  },
].map((section) => ({
  ...section,
  items: section.items.map((item) => ({ ...item, to: `/unifolio/${item.path}` })),
}));
