<script setup>
import { etapeParId } from '@/utils/finance';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { Card } from '@components';
import ActionsWorkflow from '../ActionsWorkflow.vue';

/** Étape 5 : facture (arrivée, rejet éventuel), paiement et clôture. */
defineProps({
    form: { type: Object, required: true },
    commande: { type: Object, required: true },
    modifiable: { type: Function, required: true },
    atteinte: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
});
const emit = defineEmits(['executer']);

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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label for="facture-arrivee" class="q-label">Date d'arrivée de la facture</label>
                    <input id="facture-arrivee" v-model="form.dateArriveeFacture" type="date" :disabled="!modifiable('dateArriveeFacture')" class="q-input" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="facture-paiement" class="q-label">Date du paiement</label>
                    <input id="facture-paiement" v-model="form.datePaiement" type="date" :disabled="!modifiable('datePaiement')" class="q-input" />
                </div>
                <label for="facture-finale" class="flex items-center gap-2 text-sm md:col-span-2">
                    <input id="facture-finale" v-model="form.factureFinale" type="checkbox" :disabled="!modifiable('factureFinale')" class="q-check" />
                    <span>Facture finale (commande soldée)</span>
                </label>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <ActionsWorkflow :actions="actions" :etape="5" :envoi="envoi" @executer="emit('executer', $event)" />
            </div>
        </Card>
    </div>
</template>
