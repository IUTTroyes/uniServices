// Outils du package Finances (sans rendu) : étapes du circuit, montants HT/TTC, IRI.
import { describe, it, expect } from 'vitest';
import {
    etapeCourante, etapeTerminee, ttcDepuisHt, htDepuisTtc, montantsIncoherents, montantApi, idDe, iri, dateSaisie, messageErreurApi,
} from '../utils/finance';

describe('étapes du circuit', () => {
    it('une étape n\'est terminée qu\'une fois le statut suivant atteint', () => {
        expect(etapeCourante('BROUILLON')).toBe(1);
        expect(etapeCourante('SOUMIS')).toBe(1);
        expect(etapeCourante('VERIFICATION_FINANCIERE')).toBe(2);
        expect(etapeCourante('COMMANDE_TRANSMISE')).toBe(4);
        expect(etapeCourante('SERVICE_FAIT_CERTIFIE')).toBe(5);
        // La certification ne termine pas l'étape 5 : seul le paiement le fait.
        expect(etapeTerminee('SERVICE_FAIT_CERTIFIE', 5)).toBe(false);
        expect(etapeTerminee('PAYE', 5)).toBe(true);
        expect(etapeCourante('REJETE')).toBe(0);
        expect(etapeTerminee('REJETE', 1)).toBe(false);
    });
});

describe('montants', () => {
    it('calcule dans les deux sens avec le taux', () => {
        expect(ttcDepuisHt('100', '20')).toBe(120);
        expect(ttcDepuisHt('100', '5.5')).toBe(105.5);
        expect(htDepuisTtc('120', '20.00')).toBe(100);
        expect(htDepuisTtc('10,55', '5.5')).toBe(10);
        expect(ttcDepuisHt('100', null)).toBeNull();
        expect(ttcDepuisHt('', '20')).toBeNull();
    });

    it('repère deux montants incohérents avec le taux', () => {
        expect(montantsIncoherents('100', '120', '20')).toBe(false);
        expect(montantsIncoherents('100', '120.01', '20')).toBe(false);
        expect(montantsIncoherents('100', '119.5', '20')).toBe(true);
        expect(montantsIncoherents('100', null, '20')).toBe(false);
    });

    it('formate pour l\'API', () => {
        expect(montantApi(12.5)).toBe('12.50');
        expect(montantApi('')).toBeNull();
        expect(montantApi('abc')).toBeNull();
    });
});

describe('IRI et divers', () => {
    it('extrait un identifiant', () => {
        expect(idDe({ id: 4 })).toBe(4);
        expect(idDe('/api/finance_fournisseurs/12')).toBe(12);
        expect(idDe({ '@id': '/api/x/3' })).toBe(3);
        expect(idDe(null)).toBeNull();
        expect(idDe('abc')).toBeNull();
        expect(iri('finance_fournisseurs', { id: 7 })).toBe('/api/finance_fournisseurs/7');
        expect(iri('finance_fournisseurs', null)).toBeNull();
    });

    it('prépare une date de champ et un message d\'erreur', () => {
        expect(dateSaisie('2026-11-02T00:00:00+01:00')).toBe('2026-11-02');
        expect(dateSaisie(null)).toBe('');
        expect(messageErreurApi({ response: { data: { violations: [{ message: 'A' }, { message: 'A' }, { message: 'B' }] } } })).toBe('A\nB');
        expect(messageErreurApi({ response: { data: { error: 'Refusé' } } })).toBe('Refusé');
        expect(messageErreurApi(new Error('x'), 'Défaut')).toBe('Défaut');
    });
});
