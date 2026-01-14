export const tools = [
  {
    name: "UniTranet",
    description: "Gestion de la structure des formations et des étudiants.",
    url: "http://localhost:3001/intranet",
    logo: "common-images/logo/logo_intranet_iut_troyes.svg",
  },
  {
    name: "UniFolio",
    description: "Outil de création et de gestion de portfolios universitaires.",
    url: "http://localhost:3002/unifolio",
    logo: "common-images/logo/logo_unifolio.png",
  },
  {
    name: "Correcto",
    description: "Plateforme de correction des travaux et des examens.",
    url: "http://localhost:3004/correcto",
    logo: "common-images/logo/logo_correcto.png",
  },
  {
    name: "UniEdt",
    description: "Plateforme de conception des emplois du temps et gestion des contraintes.",
    url: "http://localhost:3003/edt",
    logo: "common-images/logo/logo_uniedt.png",
  }
]

export const statuts = [
  { label: 'Maître de conférences', value: 'MCF', severity: 'info' },
  { label: 'Professeur des universités', value: 'PU', severity: 'info' },
  { label: 'ATER', value: 'ATER', severity: 'primary' },
  { label: 'PRAG', value: 'PRAG', severity: 'info' },
  { label: 'IE', value: 'IE', severity: 'success' },
  { label: 'ENSAM', value: 'ENSAM', severity: 'primary' },
  { label: 'DO', value: 'DO', severity: 'primary' },
  { label: 'Vacataire', value: 'vacataire', severity: 'secondary' },//todo: VAC ?
  { label: 'PRCE', value: 'PRCE', severity: 'info' },
  { label: 'BIATSS', value: 'BIATSS', severity: 'warn' },
  { label: 'Autre', value: 'AUTRE', severity: 'secondary' }
]

export const typesGroupes = [
    { label: 'Cours Magistral', value: 'CM' },
    { label: 'Travaux Dirigés', value: 'TD' },
    { label: 'Travaux Pratiques', value: 'TP' },
    { label: 'Langue', value: 'LV' },
    { label: 'Projet', value: 'PROJET' },
    { label: 'Autre', value: 'AUTRE' }
];
