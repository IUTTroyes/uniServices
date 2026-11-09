<script setup>
import { etapeParId, formatDate, formatMontant, nomPersonne, dateSaisie } from '@/utils/finance';
import { ref, watch } from 'vue';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import Button from 'primevue/button';
import { Card, EmptyState } from '@components';
import ActionsWorkflow from '../ActionsWorkflow.vue';

/**
 * Étape 4 : constatation du service fait (réceptions, totales ou partielles) puis certification.
 * Le service financier complète chaque réception (numéros MIGO, bon de livraison).
 */
const props = defineProps({
    commande: { type: Object, required: true },
    atteinte: { type: Boolean, default: false },
    gererReceptions: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
    receptionEnregistree: { type: [Number, String], default: null },
});
const emit = defineEmits(['executer', 'enregistrer-reception']);

// Copie modifiable des informations SIFAC de chaque réception (service financier).
const sifac = ref({});
const nouveauMigo = ref({});
watch(() => props.commande.receptions, (receptions) => {
    sifac.value = Object.fromEntries((receptions || []).map(r => [r.id, {
        numerosMigo: [...(r.numerosMigo || [])],
        dateMigo103: dateSaisie(r.dateMigo103),
        dateMigo105: dateSaisie(r.dateMigo105),
        blRattacheSifac: Boolean(r.blRattacheSifac),
    }]));
}, { immediate: true });

function ajouterMigo(id) {
    const numero = String(nouveauMigo.value[id] || '').trim();
    if (numero && !sifac.value[id].numerosMigo.includes(numero)) sifac.value[id].numerosMigo.push(numero);
    nouveauMigo.value[id] = '';
}

function enregistrer(id) {
    const s = sifac.value[id];
    emit('enregistrer-reception', id, {
        numerosMigo: s.numerosMigo,
        dateMigo103: s.dateMigo103 || null,
        dateMigo105: s.dateMigo105 || null,
        blRattacheSifac: s.blRattacheSifac,
    });
}

const etape = etapeParId(4);
</script>

<template>
    <div class="space-y-4">
        <Message v-if="!atteinte" severity="info" :closable="false">
            Cette étape s'ouvre une fois la commande validée.
        </Message>
        <Card :class="etape.bordure" title="Constatation du service fait" subtitle="Réception des biens ou des prestations" icon="pi pi-box" color="teal">
            <template #header-actions>
                <Tag v-if="commande.dateCertificationServiceFait" severity="success" :value="`Certifié le ${formatDate(commande.dateCertificationServiceFait)}`" />
            </template>
            <EmptyState v-if="!commande.receptions?.length" title="Aucune réception constatée" description="Le demandeur constate la réception une fois la commande livrée." icon="pi pi-box" compact />
            <ul v-else class="space-y-4">
                <li v-for="(r, index) in commande.receptions" :key="r.id" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold">Réception {{ index + 1 }} — {{ formatDate(r.dateEffectiveReception) }}</span>
                        <div class="flex gap-2">
                            <Tag :value="r.typeReception === 'PARTIELLE' ? 'Partielle' : 'Totale'" :severity="r.typeReception === 'PARTIELLE' ? 'warn' : 'info'" />
                            <Tag :value="r.prestationsConformes ? 'Conforme' : 'Non conforme'" :severity="r.prestationsConformes ? 'success' : 'danger'" />
                        </div>
                    </div>
                    <dl class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div><dt class="q-label">Constatée par</dt><dd>{{ nomPersonne(r.receptionnaire) }}<span v-if="r.qualiteReceptionnaire"> ({{ r.qualiteReceptionnaire }})</span></dd></div>
                        <div><dt class="q-label">Montant TTC constaté</dt><dd>{{ formatMontant(r.montantTtcConstate) }}</dd></div>
                        <div v-if="r.detailsReceptionPartielle"><dt class="q-label">Reçu</dt><dd class="whitespace-pre-line">{{ r.detailsReceptionPartielle }}</dd></div>
                    </dl>
                    <div v-if="sifac[r.id]" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="flex flex-col gap-1 sm:col-span-3">
                            <label :for="`migo-${r.id}`" class="q-label">Numéros MIGO (SIFAC)</label>
                            <div class="flex flex-wrap gap-2">
                                <Tag v-for="(m, i) in sifac[r.id].numerosMigo" :key="m" :value="m" severity="secondary">
                                    <span class="text-sm">{{ m }}</span>
                                    <button v-if="gererReceptions" type="button" class="ml-1" :aria-label="`Retirer le numéro ${m}`" @click="sifac[r.id].numerosMigo.splice(i, 1)"><i class="pi pi-times text-sm" /></button>
                                </Tag>
                                <span v-if="!sifac[r.id].numerosMigo.length" class="text-sm text-gray-500">Aucun numéro</span>
                            </div>
                            <div v-if="gererReceptions" class="flex gap-2 mt-1">
                                <input :id="`migo-${r.id}`" v-model="nouveauMigo[r.id]" type="text" maxlength="30" class="q-input" placeholder="Ex. : 5000412345" @keydown.enter.prevent="ajouterMigo(r.id)" />
                                <Button label="Ajouter" icon="pi pi-plus" size="small" severity="secondary" outlined @click="ajouterMigo(r.id)" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label :for="`migo103-${r.id}`" class="q-label">Date MIGO 103</label>
                            <input :id="`migo103-${r.id}`" v-model="sifac[r.id].dateMigo103" type="date" :disabled="!gererReceptions" class="q-input" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label :for="`migo105-${r.id}`" class="q-label">Date MIGO 105</label>
                            <input :id="`migo105-${r.id}`" v-model="sifac[r.id].dateMigo105" type="date" :disabled="!gererReceptions" class="q-input" />
                        </div>
                        <label :for="`bl-${r.id}`" class="flex items-center gap-2 text-sm self-end">
                            <input :id="`bl-${r.id}`" v-model="sifac[r.id].blRattacheSifac" type="checkbox" :disabled="!gererReceptions" class="q-check" />
                            <span>Bon de livraison rattaché dans SIFAC</span>
                        </label>
                        <div v-if="gererReceptions" class="sm:col-span-3 flex justify-end">
                            <Button label="Enregistrer cette réception" icon="pi pi-save" size="small" severity="secondary" :loading="receptionEnregistree === r.id" @click="enregistrer(r.id)" />
                        </div>
                    </div>
                </li>
            </ul>
            <dl v-if="commande.dateCertificationServiceFait" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="q-label">Certifié par</dt><dd>{{ nomPersonne(commande.certificateurServiceFait) }}</dd></div>
            </dl>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <ActionsWorkflow :actions="actions" :etape="4" :envoi="envoi" @executer="emit('executer', $event)" />
            </div>
        </Card>
    </div>
</template>
