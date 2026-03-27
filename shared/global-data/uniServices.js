import LogoIut from "@images/logo/logo_intranet_iut_troyes.svg";
import toolsMeta from "./tools.generated.json";

// La liste des outils est désormais générée automatiquement à partir des bundles
// via le fichier tools.generated.json (créé/maintenu par les scripts PHP).
// Par défaut, tous les logos utilisent LogoIut. La valeur 'url' provient du registre
// ou est construite par défaut comme un chemin relatif "/<slug>".
export const tools = (toolsMeta || []).map(item => ({
  name: item.name,
  description: item.description,
  url: item.url || `/${item.urlSlug}`,
  logo: LogoIut || item.logo,
}));

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
