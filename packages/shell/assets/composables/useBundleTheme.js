import { updatePreset } from '@primeuix/themes';
import { getApplicationMeta } from '../../../../shared/utils/applicationColors';
import { bundles } from '@bundles-registry';

export function applyBundleTheme(route) {
  const rootPath = route.matched[0]?.path;
  const bundle = bundles.find(candidate => candidate.routes?.some(entry => entry.path === rootPath));
  const { primaryColor } = getApplicationMeta(bundle?.name);
  const color = primaryColor === 'surface' ? 'violet' : primaryColor;
  const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

  updatePreset({
    semantic: {
      primary: Object.fromEntries(shades.map(shade => [shade, `var(--color-${color}-${shade})`])),
    },
  });
}
