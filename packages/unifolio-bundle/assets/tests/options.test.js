import { describe, it, expect } from 'vitest';
import { couleurCompetence, texteSurCompetence, estIntegrable, titreDepuisNomFichier } from '../components/traces/options';

describe('options des traces', () => {
  it('convertit les clés de couleur des compétences', () => {
    expect(couleurCompetence('c1')).toBe('rgba(156, 43, 38, 1)');
    expect(couleurCompetence('c5', 0.1)).toBe('rgba(43, 76, 118, 0.1)');
    expect(couleurCompetence('#ff0000', 0.5)).toBe('rgba(255, 0, 0, 0.5)');
    expect(couleurCompetence(null)).toBe('var(--p-primary-color)');
    expect(texteSurCompetence('c3')).not.toBe('#ffffff');
  });

  it('reconnaît les contenus intégrables', () => {
    expect(estIntegrable('https://youtu.be/abc')).toBe(true);
    expect(estIntegrable('http://youtu.be/abc')).toBe(false);
    expect(estIntegrable('https://youtube.com.pirate.example/x')).toBe(false);
  });

  it('propose un titre depuis un nom de fichier', () => {
    expect(titreDepuisNomFichier('rapport_sae-2.01.pdf')).toBe('Rapport sae 2.01');
    expect(titreDepuisNomFichier('')).toBe('');
  });
});
