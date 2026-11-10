import { useConfirm } from 'primevue/useconfirm';

/**
 * Boîte de confirmation standard (ConfirmDialog global du shell), à la place des confirm() natifs :
 * mêmes boutons partout (« Annuler » discret, action principale en rouge pour une suppression ou une opération
 * irréversible).
 *
 * Deux façons de l'utiliser :
 * @example
 * // Promesse : vrai si la personne confirme
 * if (await confirmer({ titre: 'Supprimer', message: 'Supprimer cet élément ?', libelle: 'Supprimer', danger: true })) { … }
 * // Rappels
 * confirmer({ header: 'Supprimer', message: 'Supprimer cet élément ?', icon: 'pi pi-trash', danger: true, accept: async () => { … } });
 */
export function useConfirmation() {
  const confirm = useConfirm();

  /** @returns {Promise<boolean>} */
  const confirmer = ({
    header,
    titre,
    message,
    icon,
    libelle = 'Confirmer',
    danger = false,
    libelleRefus = 'Annuler',
    accept,
    reject,
  }) => new Promise((resolve) => {
    let repondu = false;
    const repondre = (oui) => {
      if (repondu) return;
      repondu = true;
      resolve(oui);
      (oui ? accept : reject)?.();
    };
    confirm.require({
      header: header ?? titre ?? 'Confirmation',
      message,
      icon: icon ?? (danger ? 'pi pi-exclamation-triangle' : 'pi pi-question-circle'),
      acceptProps: danger ? { label: libelle, severity: 'danger' } : { label: libelle },
      rejectProps: { label: libelleRefus, severity: 'secondary', outlined: true },
      accept: () => repondre(true),
      reject: () => repondre(false),
      // Fermeture par la croix ou Échap : refus.
      onHide: () => repondre(false),
    });
  });

  return { confirmer };
}
