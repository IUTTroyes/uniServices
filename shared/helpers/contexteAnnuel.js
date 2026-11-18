// État mémoire propre à l’onglet ; localStorage sert uniquement à la restauration initiale.
let selection;
let generation = 0;
const YEAR_HEADER = 'X-Annee-Universitaire';
const MODE_HEADER = 'X-Contexte-Annuel';
const aliases = ['anneeUniversitaire', 'anneeUniversitaireId', 'anneeUnivId', 'annee_universitaire'];

const yearId = value => {
  if (typeof value === 'string' && /^\/api\/structure_annee_universitaires\/\d+$/.test(value)) value = value.split('/').pop();
  if ((typeof value !== 'number' && typeof value !== 'string') || !/^\d+$/.test(String(value)) || Number(value) < 1 || !Number.isSafeInteger(Number(value))) throw new Error('Identifiant d’année universitaire invalide.');
  return Number(value);
};
export const getSelectedAnneeUniversitaireId = () => {
  if (selection === undefined) {
    try { const stored = JSON.parse(globalThis.localStorage?.getItem('selectedAnneeUniv') ?? 'null'); selection = stored?.id == null ? null : yearId(stored.id); }
    catch { selection = null; }
  }
  return selection;
};
export const setSelectedAnneeUniversitaireId = value => {
  const next = value == null ? null : yearId(value);
  if (next !== getSelectedAnneeUniversitaireId()) ++generation;
  selection = next;
};

const explicitYear = config => {
  const found = [];
  const url = new URL(config.url ?? '/', 'http://contexte.local');
  let data = config.data;
  if (typeof data === 'string') { try { data = JSON.parse(data); } catch { data = null; } }
  for (const source of [url.searchParams, config.params, data]) {
    for (const alias of aliases) {
      const value = source instanceof URLSearchParams || (typeof FormData !== 'undefined' && source instanceof FormData) ? source.get(alias) : source?.[alias];
      if (value != null && value !== '' && value !== 'courante') found.push(yearId(value));
    }
  }
  if (new Set(found).size > 1) throw new Error('Plusieurs années universitaires contradictoires dans la requête.');
  return found[0] ?? null;
};

export const prepareContexteAnnuel = config => {
  config.headers ??= {};
  // Les relances après renouvellement du jeton conservent exactement le contexte capturé.
  let snapshot = config._contexteAnnuelCapture;
  if (!snapshot) {
    const option = config.contexteAnnuel ?? 'selection';
    const explicit = explicitYear(config);
    let mode, id;
    if (['aucun', 'toutes', 'ressource'].includes(option)) { mode = option; id = null; }
    else if (option === 'selection') { mode = explicit === null ? 'selection' : 'explicite'; id = explicit ?? getSelectedAnneeUniversitaireId(); }
    else { mode = 'explicite'; id = yearId(option); if (explicit !== null && explicit !== id) throw new Error('L’année explicite contredit les paramètres de la requête.'); }
    snapshot = { mode, id, generation, selection: getSelectedAnneeUniversitaireId() };
    config._contexteAnnuelCapture = snapshot;
  }
  config.headers[MODE_HEADER] = snapshot.mode;
  if (snapshot.id !== null) config.headers[YEAR_HEADER] = String(snapshot.id);
  else delete config.headers[YEAR_HEADER];
  return config;
};

export const reponseAnnuelleObsolete = response => {
  const snapshot = response.config?._contexteAnnuelCapture;
  const policy = response.headers?.['x-contexte-annuel'];
  if (!snapshot || snapshot.mode !== 'selection' || ['independant', 'transversal'].includes(policy)) return false;
  return snapshot.generation !== generation || snapshot.selection !== getSelectedAnneeUniversitaireId();
};
