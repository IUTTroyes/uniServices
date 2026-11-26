/**
 * Identité visuelle, construite depuis le registre généré (source : les bundle.meta.json).
 * Seule la géométrie du « U » est définie ici.
 */
import tools from '../../../global-data/tools.generated.json';

export const U_LEFT = 'M11 18A11 11 0 0 1 33 18V56A17 17 0 0 0 42.275 71.143A15 15 0 0 0 38.11 93.143A39 39 0 0 1 11 56Z';
export const U_RIGHT = 'M50 73A17 17 0 0 0 67 56V18A11 11 0 0 1 89 18V56A39 39 0 0 1 50 95A11 11 0 0 1 50 73Z';

export const UNI_BRAND = Object.fromEntries(
  tools.filter((tool) => tool.brand).map((tool) => [tool.brand.key, { name: tool.name, ...tool.brand }]),
);

const mark = (transform, left, right) =>
  `<g transform="${transform}"><path fill="${left}" d="${U_LEFT}"/><path fill="${right}" d="${U_RIGHT}"/></g>`;

/** Favicon d'une application : son pictogramme (ou le « U » pour le portail) sur sa couleur. */
export function faviconUrl(key) {
  const brand = UNI_BRAND[key] ?? UNI_BRAND.service;
  if (!brand) return null;
  const content = brand.pictogram
    ? `<path transform="translate(12.4 12.4) scale(2.8)" d="${brand.pictogram}" fill="none" stroke="#FFFFFF" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>`
    : mark('translate(13 12.5) scale(0.66)', '#FFFFFF', brand.accent);
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 92"><rect width="92" height="92" rx="22" fill="${brand.color}"/>${content}</svg>`;
  return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}
