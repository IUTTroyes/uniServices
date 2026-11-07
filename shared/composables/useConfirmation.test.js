import { describe, it, expect, vi } from 'vitest';

const require = vi.fn();
vi.mock('primevue/useconfirm', () => ({ useConfirm: () => ({ require }) }));

import { useConfirmation } from './useConfirmation';

describe('useConfirmation', () => {
  it('suppression : action principale en rouge, annulation discrète', () => {
    const accept = vi.fn();
    useConfirmation().confirmer({ header: 'Supprimer', message: 'Sûr ?', icon: 'pi pi-trash', libelle: 'Supprimer', danger: true, accept });
    expect(require).toHaveBeenLastCalledWith({
      header: 'Supprimer', message: 'Sûr ?', icon: 'pi pi-trash',
      acceptProps: { label: 'Supprimer', severity: 'danger' },
      rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
      accept, reject: undefined,
    });
  });

  it('confirmation simple : valeurs par défaut et libellé de refus personnalisé', () => {
    const reject = vi.fn();
    useConfirmation().confirmer({ header: 'Modifications', message: 'Enregistrer ?', libelle: 'Enregistrer', libelleRefus: 'Ignorer', accept: vi.fn(), reject });
    const appel = require.mock.lastCall[0];
    expect(appel.icon).toBe('pi pi-exclamation-triangle');
    expect(appel.acceptProps).toEqual({ label: 'Enregistrer' });
    expect(appel.rejectProps.label).toBe('Ignorer');
    expect(appel.reject).toBe(reject);
  });
});
