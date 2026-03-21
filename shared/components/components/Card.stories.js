import { defineComponent } from 'vue';

// Importation du composant à documenter
import Card from './Card.vue';

export default {
  title: 'Common/Card', // Chemin/nom dans Storybook
  component: Card,
  tags: ['autodocs'],
  argTypes: {
    title: {
      control: { type: 'text' },
      description: 'Ceci est le titre de la Card',
    },
  },
};

// Template de base pour générer une Story
const Template = (args) => defineComponent({
  components: { Card },
  setup() {
    return { args };
  },
  template: '<Card v-bind="args">Ceci est le contenu de la Card</Card>',
});

// Stories
export const Default = Template.bind({});
Default.args = {
  title: 'Ceci est un titre !',
};
