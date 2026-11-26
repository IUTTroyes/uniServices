// Packages présents dans l'application construite (voir packages/shell/distributions.js).
/* global __PACKAGES__ */
export const packagesInstalles = typeof __PACKAGES__ !== 'undefined' ? __PACKAGES__ : null;

export const estInstalle = (nom) => packagesInstalles === null || packagesInstalles.includes(nom);
