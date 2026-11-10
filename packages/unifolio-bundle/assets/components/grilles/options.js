export const GRANULARITES = [
  { value: 'ac', label: 'Apprentissage critique', description: 'Chaque trace est rattachée et évaluée sur des AC.' },
  { value: 'niveau', label: 'Niveau de compétence', description: 'Les traces sont rattachées et évaluées par niveau de compétence.' },
  { value: 'competence', label: 'Compétence', description: 'Les traces sont rattachées et évaluées par compétence, au niveau attendu de l\'année.' },
];

export const libelleGranularite = (v) => GRANULARITES.find((g) => g.value === v)?.label ?? v;

export const TYPES_CRITERE = [
  { value: 'echelle', label: 'Échelle de niveaux' },
  { value: 'booleen', label: 'Oui / Non' },
  { value: 'note', label: 'Note numérique' },
  { value: 'texte', label: 'Appréciation libre' },
];

export const PORTEES = {
  type_diplome: { label: 'Nationale', severity: 'secondary', icon: 'pi pi-globe' },
  departement: { label: 'Département', severity: 'info', icon: 'pi pi-building' },
  diplome: { label: 'Formation', severity: 'success', icon: 'pi pi-bookmark' },
};

export const CRITERE_IA = {
  libelle: 'Usage de l\'IA',
  type: 'echelle',
  source: 'declaration_ia',
  poids: 0,
  noteMax: null,
  description: 'Pertinence et transparence de l\'usage de l\'IA déclaré par l\'étudiant.',
  aide: 'Comparer la déclaration (conception, réalisation, précisions) à la production : l\'usage est-il assumé, maîtrisé et au service de l\'apprentissage ?',
  obligatoire: false,
  visibleEtudiant: true,
  utiliseAutoEval: true,
};

export const nouveauCritere = () => ({
  libelle: '',
  type: 'echelle',
  source: 'saisie',
  poids: 1,
  noteMax: null,
  description: '',
  aide: '',
  obligatoire: true,
  visibleEtudiant: true,
  utiliseAutoEval: true,
});

export const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) : '');
