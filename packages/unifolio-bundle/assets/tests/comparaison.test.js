import { describe, it, expect } from 'vitest';
import { comparer, ecartGlobal, libelleReponse, qualifierEcart, SEUIL_CONCORDANCE } from '../components/evaluation/comparaison';

const rep = (libelle, normalisee, extra = {}) => ({ critere: libelle, libelle, type: 'echelle', normalisee, echelon: { libelle: `n${normalisee}`, couleur: '#000', valeur: normalisee }, ...extra });

describe('comparaison auto-évaluation / évaluation', () => {
  it('qualifie un écart', () => {
    expect(qualifierEcart(null)).toBeNull();
    expect(qualifierEcart(0).code).toBe('concordant');
    expect(qualifierEcart(SEUIL_CONCORDANCE - 0.01).code).toBe('concordant');
    expect(qualifierEcart(0.34).code).toBe('auto_basse');
    expect(qualifierEcart(-0.34).code).toBe('auto_haute');
  });

  it('rapproche les critères par libellé, sans tenir compte de la casse ni des espaces', () => {
    const lignes = comparer(
      [rep('Maîtrise', 0.67), rep('  PERTINENCE ', 0.33), rep('Appréciation', null, { type: 'texte' })],
      [rep('maîtrise', 1), rep('Pertinence', 0.33)],
    );

    expect(lignes.map((l) => l.libelle)).toEqual(['Maîtrise', '  PERTINENCE ']);
    expect(lignes[0].ecart).toBeCloseTo(0.33);
    expect(lignes[0].qualification.code).toBe('auto_basse');
    expect(lignes[1].qualification.code).toBe('concordant');
  });

  it('ignore les critères que l\'étudiant n\'a pas renseignés', () => {
    expect(comparer([rep('Maîtrise', 0.67)], [])).toEqual([]);
  });

  it('ne qualifie pas un critère non quantifiable', () => {
    const [ligne] = comparer([rep('Remarque', null, { type: 'texte', texte: 'ok' })], [rep('Remarque', null, { type: 'texte', texte: 'ok' })]);
    expect(ligne.ecart).toBeNull();
    expect(ligne.qualification).toBeNull();
  });

  it('calcule l\'écart global', () => {
    expect(ecartGlobal(0.5, 0.8)).toBeCloseTo(0.3);
    expect(ecartGlobal(null, 0.8)).toBeNull();
    expect(ecartGlobal(0.5, undefined)).toBeNull();
  });

  it('formate une réponse quel que soit le critère', () => {
    expect(libelleReponse(rep('A', 1))).toBe('n1');
    expect(libelleReponse({ booleen: true })).toBe('Oui');
    expect(libelleReponse({ booleen: false })).toBe('Non');
    expect(libelleReponse({ valeur: 12, noteMax: 20 })).toBe('12 / 20');
    expect(libelleReponse({ texte: 'Bien' })).toBe('Bien');
    expect(libelleReponse(null)).toBe('—');
  });
});
