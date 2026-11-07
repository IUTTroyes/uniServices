import { useConfirm } from 'primevue/useconfirm';

/**
 * Boîte de confirmation standard : mêmes boutons partout (« Annuler » discret, action principale
 * éventuellement en rouge pour une suppression ou une opération irréversible).
 *
 * @example
 * const { confirmer } = useConfirmation();
 * confirmer({ header: 'Supprimer', message: 'Supprimer cet élément ?', icon: 'pi pi-trash', libelle: 'Supprimer', danger: true, accept: async () => { … } });
 */
export function useConfirmation() {
  const confirm = useConfirm();

  const confirmer = ({
    header,
    message,
    icon = 'pi pi-exclamation-triangle',
    libelle = 'Confirmer',
    danger = false,
    libelleRefus = 'Annuler',
    accept,
    reject,
  }) => confirm.require({
    header,
    message,
    icon,
    acceptProps: danger ? { label: libelle, severity: 'danger' } : { label: libelle },
    rejectProps: { label: libelleRefus, severity: 'secondary', outlined: true },
    accept,
    reject,
  });

  return { confirmer };
}
