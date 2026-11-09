<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';
import { ETAPES, formatDate, formatMontant, nomPersonne } from '@/utils/finance';
import BonCommandeWorkflowTimeline from './BonCommandeWorkflowTimeline.vue';

/**
 * Déroulé d'une ligne du tableau de bord : frise des étapes et un bloc par étape (bordure de la couleur de l'étape)
 * avec les informations clés, sans ouvrir la demande.
 */
const props = defineProps({
    commande: { type: Object, required: true },
});
const emit = defineEmits(['ouvrir', 'apercu']);

const oui = (v, vrai = 'Oui', faux = 'Non') => (v ? vrai : faux);
const AVIS = { FAVORABLE: 'Favorable', DEFAVORABLE: 'Défavorable', DEMANDE_INFO: 'Précisions demandées' };

const blocs = computed(() => {
    const c = props.commande;
    const r = c.receptions?.at(-1);
    const lignes = {
        1: [
            ['Demandeur', nomPersonne(c.demandeur)],
            ['Demandée le', formatDate(c.dateDemande)],
            ['Avis', AVIS[c.avisDirecteur] ?? 'En attente'],
            ['Document signé', oui(c.pdfSigne, 'Déposé')],
        ],
        2: [
            ['Fournisseur', c.fournisseur?.nomFournisseur || (c.fournisseurPropose ? `${c.fournisseurPropose} (proposé)` : 'Non renseigné')],
            ['N° BC SIFAC', c.numeroBonCommande || 'Non saisi'],
            ['Sur marché', oui(c.commandeSurMarche)],
            ['Vérifiée le', formatDate(c.dateVerificationFinanciere)],
        ],
        3: [
            ['Visa', c.dateVisaEngagement ? 'Accordé' : 'En attente'],
            ['Date du visa', formatDate(c.dateVisaEngagement)],
            ['BC transmis', oui(c.bcTransmis, 'Transmis')],
            ['PJ rattachées SIFAC', oui(c.pjSurSifac)],
        ],
        4: [
            ['Réceptionné le', formatDate(r?.dateEffectiveReception)],
            ['Conforme', r ? oui(r.prestationsConformes) : '—'],
            ['MIGO', r?.numerosMigo?.length ? r.numerosMigo.join(', ') : '—'],
            ['Certifié le', formatDate(c.dateCertificationServiceFait)],
        ],
        5: [
            ['Facture reçue le', formatDate(c.dateArriveeFacture)],
            ['Payée le', formatDate(c.datePaiement)],
            ['Facture finale', oui(c.factureFinale, 'Soldée', 'Non soldée')],
            ...(c.rejetFactureMotif ? [['Rejet', c.rejetFactureMotif]] : []),
        ],
    };
    return ETAPES.map(e => ({ ...e, lignes: lignes[e.id] }));
});
</script>

<template>
    <div class="p-4 space-y-4 bg-slate-50/80 dark:bg-slate-900/60 border-y border-slate-200 dark:border-slate-700">
        <div class="card p-3"><BonCommandeWorkflowTimeline :commande="commande" /></div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ formatMontant(commande.montantTtc ?? commande.montantHt) }} TTC
            <span v-if="commande.montantHt"> — {{ formatMontant(commande.montantHt) }} HT</span>
            <span v-if="commande.tauxTva !== null && commande.tauxTva !== undefined"> — TVA {{ Number(commande.tauxTva).toLocaleString('fr-FR') }} %</span>
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            <section v-for="b in blocs" :key="b.id" class="card p-3" :class="b.bordure" :aria-label="`Étape ${b.id} : ${b.titre}`">
                <h4 class="flex items-center gap-2 text-sm font-bold mb-2" :class="b.texte">
                    <i :class="[b.icone, b.iconeClasse]" aria-hidden="true" />{{ b.id }}. {{ b.titre }}
                </h4>
                <dl class="space-y-1 text-sm">
                    <div v-for="[libelle, valeur] in b.lignes" :key="libelle" class="flex justify-between gap-2 border-b border-slate-100 dark:border-slate-700/50 py-0.5 last:border-0">
                        <dt class="text-gray-500 dark:text-gray-400">{{ libelle }}</dt>
                        <dd class="font-medium text-right break-words min-w-0">{{ valeur }}</dd>
                    </div>
                </dl>
            </section>
        </div>
        <div class="flex justify-end gap-2">
            <Button label="Aperçu PDF" icon="pi pi-file-pdf" severity="secondary" outlined size="small" @click="emit('apercu')" />
            <Button label="Ouvrir la demande" icon="pi pi-folder-open" size="small" @click="emit('ouvrir')" />
        </div>
    </div>
</template>
