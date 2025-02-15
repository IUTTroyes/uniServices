import ConfigurationView from '@/views/ConfigurationView.vue'
import TypeDiplomeView from '@/views/configuration/TypeDiplomeView.vue'

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
]
