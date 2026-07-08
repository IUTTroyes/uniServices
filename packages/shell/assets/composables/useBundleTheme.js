import { updatePreset } from '@primeuix/themes';
import { bundles } from '../bundles-registry';

/**
 * Construit un objet de tokens PrimeVue pour une couleur donnée.
 * Ex : 'violet' => { 50: '{violet.50}', 100: '{violet.100}', ... }
 */
function buildColorTokens(colorName) {
  const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
  return shades.reduce((acc, shade) => {
    acc[shade] = `{${colorName}.${shade}}`;
    return acc;
  }, {});
}

/**
 * Retourne le bundle correspondant à une route donnée
 * en se basant sur le préfixe de chemin défini dans chaque manifest.
 */
function getBundleForRoute(path) {
  return bundles.find(bundle =>
    bundle.routes?.some(route => path.startsWith(route.path))
  );
}

/**
 * Applique le thème PrimeVue (primaryColor) du bundle actif.
 * Doit être appelé dans un router.afterEach().
 */
export function applyBundleTheme(toPath) {
  const bundle = getBundleForRoute(toPath);

  if (!bundle?.primaryColor) return;

  updatePreset({
    semantic: {
      primary: buildColorTokens(bundle.primaryColor)
    }
  });
}
