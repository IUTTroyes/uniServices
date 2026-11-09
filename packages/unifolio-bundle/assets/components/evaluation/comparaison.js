/**
 * Rapprochement de l'auto-évaluation de l'étudiant et de l'évaluation de l'enseignant,
 * critère par critère (par libellé : les deux peuvent reposer sur deux versions de la grille).
 */
const cle = (libelle) => String(libelle ?? '').trim().toLowerCase();

/** Seuil en deçà duquel les deux positionnements sont considérés concordants (10 points de %). */
export const SEUIL_CONCORDANCE = 0.1;

export const qualifierEcart = (ecart) => {
  if (ecart === null || ecart === undefined) return null;
  if (Math.abs(ecart) < SEUIL_CONCORDANCE) return { code: 'concordant', libelle: 'Concordant', severite: 'success' };
  return ecart < 0
    ? { code: 'auto_haute', libelle: 'Auto-évaluation plus haute', severite: 'warn' }
    : { code: 'auto_basse', libelle: 'Auto-évaluation plus basse', severite: 'info' };
};

/**
 * @param {Array} evaluation reponsesDetaillees de l'évaluation enseignante
 * @param {Array} auto       reponses de l'auto-évaluation (même format)
 * @returns {Array<{critere, libelle, type, enseignant, etudiant, ecart, qualification}>}
 */
export const comparer = (evaluation = [], auto = []) => {
  const parLibelle = new Map(auto.map((r) => [cle(r.libelle), r]));
  return evaluation.map((r) => {
    const a = parLibelle.get(cle(r.libelle)) ?? null;
    const ecart = r.normalisee !== null && r.normalisee !== undefined && a?.normalisee !== null && a?.normalisee !== undefined
      ? a.normalisee - r.normalisee
      : null;

    return { critere: r.critere, libelle: r.libelle, type: r.type, enseignant: r, etudiant: a, ecart, qualification: qualifierEcart(ecart) };
  }).filter((l) => l.etudiant !== null);
};

/** Écart global entre les deux scores (0-1), ou null. */
export const ecartGlobal = (scoreEnseignant, scoreEtudiant) =>
  scoreEnseignant === null || scoreEnseignant === undefined || scoreEtudiant === null || scoreEtudiant === undefined
    ? null
    : scoreEtudiant - scoreEnseignant;

/** Texte d'une réponse, quel que soit le type de critère. */
export const libelleReponse = (r) => {
  if (!r) return '—';
  if (r.echelon) return r.echelon.libelle;
  if (r.booleen !== null && r.booleen !== undefined) return r.booleen ? 'Oui' : 'Non';
  if (r.valeur !== null && r.valeur !== undefined) return `${r.valeur} / ${r.noteMax}`;
  return r.texte ?? '—';
};
