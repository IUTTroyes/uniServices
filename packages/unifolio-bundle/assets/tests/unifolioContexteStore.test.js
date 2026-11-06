import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('../requests/unifolioService', () => ({
  default: { getContexte: vi.fn() },
}));

import unifolioService from '../requests/unifolioService';
import { useUnifolioContexteStore } from '../stores/unifolioContexteStore';

describe('unifolioContexteStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('charge les capacités une seule fois', async () => {
    unifolioService.getContexte.mockResolvedValue({
      data: { capacites: { acces: true, etudiant: true }, scolarite: { diplome: 'BUT MMI' } },
    });
    const store = useUnifolioContexteStore();

    await store.charger();
    await store.charger();

    expect(unifolioService.getContexte).toHaveBeenCalledTimes(1);
    expect(store.peut('etudiant')).toBe(true);
    expect(store.peut('referent')).toBe(false);
    expect(store.scolarite.diplome).toBe('BUT MMI');
  });

  it('retire toutes les capacités en cas d\'erreur', async () => {
    unifolioService.getContexte.mockRejectedValue(new Error('500'));
    const store = useUnifolioContexteStore();

    await store.charger();

    expect(store.peut('acces')).toBe(false);
    expect(store.error).toBeTruthy();
    expect(store.isLoaded).toBe(false);
  });
});
