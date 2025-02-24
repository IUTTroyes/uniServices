// ButtonDelete.stories.js
import ButtonDelete from './ButtonDelete.vue'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'
import Button from 'primevue/button'

export default {
  title: 'Common/ButtonDelete',
  component: ButtonDelete,
  tags: ['autodocs'],
  decorators: [
    (story) => ({
      components: { story },
      template: '<story />',
      setup() {
        const app = createApp({})
        app.use(PrimeVue)
        app.use(ConfirmationService)
        app.directive('tooltip', Tooltip)
        app.component('Button', Button)
        app.mount('#app')
        return { app }
      },
    }),
  ],
}

const Template = (args) => ({
  components: { ButtonDelete },
  setup() {
    return { args }
  },
  template: '<ButtonDelete v-bind="args" />',
})

export const Default = Template.bind({})
Default.args = {
  tooltip: 'Supprimer cet élément',
}
