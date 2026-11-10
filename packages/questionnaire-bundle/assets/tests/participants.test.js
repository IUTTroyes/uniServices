import { describe, it, expect } from 'vitest';
import { extraireAdresses, extraireAdressesCsv } from '../utils/participants';

describe('utils/participants', () => {
  it('extrait les adresses d\'une saisie libre, sans doublon et en minuscules', () => {
    const { valides, invalides } = extraireAdresses('Alice@Exemple.fr, bob@exemple.fr\nalice@exemple.fr ; pas-une-adresse');
    expect(valides).toEqual(['alice@exemple.fr', 'bob@exemple.fr']);
    expect(invalides).toEqual(['pas-une-adresse']);
  });

  it('lit la colonne « email » d\'un CSV (séparateur ; et BOM)', () => {
    const csv = '﻿nom;email;groupe\nAlice;alice@exemple.fr;A\nBob;"bob@exemple.fr";B\nSans;;C';
    expect(extraireAdressesCsv(csv)).toEqual({ valides: ['alice@exemple.fr', 'bob@exemple.fr'], invalides: [] });
  });

  it('sans en-tête reconnu, garde les cellules qui ressemblent à une adresse', () => {
    expect(extraireAdressesCsv('alice@exemple.fr,Alice\nbob@exemple.fr,Bob').valides).toEqual(['alice@exemple.fr', 'bob@exemple.fr']);
  });

  it('renvoie une liste vide pour un contenu vide', () => {
    expect(extraireAdressesCsv('')).toEqual({ valides: [], invalides: [] });
    expect(extraireAdresses('').valides).toEqual([]);
  });
});
