/**
 * Extraction des adresses e-mail à inviter, depuis une saisie libre (une par ligne, ou séparées par , ; espace)
 * ou depuis un fichier CSV (colonne « email » si l'en-tête existe, sinon toute cellule qui ressemble à une adresse).
 * Résultat : adresses en minuscules, sans doublon ; les valeurs invalides sont renvoyées à part.
 */
const MOTIF_EMAIL = /^[^\s@,;]+@[^\s@,;]+\.[^\s@,;]+$/;

export interface AdressesExtraites {
  valides: string[];
  invalides: string[];
}

function trier(valeurs: string[]): AdressesExtraites {
  const valides = new Set<string>();
  const invalides = new Set<string>();
  valeurs
    .map(v => v.trim().replace(/^["']|["']$/g, '').trim().toLowerCase())
    .filter(v => v !== '')
    .forEach(v => (MOTIF_EMAIL.test(v) ? valides : invalides).add(v));

  return { valides: [...valides], invalides: [...invalides] };
}

export function extraireAdresses(saisie: string): AdressesExtraites {
  return trier((saisie || '').split(/[\s,;]+/));
}

export function extraireAdressesCsv(contenu: string): AdressesExtraites {
  const lignes = (contenu || '').replace(/^﻿/, '').split(/\r?\n/).filter(l => l.trim() !== '');
  if (lignes.length === 0) return { valides: [], invalides: [] };

  const separateur = lignes[0].includes(';') ? ';' : ',';
  const entete = lignes[0].split(separateur).map(c => c.trim().replace(/^["']|["']$/g, '').toLowerCase());
  const colonne = entete.findIndex(c => ['email', 'e-mail', 'mail', 'courriel'].includes(c));

  if (colonne >= 0) {
    return trier(lignes.slice(1).map(l => l.split(separateur)[colonne] ?? ''));
  }
  // Pas d'en-tête reconnu : on garde les cellules qui ressemblent à une adresse.
  const cellules = lignes.flatMap(l => l.split(separateur));
  const { valides } = trier(cellules);
  return { valides, invalides: [] };
}

/** Initiales d'un nom (ou d'une adresse) pour les pastilles : « Alice Dupont » → « AD ». */
export function initiales(nom?: string | null): string {
  const base = (nom || '').split('@')[0].replace(/[._-]+/g, ' ').trim();
  if (!base) return '?';
  return base.split(/\s+/).map(p => p[0]).join('').toUpperCase().slice(0, 2);
}
