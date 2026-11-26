import { createApp, h } from 'vue';
import UniLogo from './UniLogo.vue';
import { UNI_BRAND } from './uniBrand.js';
import tools from '../../../global-data/tools.generated.json';
import colors from '../../../global-data/application-colors.json';

const rendre = (props) => {
  const cible = document.createElement('div');
  const app = createApp({ render: () => h(UniLogo, props) });
  app.mount(cible);
  const svg = cible.querySelector('svg');
  app.unmount();
  return svg;
};

describe('identité UniService', () => {
  it.each(Object.keys(UNI_BRAND))('donne un nom accessible au logo %s', (service) => {
    for (const variant of ['lettre', 'icone']) {
      const svg = rendre({ service, variant });
      expect(svg.getAttribute('role')).toBe('img');
      expect(svg.getAttribute('aria-label')).toBe(UNI_BRAND[service].name);
    }
  });

  it('masque un logo décoratif aux lecteurs d’écran', () => {
    const svg = rendre({ service: 'stages', decorative: true });
    expect(svg.getAttribute('aria-hidden')).toBe('true');
    expect(svg.hasAttribute('role')).toBe(false);
  });

  it('relie chaque application identifiée à une marque et à sa couleur', () => {
    const identifiees = tools.filter((outil) => outil.brand);
    expect(identifiees).toHaveLength(Object.keys(UNI_BRAND).length);
    for (const outil of identifiees) {
      expect(UNI_BRAND[outil.brand].name).toBe(outil.name);
      expect(outil.primaryColor).toBe(`uni-${outil.brand}`);
      expect(colors).toContain(outil.primaryColor);
    }
  });
});
