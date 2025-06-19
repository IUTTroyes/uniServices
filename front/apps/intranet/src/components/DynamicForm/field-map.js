/**
 * Mapping des types de champs vers leurs composants et configurations
 * @type {Object.<string, {component: string, defaultProps?: Object}>}
 */
export const fieldMap = {
  text: {
    component: 'input',
    defaultProps: { type: 'text', class: 'form-input w-full rounded-md border-gray-300 shadow-sm' }
  },
  textarea: {
    component: 'textarea',
    defaultProps: { rows: 3, class: 'form-textarea w-full rounded-md border-gray-300 shadow-sm' }
  },
  number: {
    component: 'input',
    defaultProps: { type: 'number', class: 'form-input w-full rounded-md border-gray-300 shadow-sm' }
  },
  password: {
    component: 'input',
    defaultProps: { type: 'password', class: 'form-input w-full rounded-md border-gray-300 shadow-sm' }
  },
  checkbox: {
    component: 'input',
    defaultProps: { type: 'checkbox', class: 'form-checkbox rounded border-gray-300 text-primary-600 shadow-sm' }
  },
  radio: {
    component: 'input',
    defaultProps: { type: 'radio', class: 'form-radio border-gray-300 text-primary-600 shadow-sm' }
  },
  select: {
    component: 'select',
    defaultProps: { class: 'form-select w-full rounded-md border-gray-300 shadow-sm' }
  },
  date: {
    component: 'input',
    defaultProps: { type: 'date', class: 'form-input w-full rounded-md border-gray-300 shadow-sm' }
  }
}

/**
 * Vérifie si un type de champ est valide
 * @param {string} type - Le type de champ à vérifier
 * @returns {boolean}
 */
export const isValidFieldType = (type) => Object.keys(fieldMap).includes(type)

/**
 * Récupère la configuration d'un type de champ
 * @param {string} type - Le type de champ
 * @returns {Object} La configuration du champ ou la configuration par défaut
 */
export const getFieldConfig = (type) => {
  return fieldMap[type] || fieldMap.text
} 