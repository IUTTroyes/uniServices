import { updatePreset } from '@primeuix/themes';
import { getApplicationMeta } from '../../../../shared/utils/applicationColors';
import { bundles } from '../bundles-registry';

const favicons = import.meta.glob('../../../../shared/images/logo/uniservice/*.svg', { eager: true, query: '?url', import: 'default' });

function applyFavicon(brand) {
  const href = favicons[`../../../../shared/images/logo/uniservice/uni-${brand}.svg`];
  const link = document.querySelector('link[rel="icon"]');
  if (link && href) link.setAttribute('href', href);
}

export function applyBundleTheme(route) {
  const rootPath = route.matched[0]?.path;
  const bundle = bundles.find(candidate => candidate.routes?.some(entry => entry.path === rootPath));
  const { primaryColor, brand } = getApplicationMeta(bundle?.name);
  const color = primaryColor === 'surface' ? 'uni-service' : primaryColor;
  applyFavicon(brand ?? 'service');
  const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

  updatePreset({
    semantic: {
      primary: Object.fromEntries(shades.map(shade => [shade, `var(--color-${color}-${shade})`])),
    },
  });
}
