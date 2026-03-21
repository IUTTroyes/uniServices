import { defineComponent } from 'vue';

// Importation du composant à documenter
import InputBlock from './InputBlock.vue';

export default {
  title: 'Common/InputBlock', // Chemin/nom dans Storybook
  component: InputBlock,
  tags: ['autodocs'],
  argTypes: {
    id: {
      control: { type: 'text' },
      description: 'Ceci est l\'id de l\'input',
    },
    label: {
      control: { type: 'text' },
      description: 'Ceci est le label de l\'input',
    },
    help: {
      control: { type: 'text' },
      description: 'Ceci est le texte d\'aide',
    },

  },
};

// Template de base pour générer une Story
const Template = (args) => defineComponent({
  components: { InputBlock },
  setup() {
    return { args };
  },
  template: '<InputBlock v-bind="args"></InputBlock>',
});

// Stories
export const Default = Template.bind({});
Default.args = {
  id: 'id',
  help: 'Ceci est un texte d\'aide',
  label: 'Label du champs'
};
