import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));

import { cheminRouteur, useNotificationStore } from './notificationStore';

beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
});

describe('cheminRouteur', () => {
  it('retire le préfixe /app du routeur', () => {
    expect(cheminRouteur('/app/unifolio/traces/x')).toBe('/unifolio/traces/x');
    expect(cheminRouteur('/app')).toBe('/');
    expect(cheminRouteur('/unifolio/evaluations')).toBe('/unifolio/evaluations');
  });

  it('refuse tout ce qui n\'est pas un chemin interne', () => {
    for (const url of ['https://exemple.org', '//exemple.org', 'javascript:alert(1)', '', null, undefined, 42]) {
      expect(cheminRouteur(url)).toBeNull();
    }
  });
});

describe('useNotificationStore', () => {
  it('compte, charge et met à jour le compteur', async () => {
    const store = useNotificationStore();
    api.get.mockResolvedValueOnce({ data: { nonLues: 3 } });
    await store.compter();
    expect(store.nonLues).toBe(3);

    api.get.mockResolvedValueOnce({ data: { notifications: [{ uuid: 'a', lue: false }], nonLues: 1 } });
    await store.charger();
    expect(api.get).toHaveBeenLastCalledWith('/api/notifications', { params: { limite: 20 } });
    expect(store.items).toHaveLength(1);
    expect(store.nonLues).toBe(1);
    expect(store.chargement).toBe(false);
  });

  it('un compteur en échec ne casse pas la cloche', async () => {
    const store = useNotificationStore();
    api.get.mockRejectedValueOnce(new Error('réseau'));
    await store.compter();
    expect(store.nonLues).toBe(0);
    api.get.mockRejectedValueOnce(new Error('réseau'));
    await store.charger();
    expect(store.erreur).toContain('Impossible');
  });

  it('marque lue (optimiste) puis annule en cas d\'échec', async () => {
    const store = useNotificationStore();
    const n = { uuid: 'a', lue: false };
    api.post.mockResolvedValueOnce({ data: { nonLues: 4 } });
    await store.marquerLue(n);
    expect(n.lue).toBe(true);
    expect(store.nonLues).toBe(4);
    expect(api.post).toHaveBeenCalledWith('/api/notifications/a/lue');

    const m = { uuid: 'b', lue: false };
    api.post.mockRejectedValueOnce(new Error('réseau'));
    await store.marquerLue(m);
    expect(m.lue).toBe(false);

    api.post.mockClear();
    await store.marquerLue({ uuid: 'c', lue: true });
    expect(api.post).not.toHaveBeenCalled();
  });

  it('tout marquer comme lu', async () => {
    const store = useNotificationStore();
    store.items = [{ uuid: 'a', lue: false }, { uuid: 'b', lue: false }];
    store.nonLues = 2;
    api.post.mockResolvedValueOnce({ data: { nonLues: 0 } });
    await store.toutLire();
    expect(store.items.every((n) => n.lue)).toBe(true);
    expect(store.nonLues).toBe(0);
  });
});
