// Distributions du front : APP_DISTRIBUTION choisit le registre des bundles et la liste des packages installés.
import path from 'path';

const PACKAGES = {
  unifolio: ['auth', 'unifolio'],
};

export function distribution(shellDir, nom = process.env.APP_DISTRIBUTION || 'intranet') {
  if (nom !== 'intranet' && !PACKAGES[nom]) {
    throw new Error(`Distribution inconnue : ${nom}`);
  }

  return {
    registre: path.resolve(shellDir, nom === 'intranet' ? 'assets/bundles-registry.js' : `assets/distributions/${nom}.js`),
    // null : tous les packages sont installés.
    packages: PACKAGES[nom] ?? null,
  };
}
