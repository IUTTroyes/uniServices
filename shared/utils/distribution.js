// Packages présents dans l'application construite (voir packages/shell/distributions.js).
/* global __PACKAGES__ */
export const packagesInstalles = typeof __PACKAGES__ !== 'undefined' ? __PACKAGES__ : null;

export const estInstalle = (nom) => packagesInstalles === null || packagesInstalles.includes(nom);

// Clé sous laquelle l'application fournit les manifestes des bundles (packages/shell/assets/main.js).
// shared/ ne doit pas importer le registre : chaque manifeste importe la mise en page, ce qui ferait une boucle.
export const CLE_BUNDLES = Symbol('bundles');
