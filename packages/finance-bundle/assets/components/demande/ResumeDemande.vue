<script setup>
import { computed } from 'vue';
import { Card } from '@components';
import { formatMontant, formatDate, nomPersonne, etapeCourante, etapeParId, estFinalisee } from '@/utils/finance';
import StatutTag from '../StatutTag.vue';

/** Résumé d'une demande, toujours visible à côté des étapes : statut, montants, imputation, fournisseur, références. */
const props = defineProps({
    commande: { type: Object, required: true },
});

const finalisee = computed(() => estFinalisee(props.commande));
const etape = computed(() => etapeParId(etapeCourante(props.commande.statut)));
const bordure = computed(() => (finalisee.value ? 'border-l-4 border-l-emerald-600' : (etape.value?.bordure || 'border-l-4 border-l-red-600')));
const fournisseur = computed(() => {
    const f = props.commande.fournisseur;
    if (f) return `${f.numeroFournisseur ? f.numeroFournisseur + ' - ' : ''}${f.nomFournisseur}`;
    return props.commande.fournisseurPropose ? `${props.commande.fournisseurPropose} (proposé)` : '—';
});
const taux = computed(() => (props.commande.tauxTva === null || props.commande.tauxTva === undefined
    ? null
    : `${Number(props.commande.tauxTva).toLocaleString('fr-FR')} %`));
</script>

<template>
    <Card title="Résumé" icon="pi pi-info-circle" :color="finalisee ? 'emerald' : (etape?.couleur || 'gray')" :class="bordure">
        <div class="flex flex-col gap-3 text-sm">
            <StatutTag :statut="commande.statut" :finalisee="finalisee" class="self-start" />
            <dl class="flex flex-col gap-2">
                <div>
                    <dt class="q-label">Montant TTC</dt>
                    <dd class="text-lg font-bold">{{ formatMontant(commande.montantTtc) }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><dt class="q-label">HT</dt><dd>{{ formatMontant(commande.montantHt) }}</dd></div>
                    <div><dt class="q-label">TVA</dt><dd>{{ taux || '—' }}</dd></div>
                </div>
                <div><dt class="q-label">{{ commande.service ? 'Service' : 'Département' }}</dt><dd>{{ commande.service?.libelle || commande.departement?.libelle || '—' }}</dd></div>
                <div><dt class="q-label">Demandeur</dt><dd>{{ nomPersonne(commande.demandeur) }}</dd></div>
                <div><dt class="q-label">Fournisseur</dt><dd class="break-words">{{ fournisseur }}</dd></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><dt class="q-label">N° BC SIFAC</dt><dd>{{ commande.numeroBonCommande || '—' }}</dd></div>
                    <div><dt class="q-label">Centre financier</dt><dd class="break-words">{{ commande.centreFinancier || '—' }}</dd></div>
                </div>
                <div><dt class="q-label">Demande du</dt><dd>{{ formatDate(commande.dateDemande) }}</dd></div>
                <div><dt class="q-label">Document signé</dt><dd>{{ commande.pdfSigne ? 'Déposé' : 'Non déposé' }}</dd></div>
            </dl>
        </div>
    </Card>
</template>
