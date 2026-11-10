<script setup>
import { etapeParId, formatDate } from '@/utils/finance';
import { computed, ref, watch } from 'vue';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import Button from 'primevue/button';
import { Card } from '@components';
import ActionsWorkflow from '../ActionsWorkflow.vue';

/**
 * Étape 5 : facture (arrivée, rejet éventuel), paiement et clôture.
 * L'arrivée de la facture et le paiement se saisissent ici même (date + bouton) : ils font avancer le circuit.
 * Ensuite, le service financier peut corriger ces dates dans les champs (enregistrés avec la demande).
 */
const props = defineProps({
    form: { type: Object, required: true },
    commande: { type: Object, required: true },
    modifiable: { type: Function, required: true },
    atteinte: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
});
const emit = defineEmits(['executer', 'envoyer']);

const aujourdhui = () => new Date().toISOString().slice(0, 10);
const actionFacture = computed(() => props.actions.find(a => a.action === 'recevoir_facture'));
const actionPaiement = computed(() => props.actions.find(a => a.action === 'payer'));
/** Les autres actions de l'étape (rejet de facture, clôture…) restent des boutons. */
const autresActions = computed(() => props.actions.filter(a => !['recevoir_facture', 'payer'].includes(a.action)));

const dateFacture = ref(aujourdhui());
const datePaiement = ref(aujourdhui());
const factureFinale = ref(true);
watch(() => props.commande.statut, () => {
    dateFacture.value = aujourdhui();
    datePaiement.value = aujourdhui();
    factureFinale.value = true;
});

const dateValide = (d) => /^\d{4}-\d{2}-\d{2}$/.test(d || '') && d <= aujourdhui();

function enregistrerFacture() {
    if (dateValide(dateFacture.value)) emit('envoyer', actionFacture.value, { date: dateFacture.value });
}
function enregistrerPaiement() {
    if (dateValide(datePaiement.value)) emit('envoyer', actionPaiement.value, { date: datePaiement.value, factureFinale: factureFinale.value });
}

const etape = etapeParId(5);
</script>

<template>
    <div class="space-y-4">
        <Message v-if="!atteinte" severity="info" :closable="false">
            Cette étape s'ouvre une fois le service fait certifié.
        </Message>
        <Message v-if="commande.rejetFactureMotif" severity="warn" :closable="false">
            Facture rejetée : {{ commande.rejetFactureMotif }}
        </Message>
        <Card :class="etape.bordure" title="Facture et paiement" icon="pi pi-wallet" color="pink">
            <template #header-actions>
                <Tag v-if="commande.statut === 'CLOTURE'" severity="secondary" value="Clôturée" />
                <Tag v-else-if="commande.datePaiement" severity="success" :value="commande.factureFinale ? 'Payée, soldée' : 'Payée (facture partielle)'" />
            </template>

            <!-- Étape en cours : saisie de la date qui fait avancer le circuit -->
            <form v-if="actionFacture" class="rounded-lg border-2 border-pink-300 dark:border-pink-800 bg-pink-50/60 dark:bg-pink-950/30 p-4 flex flex-wrap items-end gap-3" @submit.prevent="enregistrerFacture">
                <div class="flex flex-col gap-1">
                    <label for="facture-arrivee-saisie" class="q-label q-label-required">Date d'arrivée de la facture</label>
                    <input id="facture-arrivee-saisie" v-model="dateFacture" type="date" :max="aujourdhui()" required class="q-input" />
                </div>
                <Button type="submit" label="Enregistrer l'arrivée de la facture" icon="pi pi-inbox" :disabled="!dateValide(dateFacture)" :loading="envoi === 'recevoir_facture'" />
            </form>
            <form v-else-if="actionPaiement" class="rounded-lg border-2 border-pink-300 dark:border-pink-800 bg-pink-50/60 dark:bg-pink-950/30 p-4 flex flex-wrap items-end gap-3" @submit.prevent="enregistrerPaiement">
                <div class="flex flex-col gap-1">
                    <label for="facture-paiement-saisie" class="q-label q-label-required">Date du paiement</label>
                    <input id="facture-paiement-saisie" v-model="datePaiement" type="date" :max="aujourdhui()" required class="q-input" />
                </div>
                <label for="facture-finale-saisie" class="flex items-center gap-2 text-sm pb-2">
                    <input id="facture-finale-saisie" v-model="factureFinale" type="checkbox" class="q-check" />
                    <span>Facture finale (commande soldée)</span>
                </label>
                <Button type="submit" label="Enregistrer le paiement" icon="pi pi-check" :disabled="!dateValide(datePaiement)" :loading="envoi === 'payer'" />
            </form>

            <!-- Dates enregistrées : corrigeables par le service financier, sinon en lecture -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" :class="{ 'mt-4': actionFacture || actionPaiement }">
                <div class="flex flex-col gap-1">
                    <label for="facture-arrivee" class="q-label">Date d'arrivée de la facture</label>
                    <input v-if="modifiable('dateArriveeFacture')" id="facture-arrivee" v-model="form.dateArriveeFacture" type="date" class="q-input" />
                    <span v-else id="facture-arrivee" class="text-sm">{{ commande.dateArriveeFacture ? formatDate(commande.dateArriveeFacture) : 'Non reçue' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="facture-paiement" class="q-label">Date du paiement</label>
                    <input v-if="modifiable('datePaiement')" id="facture-paiement" v-model="form.datePaiement" type="date" class="q-input" />
                    <span v-else id="facture-paiement" class="text-sm">{{ commande.datePaiement ? formatDate(commande.datePaiement) : 'Non payée' }}</span>
                </div>
                <label v-if="modifiable('factureFinale')" for="facture-finale" class="flex items-center gap-2 text-sm md:col-span-2">
                    <input id="facture-finale" v-model="form.factureFinale" type="checkbox" class="q-check" />
                    <span>Facture finale (commande soldée)</span>
                </label>
                <p v-if="modifiable('dateArriveeFacture')" class="q-hint md:col-span-2">Correction des dates : enregistrez avec le bouton « Enregistrer » en haut de la page.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <ActionsWorkflow :actions="autresActions" :etape="5" :envoi="envoi" @executer="emit('executer', $event)" />
            </div>
        </Card>
    </div>
</template>
