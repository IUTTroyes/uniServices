import { createApp, h } from 'vue';
import UniLogo from './UniLogo.vue';
import { UNI_BRAND, faviconUrl } from './uniBrand.js';

const rendre = (props) => {
  const cible = document.createElement('div');
  const app = createApp({ render: () => h(UniLogo, props) });
  app.mount(cible);
  const logo = cible.querySelector('.uni-logo');
  app.unmount();
  return logo;
};

describe('logo UniService', () => {
  it.each(Object.keys(UNI_BRAND))('donne un nom accessible au logo %s', (service) => {
    for (const variant of ['lettre', 'icone']) {
      const logo = rendre({ service, variant });
      expect(logo.getAttribute('role')).toBe('img');
      expect(logo.getAttribute('aria-label')).toBe(UNI_BRAND[service].name);
    }
  });

  it('écrit le nom de la configuration, le « U » dessiné remplaçant la capitale', () => {
    const logo = rendre({ service: 'ticket', tagline: true });
    expect(logo.querySelector('.uni-logo__word').textContent).toBe('niTicket');
    expect(logo.querySelector('.uni-logo__tagline').textContent).toBe(UNI_BRAND.ticket.tagline);
    expect(logo.getAttribute('aria-label')).toBe(`UniTicket – ${UNI_BRAND.ticket.tagline}`);
  });

  it('masque un logo décoratif aux lecteurs d’écran', () => {
    const logo = rendre({ service: 'stages', decorative: true });
    expect(logo.getAttribute('aria-hidden')).toBe('true');
    expect(logo.hasAttribute('role')).toBe(false);
  });

  it('dessine un favicon propre à chaque application', () => {
    const favicons = Object.keys(UNI_BRAND).map(faviconUrl);
    expect(new Set(favicons).size).toBe(favicons.length);
    expect(decodeURIComponent(faviconUrl('docs'))).toContain(UNI_BRAND.docs.color);
  });
});
