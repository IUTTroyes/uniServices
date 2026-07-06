import ConfigurationView from '../../views/ConfigurationView.vue'
import ListAnneeUniv from '../../views/configuration/annee_univ/ListAnneeUniv.vue';
import NewAnneeUniv from "../../views/configuration/annee_univ/NewAnneeUniv.vue";
import AnneeUnivDiplomes from "../../views/configuration/annee_univ/AnneeUnivDiplomes.vue";
import EditAnneeUniv from "../../views/configuration/annee_univ/EditAnneeUniv.vue";
import GestionAccesView from '../../views/configuration/GestionAccesView.vue'
import EtablissementView from "../../views/configuration/EtablissementView.vue";

export default [
  {
    path: '',
    component: ConfigurationView,
    name: 'Configuration',
    meta: {
      permission: 'isReferent',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: null
      }]
    },
  },
  {
    path: 'etablissement',
    component: EtablissementView,
    name: 'etablissement',
    meta: {
      permission: 'isSuperAdmin',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Établissement',
        route: null
      }]
    },
  },
  {
    path: 'annees-universitaires',
    component: ListAnneeUniv,
    name: 'annee-universitaire',
    meta: {
      permission: 'isReferent',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Année universitaire',
        route: null
      }],
    }
  },
  {
    path: 'annee-universitaire/new',
    component: NewAnneeUniv,
    name: 'annee-universitaire-new',
    meta: {
      permission: 'isReferent',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Nouvelle année universitaire',
        route: null
      }]
    }
  },
  {
    path: 'annee-universitaire/:id/diplomes',
    component: AnneeUnivDiplomes,
    name: 'annee-universitaire-diplomes',
    meta: {
      permission: 'isReferent',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Diplômes',
        route: null
      }]
    }
  },
  {
    path: 'annee-universitaire/:id/edit',
    component: EditAnneeUniv,
    name: 'annee-universitaire-edit',
    meta: {
      permission: 'isReferent',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Modifier l\'année universitaire',
        route: null
      }]
    }
  },
  {
    path: 'gestion-acces',
    component: GestionAccesView,
    name: 'gestion-acces',
    meta: {
      permission: 'isReferent',
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Gestion des accès',
        route: null
      }]
    },
  }
]
