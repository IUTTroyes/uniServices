// ButtonInfo.stories.js
import ButtonInfo from './ButtonInfo.vue'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'
import Button from 'primevue/button'

export default {
  title: 'Common/ButtonInfo',
  component: ButtonInfo,
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
  components: { ButtonInfo },
  setup() {
    return { args }
  },
  template: '<ButtonInfo v-bind="args" />',
})

export const Default = Template.bind({})
Default.args = {
  tooltip: 'View information',
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
