import { describe, it, expect } from 'vitest';
import { dateFr, statutConvention, etatDepuisStatut, aUneDemande, nomContact, adresseLigne, idDepuisRelation, STATUT_PAR_ETAT } from '../utils/stage.js';
import { messageErreurApi } from '../utils/erreurApi.js';

describe('utils/stage', () => {
  it('formate les dates sans décalage de fuseau', () => {
    expect(dateFr('2027-04-05T00:00:00+02:00')).toBe('05/04/2027');
    expect(dateFr('2027-04-05')).toBe('05/04/2027');
    expect(dateFr(null)).toBe('');
    expect(dateFr('texte')).toBe('texte');
  });

  it('traduit les états et les statuts dans les deux sens', () => {
    for (const [etat, statut] of Object.entries(STATUT_PAR_ETAT)) {
      expect(statutConvention(etat)).toBe(statut);
      if (etat !== 'ETAT_STAGE_CONVENTION_IMPRIMEE') {
        expect(etatDepuisStatut(statut, true)).toBe(etat);
      }
    }
    expect(statutConvention('ETAT_STAGE_AUTORISE')).toBe('Aucune');
    expect(etatDepuisStatut('Aucune', true)).toBe('ETAT_STAGE_AUTORISE');
    expect(etatDepuisStatut('Aucune', false)).toBe('ETAT_STAGE_INCOMPLET');
  });

  it('distingue une demande d\'une simple autorisation de saisie', () => {
    expect(aUneDemande('ETAT_STAGE_AUTORISE')).toBe(false);
    expect(aUneDemande('ETAT_STAGE_INCOMPLET')).toBe(false);
    expect(aUneDemande('ETAT_STAGE_DEPOSE')).toBe(true);
    expect(aUneDemande(undefined)).toBe(false);
  });

  it('affiche contacts et adresses', () => {
    expect(nomContact({ civilite: 'Mme', prenom: 'Sylvie', nom: 'MARTIN' })).toBe('Mme Sylvie MARTIN');
    expect(nomContact({ prenom: 'Jean', nom: 'DUPONT' })).toBe('M. Jean DUPONT');
    expect(nomContact({}, 'Non affecté')).toBe('Non affecté');
    expect(adresseLigne({ adresse: '9 rue de Québec', complement1: 'Bât. A', codePostal: '10430', ville: 'Rosières' }))
      .toBe('9 rue de Québec, Bât. A - 10430 Rosières (France)');
    expect(adresseLigne(null)).toBe('-');
  });

  it('extrait l\'identifiant d\'une relation, objet ou IRI', () => {
    expect(idDepuisRelation({ id: 12 })).toBe(12);
    expect(idDepuisRelation({ '@id': '/api/stage_etudiants/7' })).toBe(7);
    expect(idDepuisRelation('/api/personnels/3')).toBe(3);
    expect(idDepuisRelation(null)).toBe(null);
    expect(idDepuisRelation('/api/x/abc')).toBe(null);
  });
});

describe('utils/erreurApi', () => {
  it('privilégie les violations de validation', () => {
    const error = { response: { data: { violations: [{ message: 'A' }, { message: 'B' }, { message: 'A' }], detail: 'X' } } };
    expect(messageErreurApi(error, 'défaut')).toBe('A\nB');
  });
  it('reprend le message du serveur, sinon le défaut', () => {
    expect(messageErreurApi({ response: { data: { error: 'Refusé' } } }, 'défaut')).toBe('Refusé');
    expect(messageErreurApi({ response: { data: { detail: 'Interdit' } } }, 'défaut')).toBe('Interdit');
    expect(messageErreurApi(new Error('réseau'), 'défaut')).toBe('défaut');
  });
});
