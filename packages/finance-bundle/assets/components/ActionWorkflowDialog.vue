<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

/**
 * Saisie complémentaire d'une action du circuit avant son envoi :
 *  - commentaire (motif d'avis défavorable, demande de précisions, rejet…) ;
 *  - constatation du service fait (type, date, montant, conformité, bons de livraison en PDF) ;
 *  - date de facture ou de paiement.
 * Émet `valider` avec les données à envoyer au serveur.
 */
const props = defineProps({
    /** { action, libelle, commentaire } tel que fourni par le serveur, ou null. */
    action: { type: Object, default: null },
    montantTtc: { type: [String, Number], default: null },
    envoi: { type: Boolean, default: false },
});
const emit = defineEmits(['valider']);
const visible = defineModel('visible', { type: Boolean, default: false });

const aujourdhui = () => new Date().toISOString().slice(0, 10);
const donnees = ref({});
const bonsLivraison = ref([]);
const erreurFichier = ref('');
const TAILLE_MAX = 10 * 1024 * 1024;

function ajouterFichiers(event) {
    erreurFichier.value = '';
    for (const f of event.target.files || []) {
        if (f.type && f.type !== 'application/pdf') {
            erreurFichier.value = `« ${f.name} » n'est pas un PDF.`;
        } else if (f.size > TAILLE_MAX) {
            erreurFichier.value = `« ${f.name} » dépasse 10 Mo.`;
        } else if (!bonsLivraison.value.some(b => b.name === f.name && b.size === f.size)) {
            bonsLivraison.value.push(f);
        }
    }
    event.target.value = '';
}

const avecDate = computed(() => ['recevoir_facture', 'payer'].includes(props.action?.action));
const constatation = computed(() => props.action?.action === 'constater_service_fait');
const libelleDate = computed(() => (props.action?.action === 'payer' ? 'Date du paiement' : 'Date d\'arrivée de la facture'));
const valide = computed(() => {
    if (props.action?.commentaire && !String(donnees.value.commentaire || '').trim()) return false;
    if (constatation.value && donnees.value.typeReception === 'PARTIELLE' && !String(donnees.value.detailsReceptionPartielle || '').trim()) return false;
    if (constatation.value && !bonsLivraison.value.length) return false;
    return true;
});

watch(visible, (ouvert) => {
    if (!ouvert) return;
    bonsLivraison.value = [];
    erreurFichier.value = '';
    donnees.value = constatation.value
        ? { typeReception: 'TOTALE', dateEffectiveReception: aujourdhui(), montantTtcConstate: props.montantTtc, prestationsConformes: true, detailsReceptionPartielle: '', qualiteReceptionnaire: '' }
        : { commentaire: '', date: aujourdhui(), factureFinale: true };
}, { immediate: true });

function valider() {
    if (!valide.value) return;
    const d = donnees.value;
    if (constatation.value) {
        emit('valider', { ...d, bonsLivraison: [...bonsLivraison.value] });
    } else {
        emit('valider', {
            ...(props.action?.commentaire ? { commentaire: d.commentaire.trim() } : {}),
            ...(avecDate.value ? { date: d.date } : {}),
            ...(props.action?.action === 'payer' ? { factureFinale: d.factureFinale } : {}),
        });
    }
}
</script>

