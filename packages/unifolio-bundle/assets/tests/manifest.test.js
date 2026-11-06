import { describe, it, expect, vi } from 'vitest';
import manifest from '../manifest';
import { unifolioSections } from '../navigation';

vi.mock('@components/components/layout/AppLayout.vue', () => ({ default: { name: 'AppLayout' } }));
vi.mock('@images/logo/logo_intranet_iut_troyes.svg', () => ({ default: 'logo.svg' }));
vi.mock('@components', () => ({ registerWidgetComponent: vi.fn() }));

describe('unifolio manifest', () => {
  const racine = manifest.routes.find((r) => r.path === '/unifolio');

  it('garde le nom et la couleur du bundle', () => {
    expect(manifest.name).toBe('unifolio');
    expect(manifest.primaryColor).toBe('emerald');
    expect(typeof manifest.registerWidgets).toBe('function');
  });

  it('déclare une route par section, protégée par la même permission que le menu', () => {
    const items = unifolioSections.flatMap((s) => s.items);
    expect(racine).toBeDefined();
    expect(racine.children.map((c) => c.path)).toEqual(['', ...items.map((i) => i.path)]);

    for (const item of items) {
      const route = racine.children.find((c) => c.path === item.path);
      const menu = manifest.menu.items.find((m) => m.to === item.to);
      expect(route.meta.permission).toEqual(item.permission);
      expect(menu.permission).toEqual(item.permission);
      expect(item.to).toBe(`/unifolio/${item.path}`);
    }
  });

  it('réserve les sections aux bons profils', () => {
    const byPath = Object.fromEntries(unifolioSections.flatMap((s) => s.items).map((i) => [i.path, i]));
    expect(byPath.traces.permission).toBe('isEtudiant');
    expect(byPath.traces.capacite).toBe('etudiant');
    expect(byPath['admin/grilles'].permission).toBe('isReferentPortfolio');
    expect(byPath.evaluations.capacite).toBe('correcteur');
    expect(byPath.moderation.capacite).toBe('moderateur');
  });

  it('a des noms de routes uniques', () => {
    const names = racine.children.map((c) => c.name);
    expect(new Set(names).size).toBe(names.length);
  });
});
