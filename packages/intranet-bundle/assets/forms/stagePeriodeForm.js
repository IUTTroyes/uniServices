import { reactive } from 'vue';
import api from '@helpers/axios.js'

const fields = (params) => reactive({
  libelle: {
    groupId: 'userId_2',
    label: 'Libellé',
    defaultValue: '',
    placeholder: 'Ex: Période de stage 1',
    fluid: true,
    validation: {
      validateOnBlur:true,
    }
    // schema: userNameSchema
  },
  structureAnneeUniversitaire: {
    groupId: 'userId_3',
    label: 'Année universitaire',
    defaultValue: '',
    fluid: true,
    as: 'Select',
    fetchData: async () => {
      const response = await api.get('/api/structure_annee_universitaires');
      return response.data;
    },
    optionLabel: 'libelle',
    validation: {
      validateOnBlur:true,
    }
    // schema: userNameSchema
  },
  semestreProgramme: {
    groupId: 'userId_4',
    label: 'Semestre Programme',
    defaultValue: '',
    fluid: true,
    as: 'Select',
    fetchData: async () => {
      const response = await api.get(`/api/structure_semestres?departement=${params.departement}`);
      return response.data;
    },
    optionLabel: 'libelle',
    // schema: userNameSchema
  },
  dateDebut: {
    groupId: 'userId_5',
    label: 'Date de début',
    defaultValue: null,
    typeData: 'date',
    fluid: true,
    as: 'DatePicker',
    showIcon: true
    // schema: userNameSchema
  },
  dateFin: {
    groupId: 'userId_6',
    label: 'Date de fin',
    defaultValue: null,
    typeData: 'date',
    fluid: true,
    as: 'DatePicker',
    showIcon: true
    // schema: userNameSchema
  },
  nbSemaines: {
    groupId: 'userId_7',
    label: 'Nombre de semaines',
    defaultValue: '',
    fluid: true,
    typeData: 'int',
    // schema: userNameSchema
  },
  nbJours: {
    groupId: 'userId_8',
    label: 'Nombre de jours',
    defaultValue: '',
    fluid: true,
    typeData: 'int',
    // schema: userNameSchema
  },
  semestresSaisie: {
    groupId: 'userId_9',
    label: 'Semestre(s) de saisie',
    defaultValue: [],
    fluid: true,
    as: 'Checkbox',
    fetchData: async () => {
      const response = await api.get(`/api/structure_semestres?departement=${params.departement}`);
      return response.data;
    },
    optionLabel: 'libelle',
    layout: 'row'
    // schema: userNameSchema
  },
});

export default fields;