<template>
    <Dialog v-model:visible="visible" :header="action?.libelle || 'Action'" modal class="w-full max-w-xl">
        <form id="form-action-workflow" class="q-form" @submit.prevent="valider">
            <template v-if="constatation">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="sf-type" class="q-label q-label-required">Réception</label>
                        <select id="sf-type" v-model="donnees.typeReception" class="q-input">
                            <option value="TOTALE">Totalité de la commande</option>
                            <option value="PARTIELLE">Partie des biens / prestations</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="sf-date" class="q-label q-label-required">Date de réception</label>
                        <input id="sf-date" v-model="donnees.dateEffectiveReception" type="date" :max="aujourdhui()" required class="q-input" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="sf-montant" class="q-label">Montant TTC constaté (€)</label>
                        <input id="sf-montant" v-model="donnees.montantTtcConstate" type="number" min="0" step="0.01" class="q-input" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="sf-qualite" class="q-label">Votre qualité</label>
                        <input id="sf-qualite" v-model="donnees.qualiteReceptionnaire" type="text" maxlength="100" placeholder="Ex. : enseignant, technicien" class="q-input" />
                    </div>
                    <div v-if="donnees.typeReception === 'PARTIELLE'" class="flex flex-col gap-1 sm:col-span-2">
                        <label for="sf-details" class="q-label q-label-required">Ce qui a été reçu</label>
                        <textarea id="sf-details" v-model="donnees.detailsReceptionPartielle" rows="3" class="q-input" />
                    </div>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2" for="sf-conforme">
                        <input id="sf-conforme" v-model="donnees.prestationsConformes" type="checkbox" class="q-check" />
                        <span>Biens ou prestations livrés conformes à la commande</span>
                    </label>
                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <span class="q-label q-label-required">Bon(s) de livraison (PDF)</span>
                        <ul v-if="bonsLivraison.length" class="flex flex-col gap-1" aria-label="Bons de livraison sélectionnés">
                            <li v-for="(f, i) in bonsLivraison" :key="f.name + f.size" class="flex items-center justify-between gap-2 rounded border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm">
                                <span class="flex items-center gap-2 min-w-0"><i class="pi pi-file-pdf text-red-500" aria-hidden="true" /><span class="truncate">{{ f.name }}</span></span>
                                <button type="button" class="text-gray-500 hover:text-red-600" :aria-label="`Retirer ${f.name}`" @click="bonsLivraison.splice(i, 1)"><i class="pi pi-times" /></button>
                            </li>
                        </ul>
                        <label for="sf-bl" class="self-start inline-flex items-center gap-2 cursor-pointer rounded border border-dashed border-gray-300 dark:border-gray-600 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                            <i class="pi pi-upload" aria-hidden="true" />
                            {{ bonsLivraison.length ? 'Ajouter un autre PDF' : 'Choisir le ou les PDF' }}
                        </label>
                        <input id="sf-bl" type="file" accept="application/pdf" multiple class="sr-only" @change="ajouterFichiers" />
                        <small v-if="erreurFichier" class="text-red-600">{{ erreurFichier }}</small>
                        <small v-else class="q-hint">Un ou plusieurs PDF, 10 Mo au plus chacun. Le service financier visera ensuite la réception.</small>
                    </div>
                </div>
            </template>
            <template v-else>
                <div v-if="avecDate" class="flex flex-col gap-1">
                    <label for="action-date" class="q-label q-label-required">{{ libelleDate }}</label>
                    <input id="action-date" v-model="donnees.date" type="date" required class="q-input" />
                </div>
                <label v-if="action?.action === 'payer'" class="flex items-center gap-2 text-sm" for="action-finale">
                    <input id="action-finale" v-model="donnees.factureFinale" type="checkbox" class="q-check" />
                    <span>Facture finale (commande soldée)</span>
                </label>
                <div v-if="action?.commentaire" class="flex flex-col gap-1">
                    <label for="action-commentaire" class="q-label q-label-required">Motif / commentaire</label>
                    <textarea id="action-commentaire" v-model="donnees.commentaire" rows="4" required class="q-input" placeholder="Visible par le demandeur" />
                </div>
            </template>
        </form>
        <template #footer>
            <Button label="Annuler" severity="secondary" outlined @click="visible = false" />
            <Button type="submit" form="form-action-workflow" :label="action?.libelle || 'Valider'" :severity="action?.severite === 'danger' ? 'danger' : 'primary'" :disabled="!valide" :loading="envoi" />
        </template>
    </Dialog>
</template>
