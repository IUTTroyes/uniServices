import ConfigurationView from '@/views/ConfigurationView.vue'
import TypeDiplomeView from '@/views/configuration/TypeDiplomeView.vue'
import AnneeUniversitaireView from '@/views/configuration/AnneeUniversitaireView.vue'
import GestionAccesView from '@/views/configuration/GestionAccesView.vue'

export default [
  {
    path: '',
    component: ConfigurationView,
    name: 'Configuration',
    meta: {
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: null
      }]
    },
  },
  {
    path: 'type-diplome',
    component: TypeDiplomeView,
    name: 'type-diplome',
    meta: {
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Type de diplôme',
        route: null
      }]
    },
  },
  {
    path: 'annee-universitaire',
    component: AnneeUniversitaireView,
    name: 'annee-universitaire',
    meta: {
      breadcrumb: [{ label: 'Portail', route: '/portail' }, {
        label: 'Configuration',
        route: '/configuration'
      }, {
        label: 'Année universitaire',
        route: null
      }]
    },
  },
  {
    path: 'gestion-acces',
    component: GestionAccesView,
    name: 'gestion-acces',
    meta: {
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
