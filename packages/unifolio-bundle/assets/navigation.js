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
      { path: 'auto-evaluation', label: 'Auto-évaluation', icon: 'pi pi-fw pi-chart-line', description: 'Vous positionner sur les compétences de votre année.', permission: 'isEtudiant', capacite: 'etudiant', lot: 3 },
      { path: 'portfolios', label: 'Mes portfolios', icon: 'pi pi-fw pi-book', description: 'Portfolio académique de l\'année et portfolio vitrine.', permission: 'isEtudiant', capacite: 'etudiant', lot: 5 },
      { path: 'cv', label: 'Mon CV', icon: 'pi pi-fw pi-id-card', description: 'Expériences, formations et compétences.', permission: 'isEtudiant', capacite: 'etudiant', lot: 5 },
    ],
  },
  {
    titre: 'Évaluation',
    items: [
      { path: 'evaluations', label: 'À évaluer', icon: 'pi pi-fw pi-check-square', description: 'Traces et portfolios des étudiants qui vous sont affectés.', permission: ['isEnseignant', 'isReferentPortfolio', 'ROLE_PORTFOLIO_CORRECTOR'], capacite: 'correcteur', lot: 4 },
      { path: 'suivi', label: 'Suivi des étudiants', icon: 'pi pi-fw pi-users', description: 'Progression par compétence, promo par promo.', permission: ['isEnseignant', 'isReferentPortfolio', 'ROLE_PORTFOLIO_CORRECTOR'], capacite: 'correcteur', lot: 7 },
    ],
  },
  {
    titre: 'Administration',
    items: [
      { path: 'admin/formations', label: 'Paramétrage des formations', icon: 'pi pi-fw pi-sliders-h', description: 'Granularité d\'évaluation et grille par défaut.', permission: 'isReferentPortfolio', capacite: 'referent', lot: 2 },
      { path: 'admin/grilles', label: 'Grilles d\'évaluation', icon: 'pi pi-fw pi-table', description: 'Critères, échelles et versions.', permission: 'isReferentPortfolio', capacite: 'referent', lot: 2 },
      { path: 'admin/campagnes', label: 'Campagnes', icon: 'pi pi-fw pi-calendar', description: 'Calendrier de dépôt et d\'évaluation, affectations.', permission: 'isReferentPortfolio', capacite: 'referent', lot: 4 },
      { path: 'admin/modeles', label: 'Modèles de portfolio', icon: 'pi pi-fw pi-clone', description: 'Pages et blocs imposés du portfolio académique.', permission: 'isReferentPortfolio', capacite: 'referent', lot: 5 },
      { path: 'moderation', label: 'Modération', icon: 'pi pi-fw pi-eye', description: 'Portfolios vitrines publiés.', permission: ['isReferentPortfolio', 'ROLE_PORTFOLIO_PUBLISHER'], capacite: 'moderateur', lot: 6 },
    ],
  },
].map((section) => ({
  ...section,
  items: section.items.map((item) => ({ ...item, to: `/unifolio/${item.path}` })),
}));
