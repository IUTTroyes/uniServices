import { defineComponent } from 'vue';

// Importation du composant à documenter
import Alert from './Alert.vue';

export default {
  title: 'Common/Alert', // Chemin/nom dans Storybook
  component: Alert,
  tags: ['autodocs'],
  argTypes: {
    severity: {
      control: { type: 'select' },
      options: ['info', 'success', 'warn', 'error'],
      description: 'Niveau de sévérité du message',
    },
    icon: {
      control: { type: 'text' },
      description: 'Nom de l’icône à afficher (optionnel)',
    },
    message: {
      control: { type: 'text' },
      description: 'Message à afficher dans le composant',
    },
  },
};

// Template de base pour générer une Story
const Template = (args) => defineComponent({
  components: { Alert },
  setup() {
    return { args };
  },
  template: '<Alert v-bind="args" />',
});

// Stories
export const Default = Template.bind({});
Default.args = {
  message: 'Ceci est un message d\'information !',
};

export const WithSeverity = Template.bind({});
WithSeverity.args = {
  message: 'Ceci est un message de succès !',
  severity: 'success',
};

export const WithIcon = Template.bind({});
WithIcon.args = {
  message: 'Ceci est un message avec une icône !',
  icon: 'pi-check',
  severity: 'success',
};
