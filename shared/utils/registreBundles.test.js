import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { describe, it, expect } from 'vitest';

const racine = resolve(__dirname, '../..');
const fichiers = (dossier) => readdirSync(dossier).flatMap((nom) => {
  if (nom === 'node_modules') return [];
  const chemin = join(dossier, nom);
  if (statSync(chemin).isDirectory()) return fichiers(chemin);

  return /\.(js|ts|vue)$/.test(nom) && !/\.test\.js$/.test(nom) ? [chemin] : [];
});

describe('registre des bundles', () => {
  // Les manifestes importent la mise en page partagée : si shared/ ou un package importait le registre,
  // la boucle ferait planter l'application selon l'ordre de chargement des modules.
  it('n’est importé que par le shell', () => {
    const dossiers = [join(racine, 'shared'), ...readdirSync(join(racine, 'packages'))
      .filter((nom) => nom.endsWith('-bundle'))
      .map((nom) => join(racine, 'packages', nom, 'assets'))];

    const fautifs = dossiers.flatMap(fichiers)
      .filter((f) => /(from|import\()\s*['"][^'"]*bundles-registry['"]/.test(readFileSync(f, 'utf8')));

    expect(fautifs).toEqual([]);
  });
});
