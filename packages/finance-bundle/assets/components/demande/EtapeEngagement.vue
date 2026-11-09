<script setup>
import { etapeParId, formatDate, nomPersonne } from '@/utils/finance';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { Card } from '@components';
import ActionsWorkflow from '../ActionsWorkflow.vue';

/** Étape 3 : visa d'engagement budgétaire puis transmission du bon de commande au fournisseur. */
defineProps({
    form: { type: Object, required: true },
    commande: { type: Object, required: true },
    modifiable: { type: Function, required: true },
    atteinte: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
});
const emit = defineEmits(['executer']);

const etape = etapeParId(3);
</script>

<template>
    <div class="space-y-4">
        <Message v-if="!atteinte" severity="info" :closable="false">
            Cette étape s'ouvre après la vérification du service financier.
        </Message>
        <Card :class="etape.bordure" title="Visa d'engagement et commande" subtitle="Autorisation de passer la commande au fournisseur" icon="pi pi-check-circle" color="purple">
            <template #header-actions>
                <Tag v-if="commande.bcTransmis" severity="success" value="BC transmis au fournisseur" />
                <Tag v-else-if="commande.dateVisaEngagement" severity="info" :value="`Visa accordé le ${formatDate(commande.dateVisaEngagement)}`" />
            </template>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div><dt class="q-label">Date du visa</dt><dd>{{ formatDate(commande.dateVisaEngagement) }}</dd></div>
                <div><dt class="q-label">Visa accordé par</dt><dd>{{ nomPersonne(commande.signataireVisaEngagement) }}</dd></div>
                <div><dt class="q-label">N° de bon de commande</dt><dd>{{ commande.numeroBonCommande || '—' }}</dd></div>
            </dl>
            <div class="mt-4 flex flex-col gap-1 max-w-xs">
                <label for="engagement-date-bc" class="q-label">Date du bon de commande</label>
                <input id="engagement-date-bc" v-model="form.dateBonCommande" type="date" :disabled="!modifiable('dateBonCommande')" class="q-input" />
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <ActionsWorkflow :actions="actions" :etape="3" :envoi="envoi" @executer="emit('executer', $event)" />
            </div>
        </Card>
    </div>
</template>
