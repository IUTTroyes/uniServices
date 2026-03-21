// en utilisant le tableau dans common-data-global, construire les deux méthods getStatutsText et getStatuColor

import { statuts } from '@config/uniServices.js'
import { capitalize } from '@helpers/string.js'

/*
export const statuts = [
  { label: 'Maître de conférences', value: 'MCF', severity: 'info' },
  { label: 'Professeur des universités', value: 'PU', severity: 'info' },
  { label: 'ATER', value: 'ATER', severity: 'primary' },
  { label: 'PRAG', value: 'PRAG', severity: 'info' },
  { label: 'IE', value: 'IE', severity: 'success' },
  { label: 'ENSAM', value: 'ENSAM', severity: 'primary' },
  { label: 'DO', value: 'DO', severity: 'primary' },
  { label: 'Vacataire', value: 'VAC', severity: 'success' },
  { label: 'PRCE', value: 'PRCE', severity: 'info' },
  { label: 'BIATSS', value: 'BIATSS', severity: 'warn' },
  { label: 'Autre', value: 'AUTRE', severity: 'secondary' }
]
 */
export function getStatutText(value) {
  const statut = statuts.find(statut => statut.value === value)
  return statut ? statut.label : capitalize(value)
}

export function getStatutColor(value) {
  const statut = statuts.find(statut => statut.value === value)
  return statut ? statut.severity : ''
}
