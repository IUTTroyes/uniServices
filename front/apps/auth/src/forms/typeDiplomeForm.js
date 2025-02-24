import { reactive } from 'vue';

const fields = reactive({
  libelle: {
    groupId: 'userId_2',
    label: 'Libellé',
    defaultValue: 'PrimeVue',
    fluid: true,
    // schema: userNameSchema
  },
  sigle: {
    groupId: 'userId_3',
    label: 'Sigle',
    defaultValue: 'PrimeVue',
    fluid: true,
    // schema: userNameSchema
  }
});

export default fields;

