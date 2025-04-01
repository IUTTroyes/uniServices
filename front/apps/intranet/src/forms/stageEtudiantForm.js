import { reactive } from 'vue'
import api from '@helpers/axios.js'

export const step1Fields = reactive({
  libelle: {
    groupId: 'userId_2',
    label: 'Libellé',
    defaultValue: '',
    placeholder: 'Ex: Période de stage 1',
    fluid: true,
    validation: {
      validateOnBlur: true,
    }
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
  }
})

const step2Fields = (params) => reactive({})
const step3Fields = (params) => reactive({})
const step4Fields = (params) => reactive({})
const step5Fields = (params) => reactive({})
const step6Fields = (params) => reactive({})

export {
  step2Fields,
  step3Fields,
  step4Fields,
  step5Fields,
  step6Fields
}

