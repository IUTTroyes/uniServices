import { reactive } from 'vue';

const fields = reactive({
  libelle: {
    groupId: 'libelle',
    label: 'Libellé',
    placeholder: '2024-2025',
    defaultValue: '',
    fluid: true,
    // schema: userNameSchema
  },
  annee: {
    groupId: 'annee',
    label: 'Année',
    defaultValue: '2025',
    typeData: 'int',
    fluid: true,
    // schema: userNameSchema
  },
  commentaire: {
    groupId: 'commentaire',
    label: 'Commentaire libre',
    defaultValue: '',
    fluid: true,
    // schema: userNameSchema
  }
});

export default fields;

