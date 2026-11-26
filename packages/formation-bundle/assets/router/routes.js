import { useAnneeStore } from '@stores';

const miettes = () => [{ label: 'Formations', route: '/formation', icon: 'pi pi-sitemap' }];

export const formationRoutes = [
  {
    path: '',
    name: 'formation-accueil',
    component: () => import('../views/FormationHomeView.vue'),
    meta: { permission: 'canViewAdministration' },
  },
  {
    path: 'personnels',
    component: () => import('../views/Personnels/PersonnelsListeView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: [...miettes(), { label: 'Personnels', route: null }] },
  },
  {
    path: 'etudiants',
    component: () => import('../views/Etudiants/EtudiantsListeView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: [...miettes(), { label: 'Étudiants', route: null }] },
  },
  {
    path: 'etudiant',
    meta: { permission: 'canViewAdministration' },
    children: [
      {
        path: 'ajout',
        component: () => import('../views/Etudiants/EtudiantsAjoutView.vue'),
        meta: { breadcrumb: [...miettes(), { label: 'Ajouter des étudiants', route: null }] },
        children: [
          { path: 'apogee', component: () => import('../components/Etudiant/EtudiantAddApogee.vue') },
          { path: 'manuel', component: () => import('../components/Etudiant/EtudiantAddManuel.vue') },
          { path: 'sans-scolarite', name: 'ajout-etudiants-sans-scolarite', component: () => import('../components/Etudiant/EtudiantsSansScolarite.vue') },
        ],
      },
      {
        path: 'ajout/result',
        component: () => import('../views/Etudiants/EtudiantsAjoutResultView.vue'),
        meta: { breadcrumb: [...miettes(), { label: "Résultat de l'import", route: null }] },
        props: true,
      },
    ],
  },
  {
    path: 'pn',
    component: () => import('../views/Pn/PnView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: [...miettes(), { label: 'Programmes (PN)', route: null }] },
  },
  {
    path: 'referentiels-competences',
    component: () => import('../views/Ref-Competences/IndexView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: [...miettes(), { label: 'Référentiels de compétences', route: null }] },
  },
  {
    path: 'annee/:anneeId/etudiants',
    name: 'liste-etudiants',
    component: () => import('../views/Etudiants/EtudiantsListeView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: () => [...miettes(), { label: useAnneeStore().annee?.libelle ?? 'Année', route: null }, { label: 'Liste des étudiants', route: null }] },
  },
  {
    path: 'annee/:anneeId/groupes/structure',
    name: 'structure-groupe',
    component: () => import('../views/Administration/AnneeActions/GroupesView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: () => [...miettes(), { label: useAnneeStore().annee?.libelle ?? 'Année', route: null }, { label: 'Structure des groupes', route: null }] },
  },
  {
    path: 'annee/:anneeId/groupes/affectation',
    name: 'affectation-groupe',
    component: () => import('../views/Administration/AnneeActions/GroupesView.vue'),
    meta: { permission: 'canViewAdministration', breadcrumb: () => [...miettes(), { label: useAnneeStore().annee?.libelle ?? 'Année', route: null }, { label: 'Affectation des groupes', route: null }] },
  },
  {
    path: 'departement',
    component: () => import('../views/Super-Administration/Departement/NewDepartement.vue'),
    meta: { permission: 'SUPER_ADMIN', breadcrumb: [...miettes(), { label: 'Nouveau département', route: null }] },
  },
  {
    path: 'departements',
    component: () => import('../views/Super-Administration/Departement/ListDepartement.vue'),
    meta: { permission: 'SUPER_ADMIN', breadcrumb: [...miettes(), { label: 'Départements', route: null }] },
  },
  {
    path: 'type-diplome',
    name: 'super-admin-type-diplome-new',
    component: () => import('../views/Super-Administration/TypeDiplome/NewTypeDiplome.vue'),
    meta: { permission: 'SUPER_ADMIN', breadcrumb: [...miettes(), { label: 'Nouveau type de diplôme', route: null }] },
  },
  {
    path: 'types-diplomes',
    name: 'super-admin-types-diplomes',
    component: () => import('../views/Super-Administration/TypeDiplome/ListTypeDiplome.vue'),
    meta: { permission: 'SUPER_ADMIN', breadcrumb: [...miettes(), { label: 'Types de diplômes', route: null }] },
  },
  {
    path: 'groupe/:groupeId',
    name: 'groupe-edit',
    component: () => import('../views/Super-Administration/Groupe/EditGroupeView.vue'),
    meta: { permission: 'SUPER_ADMIN', breadcrumb: [...miettes(), { label: 'Groupe', route: null }] },
  },
];

// Écrans auparavant servis par l'intranet : les anciennes adresses (favoris, liens) restent valables.
const DEPLACEES = [
  ['administration/personnels', 'personnels'],
  ['administration/etudiants', 'etudiants'],
  ['administration/etudiant/ajout', 'etudiant/ajout'],
  ['administration/etudiant/ajout/apogee', 'etudiant/ajout/apogee'],
  ['administration/etudiant/ajout/manuel', 'etudiant/ajout/manuel'],
  ['administration/etudiant/ajout/sans-scolarite', 'etudiant/ajout/sans-scolarite'],
  ['administration/pn', 'pn'],
  ['administration/referentiels-competences', 'referentiels-competences'],
  ['administration/annee/:anneeId/etudiants', 'annee/:anneeId/etudiants'],
  ['administration/annee/:anneeId/groupes/structure', 'annee/:anneeId/groupes/structure'],
  ['administration/annee/:anneeId/groupes/affectation', 'annee/:anneeId/groupes/affectation'],
  ['super-administration/departement', 'departement'],
  ['super-administration/departements', 'departements'],
  ['super-administration/type-diplome', 'type-diplome'],
  ['super-administration/types-diplomes', 'types-diplomes'],
  ['super-administration/groupe/:groupeId', 'groupe/:groupeId'],
];

export const anciennesAdresses = DEPLACEES.map(([avant, apres]) => ({
  path: `/intranet/${avant}`,
  redirect: (to) => ({
    path: `/formation/${apres.replace(/:(\w+)/g, (_, nom) => to.params[nom])}`,
    query: to.query,
    hash: to.hash,
  }),
}));
