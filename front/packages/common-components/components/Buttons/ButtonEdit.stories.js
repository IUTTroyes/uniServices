// ButtonEdit.stories.js
import ButtonEdit from './ButtonEdit.vue'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'
import Button from 'primevue/button'

export default {
  title: 'Common/ButtonEdit',
  component: ButtonEdit,
  tags: ['autodocs'],
  decorators: [
    (story) => ({
      components: { story },
      template: '<story />',
      setup() {
        return {}
      },
    }),
  ],
}

const Template = (args) => ({
  components: { ButtonEdit },
  setup() {
    return { args }
  },
  template: '<ButtonEdit v-bind="args" />',
})

export const Default = Template.bind({})
Default.args = {
  tooltip: 'Edit this item',
}

// Default.decorators = [
//   (story) => ({
//     components: { story },
//     template: '<story />',
//     setup() {
//       return {}
//     },
//     mounted() {
//       this.$app.use(PrimeVue)
//       this.$app.use(ConfirmationService)
//       this.$app.directive('tooltip', Tooltip)
//       this.$app.component('Button', Button)
//     },
//   }),
// ]
