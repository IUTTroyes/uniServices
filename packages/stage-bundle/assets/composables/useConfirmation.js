import { useConfirm } from 'primevue/useconfirm';

/**
 * Confirmation par la boîte de dialogue PrimeVue (ConfirmDialog global du shell), sous forme de promesse :
 *   if (await confirmer({ message: 'Supprimer ce critère ?', danger: true })) { … }
 * Remplace les confirm() natifs (anti-pattern du styleguide).
 */
export function useConfirmation() {
  const confirm = useConfirm();

  const confirmer = ({ message, titre = 'Confirmation', libelle = 'Confirmer', danger = false }) => new Promise((resolve) => {
    let repondu = false;
    const repondre = (valeur) => {
      if (!repondu) {
        repondu = true;
        resolve(valeur);
      }
    };
    confirm.require({
      message,
      header: titre,
      icon: danger ? 'pi pi-exclamation-triangle' : 'pi pi-question-circle',
      rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
      acceptProps: { label: libelle, severity: danger ? 'danger' : 'primary' },
      accept: () => repondre(true),
      reject: () => repondre(false),
      onHide: () => repondre(false)
    });
  });

  return { confirmer };
}
