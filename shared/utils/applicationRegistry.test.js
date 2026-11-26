import fs from 'node:fs';
import path from 'node:path';
import tools from '../global-data/tools.generated.json';
import colors from '../global-data/application-colors.json';
import { getApplicationMeta, normalizeAppSlug } from './applicationColors';

const racine = path.resolve(__dirname, '../..');
const lire = (fichier) => JSON.parse(fs.readFileSync(fichier, 'utf8'));
const metas = [
  ...fs.readdirSync(path.join(racine, 'packages')).map((d) => path.join(racine, 'packages', d, 'bundle.meta.json')),
  ...fs.readdirSync(path.join(racine, 'shared/global-data/external-tools'))
    .map((f) => path.join(racine, 'shared/global-data/external-tools', f)),
].filter((f) => fs.existsSync(f)).map(lire);
const css = fs.readFileSync(path.join(racine, 'shared/styles/uni-brand.css'), 'utf8');
const nuance = (cle, n) => css.match(new RegExp(`--color-uni-${cle}-${n}: (#[0-9a-f]{6});`))?.[1];

const luminance = (hex) => {
  const [r, v, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * v + 0.0722 * b;
};
const contraste = (a, b) => {
  const [clair, sombre] = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (clair + 0.05) / (sombre + 0.05);
};

describe('registre des applications (lancer « pnpm registry » si ce test échoue)', () => {
  it('reflète chaque bundle.meta.json', () => {
    expect(tools).toHaveLength(metas.length);
    for (const meta of metas) {
      const outil = tools.find((t) => t.urlSlug === meta.urlSlug);
      expect(outil).toMatchObject({ name: meta.name, description: meta.description, icon: meta.icon });
      expect(outil.aliases).toEqual(meta.aliases ?? []);
      if (meta.brand) {
        expect(outil.brand).toMatchObject(meta.brand);
        expect(outil.primaryColor).toBe(`uni-${meta.brand.key}`);
        expect(colors).toContain(outil.primaryColor);
        expect(nuance(meta.brand.key, 500)).toBe(meta.brand.color.toLowerCase());
      }
    }
  });

  it('n’attribue chaque identifiant et alias qu’à une application', () => {
    const codes = metas.flatMap((m) => [m.urlSlug, ...(m.aliases ?? [])]);
    expect(new Set(codes).size).toBe(codes.length);
  });

  it.each([['portfolio', 'unifolio'], ['document', 'documents'], ['stages', 'stage'], ['Core', 'auth']])(
    'rattache le code « %s » du serveur à %s',
    (code, slug) => {
      expect(normalizeAppSlug(code)).toBe(slug);
      expect(getApplicationMeta(code).brand).toBeTruthy();
    },
  );

  it.each(tools.filter((t) => t.brand).map((t) => [t.name, t.brand.key]))(
    'garde %s lisible (contraste WCAG AA)',
    (_, cle) => {
      // Texte blanc sur la couleur dominante, badge clair, primaire du mode sombre.
      expect(contraste('#ffffff', nuance(cle, 500))).toBeGreaterThanOrEqual(4.5);
      expect(contraste(nuance(cle, 800), nuance(cle, 100))).toBeGreaterThanOrEqual(4.5);
      expect(contraste(nuance(cle, 600), '#ffffff')).toBeGreaterThanOrEqual(4.5);
      expect(contraste(nuance(cle, 300), nuance(cle, 950))).toBeGreaterThanOrEqual(4.5);
      expect(contraste(nuance(cle, 400), '#18181b')).toBeGreaterThanOrEqual(4.5);
    },
  );
});
