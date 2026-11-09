<script setup>
import { etapeParId, formatDate, nomPersonne } from '@/utils/finance';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { Card } from '@components';
import FournisseurChamp from '../FournisseurChamp.vue';
import ActionsWorkflow from '../ActionsWorkflow.vue';

/** Étape 2 : vérification par le service financier (fournisseur du référentiel, n° SIFAC, contrôles). */
defineProps({
    form: { type: Object, required: true },
    commande: { type: Object, required: true },
    modifiable: { type: Function, required: true },
    atteinte: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
    peutCreerFournisseur: { type: Boolean, default: false },
});
const emit = defineEmits(['executer']);

const CASES = [
    { champ: 'commandeSurMarche', libelle: 'Commande sur marché' },
    { champ: 'dateLivraisonRenseignee', libelle: 'Date de livraison renseignée' },
    { champ: 'pjSurSifac', libelle: 'Pièces jointes rattachées dans SIFAC' },
];

const etape = etapeParId(2);
</script>

<template>
    <div class="space-y-4">
        <Message v-if="!atteinte" severity="info" :closable="false">
            Cette étape s'ouvre après l'avis favorable sur la demande.
        </Message>
        <Card :class="etape.bordure" title="Vérification du service financier (SIFAC)" icon="pi pi-search" color="amber">
            <template #header-actions>
                <Tag v-if="commande.dateVerificationFinanciere" severity="success" :value="`Vérifiée le ${formatDate(commande.dateVerificationFinanciere)}`" />
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <FournisseurChamp
                        v-model="form.fournisseur"
                        v-model:propose="form.fournisseurPropose"
                        id-prefixe="verif-fournisseur"
                        :modifiable="modifiable('fournisseur')"
                        :proposable="false"
                        :peut-creer="peutCreerFournisseur"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="verif-numero" class="q-label q-label-required">N° de bon de commande SIFAC</label>
                    <input id="verif-numero" v-model="form.numeroBonCommande" type="text" maxlength="50" :disabled="!modifiable('numeroBonCommande')" class="q-input" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="verif-cf" class="q-label">Centre financier</label>
                    <input id="verif-cf" v-model="form.centreFinancier" type="text" maxlength="50" :disabled="!modifiable('centreFinancier')" class="q-input" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="verif-colis" class="q-label">Colis attendu</label>
                    <input id="verif-colis" v-model="form.colisAttendu" type="text" maxlength="100" :disabled="!modifiable('colisAttendu')" class="q-input" />
                </div>
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label v-for="c in CASES" :key="c.champ" :for="`verif-${c.champ}`" class="flex items-center gap-2 text-sm">
                        <input :id="`verif-${c.champ}`" v-model="form[c.champ]" type="checkbox" :disabled="!modifiable(c.champ)" class="q-check" />
                        <span>{{ c.libelle }}</span>
                    </label>
                </div>
                <dl class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div><dt class="q-label">Vérifiée par</dt><dd>{{ nomPersonne(commande.verificateurFinancier) }}</dd></div>
                    <div v-if="commande.motifRejet && commande.statut === 'REJETE'"><dt class="q-label">Motif du rejet</dt><dd class="whitespace-pre-line">{{ commande.motifRejet }}</dd></div>
                </dl>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                <ActionsWorkflow :actions="actions" :etape="2" :envoi="envoi" @executer="emit('executer', $event)" />
            </div>
        </Card>
    </div>
</template>
