import { describe, it, expect, vi, beforeEach } from 'vitest';

const require = vi.fn();
vi.mock('primevue/useconfirm', () => ({ useConfirm: () => ({ require }) }));

import { useConfirmation } from './useConfirmation';

describe('useConfirmation', () => {
  beforeEach(() => require.mockReset());

  it('suppression : action principale en rouge, annulation discrète', () => {
    useConfirmation().confirmer({ header: 'Supprimer', message: 'Sûr ?', icon: 'pi pi-trash', libelle: 'Supprimer', danger: true });
    const appel = require.mock.lastCall[0];
    expect(appel).toMatchObject({
      header: 'Supprimer', message: 'Sûr ?', icon: 'pi pi-trash',
      acceptProps: { label: 'Supprimer', severity: 'danger' },
      rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
    });
  });

  it('confirmation simple : valeurs par défaut et libellé de refus personnalisé', () => {
    useConfirmation().confirmer({ titre: 'Modifications', message: 'Enregistrer ?', libelle: 'Enregistrer', libelleRefus: 'Ignorer' });
    const appel = require.mock.lastCall[0];
    expect(appel.header).toBe('Modifications');
    expect(appel.icon).toBe('pi pi-question-circle');
    expect(appel.acceptProps).toEqual({ label: 'Enregistrer' });
    expect(appel.rejectProps.label).toBe('Ignorer');
  });

  it('promesse vraie et rappel accept à la confirmation', async () => {
    const accept = vi.fn();
    const promesse = useConfirmation().confirmer({ message: 'Ok ?', accept });
    require.mock.lastCall[0].accept();
    await expect(promesse).resolves.toBe(true);
    expect(accept).toHaveBeenCalledOnce();
  });

  it('promesse fausse au refus ou à la fermeture, une seule réponse', async () => {
    const reject = vi.fn();
    const promesse = useConfirmation().confirmer({ message: 'Ok ?', reject });
    const appel = require.mock.lastCall[0];
    appel.onHide();
    appel.reject();
    await expect(promesse).resolves.toBe(false);
    expect(reject).toHaveBeenCalledOnce();
  });
});
