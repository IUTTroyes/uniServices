// SelectBlock.stories.js
import SelectBlock from './SelectBlock.vue'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'
import Dropdown from 'primevue/dropdown'

export default {
  title: 'Common/SelectBlock',
  component: SelectBlock,
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
  components: { SelectBlock },
  setup() {
    return { args }
  },
  template: '<SelectBlock v-bind="args" />',
})

export const Default = Template.bind({})
Default.args = {
  tooltip: 'Select an option',
  options: [
    { label: 'Option 1', value: '1' },
    { label: 'Option 2', value: '2' },
    { label: 'Option 3', value: '3' },
  ],
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
//       this.$app.component('Dropdown', Dropdown)
//     },
//   }),
// ]
