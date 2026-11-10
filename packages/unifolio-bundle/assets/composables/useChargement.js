import { onMounted, ref } from 'vue';
import { messageErreur } from '../requests/traceService';

/**
 * Schéma « chargement → erreur → contenu » commun aux écrans : état réactif + rechargement.
 *
 * @param {() => Promise<{data: any}>} charger  appel d'API ; sa réponse `data` alimente `donnees`
 * @param {object}   [options]
 * @param {string}   [options.messageParDefaut]  message affiché si l'API n'en donne pas
 * @param {(data: any) => void} [options.apres]  traitement de la réponse (remplace l'affectation à `donnees`)
 * @param {*}        [options.initial=null]      valeur de `donnees` avant la réponse (ex. [] pour une liste)
 * @param {boolean}  [options.immediat=true]     charger au montage
 */
export function useChargement(charger, { messageParDefaut = 'Chargement impossible.', apres = null, initial = null, immediat = true } = {}) {
  const donnees = ref(initial);
  const chargement = ref(true);
  const erreur = ref(null);

  const recharger = async () => {
    erreur.value = null;
    try {
      const { data } = await charger();
      if (apres) apres(data);
      else donnees.value = data;
    } catch (e) {
      erreur.value = messageErreur(e, messageParDefaut);
    } finally {
      chargement.value = false;
    }
  };

  if (immediat) onMounted(recharger);

  return { donnees, chargement, erreur, recharger };
}
