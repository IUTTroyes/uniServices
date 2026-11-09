<script setup>
import { etapeParId, ttcDepuisHt, htDepuisTtc, montantsIncoherents, formatDate, nomPersonne } from '@/utils/finance';
import { ref, computed, watch } from 'vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { Card } from '@components';
import FournisseurChamp from '../FournisseurChamp.vue';
import ActionsWorkflow from '../ActionsWorkflow.vue';

/**
 * Étape 1 : la demande (objet, montants, fournisseur), le document signé et l'avis du valideur.
 * HT et TTC se calculent l'un l'autre avec le taux de TVA, sans jamais écraser un montant saisi à la main.
 */
const props = defineProps({
    form: { type: Object, required: true },
    commande: { type: Object, default: null },
    nouvelle: { type: Boolean, default: false },
    modifiable: { type: Function, required: true },
    tauxTva: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
    peutDeposer: { type: Boolean, default: false },
    /** Le document signé peut porter la décision (avis favorable ou refus) : aucune ressaisie. */
    peutDecider: { type: Boolean, default: false },
    peutCreerFournisseur: { type: Boolean, default: false },
    depotEnCours: { type: Boolean, default: false },
});
const emit = defineEmits(['executer', 'deposer', 'consulter-document', 'telecharger-pdf']);

const PRESTATIONS = ['Fournitures', 'Services', 'Travaux'];
const AVIS = {
    FAVORABLE: { valeur: 'Avis favorable', severite: 'success' },
    DEFAVORABLE: { valeur: 'Avis défavorable', severite: 'danger' },
    DEMANDE_INFO: { valeur: 'Précisions demandées', severite: 'warn' },
};

// Montant saisi à la main : l'autre suit le taux. Les deux saisis : ni l'un ni l'autre n'est recalculé.
const htSaisi = ref(false);
const ttcSaisi = ref(false);
watch(() => props.commande?.id, () => {
    htSaisi.value = props.form.montantHt !== null && props.form.montantHt !== '';
    ttcSaisi.value = htSaisi.value && montantsIncoherents(props.form.montantHt, props.form.montantTtc, props.form.tauxTva);
}, { immediate: true });

const tauxProposes = computed(() => props.tauxTva.filter(t => t.actif || Number(t.taux) === Number(props.form.tauxTva)));
const incoherents = computed(() => montantsIncoherents(props.form.montantHt, props.form.montantTtc, props.form.tauxTva));
const avis = computed(() => AVIS[props.commande?.avisDirecteur] ?? { valeur: 'En attente d\'avis', severite: 'secondary' });

function saisirHt(valeur) {
    props.form.montantHt = valeur;
    htSaisi.value = valeur !== '';
    if (!ttcSaisi.value) props.form.montantTtc = ttcDepuisHt(valeur, props.form.tauxTva);
}
function saisirTtc(valeur) {
    props.form.montantTtc = valeur;
    ttcSaisi.value = valeur !== '';
    if (!htSaisi.value) props.form.montantHt = htDepuisTtc(valeur, props.form.tauxTva);
}
function changerTaux() {
    if (htSaisi.value && !ttcSaisi.value) props.form.montantTtc = ttcDepuisHt(props.form.montantHt, props.form.tauxTva);
    else if (ttcSaisi.value && !htSaisi.value) props.form.montantHt = htDepuisTtc(props.form.montantTtc, props.form.tauxTva);
}
function recalculerTtc() {
    props.form.montantTtc = ttcDepuisHt(props.form.montantHt, props.form.tauxTva);
    ttcSaisi.value = false;
}

// Dépôt du document signé : avec une décision (avis favorable / refus) ou simple remplacement du fichier.
const fichier = ref(null);
const decisionChoisie = ref(null);
const dialogueRefus = ref(false);
const refus = ref({ motif: '', fichier: null });

function choisirFichier(decision) {
    decisionChoisie.value = decision;
    fichier.value?.click();
}
function fichierSelectionne(event) {
    const f = event.target.files?.[0];
    if (f) emit('deposer', { fichier: f, decision: decisionChoisie.value });
    event.target.value = '';
}
function ouvrirRefus() {
    refus.value = { motif: '', fichier: null };
    dialogueRefus.value = true;
}
function validerRefus() {
    if (!refus.value.motif.trim()) return;
    emit('deposer', { fichier: refus.value.fichier, decision: 'defavorable', commentaire: refus.value.motif.trim() });
    dialogueRefus.value = false;
}

const etape = etapeParId(1);
</script>

<template>
    <div class="space-y-6">
        <Card :class="etape.bordure" title="Demande d'achat" subtitle="Objet, montants et fournisseur pressenti" icon="pi pi-file-edit" color="blue">
            <template #header-actions>
                <Tag v-if="commande?.dateDemande && !nouvelle && commande?.statut !== 'BROUILLON'" severity="info" :value="`Soumise le ${formatDate(commande.dateDemande)}`" />
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <span class="q-label">Demandeur</span>
                    <span class="text-sm">{{ nouvelle ? 'Vous' : nomPersonne(commande?.demandeur) }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="demande-departement" class="q-label q-label-required">Département d'imputation</label>
                    <select v-if="nouvelle" id="demande-departement" v-model="form.departement" required class="q-input">
                        <option :value="null" disabled>Choisir le département</option>
                        <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.libelle }}</option>
                    </select>
                    <span v-else id="demande-departement" class="text-sm">{{ commande?.departement?.libelle || '—' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="demande-prestation" class="q-label q-label-required">Type de prestation</label>
                    <select id="demande-prestation" v-model="form.prestation" :disabled="!modifiable('prestation')" class="q-input">
                        <option v-for="p in PRESTATIONS" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="demande-cf" class="q-label">Centre financier</label>
                    <input id="demande-cf" v-model="form.centreFinancier" type="text" maxlength="50" :disabled="!modifiable('centreFinancier')" class="q-input" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                    <label for="demande-objet" class="q-label q-label-required">Objet de la dépense</label>
                    <input id="demande-objet" v-model="form.objetDemande" type="text" maxlength="255" required :disabled="!modifiable('objetDemande')" class="q-input" placeholder="Ex. : matériel informatique salle 104" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="demande-taux" class="q-label">Taux de TVA</label>
                    <select id="demande-taux" v-model="form.tauxTva" :disabled="!modifiable('tauxTva')" class="q-input" @change="changerTaux">
                        <option :value="null">Non renseigné</option>
                        <option v-for="t in tauxProposes" :key="t.id" :value="t.taux">{{ t.libelle }}</option>
                    </select>
                </div>
                <div class="hidden md:block" />
                <div class="flex flex-col gap-1">
                    <label for="demande-ht" class="q-label">Montant HT (€)</label>
                    <input id="demande-ht" :value="form.montantHt" type="number" min="0" step="0.01" :disabled="!modifiable('montantHt')" class="q-input" @input="saisirHt($event.target.value)" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="demande-ttc" class="q-label">Montant TTC (€)</label>
                    <input id="demande-ttc" :value="form.montantTtc" type="number" min="0" step="0.01" :disabled="!modifiable('montantTtc')" class="q-input" @input="saisirTtc($event.target.value)" />
                </div>
                <div v-if="incoherents" class="md:col-span-2 q-callout q-callout-warning flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm">Le montant TTC saisi ne correspond pas au HT avec ce taux de TVA : les deux montants seront conservés tels quels.</span>
                    <Button v-if="modifiable('montantTtc')" label="Recalculer le TTC" size="small" severity="secondary" outlined @click="recalculerTtc" />
                </div>
                <p class="md:col-span-2 q-hint">Indiquez le HT, ou le TTC et le taux : l'autre montant est calculé.</p>
                <div class="md:col-span-2">
                    <FournisseurChamp
                        v-model="form.fournisseur"
                        v-model:propose="form.fournisseurPropose"
                        id-prefixe="demande-fournisseur"
                        :modifiable="modifiable('fournisseur')"
                        :proposable="modifiable('fournisseurPropose')"
                        :peut-creer="peutCreerFournisseur"
                    />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                    <label for="demande-note" class="q-label">Note explicative (contexte, motivation, objectifs pédagogiques)</label>
                    <textarea id="demande-note" v-model="form.noteExplicative" rows="3" :disabled="!modifiable('noteExplicative')" class="q-input" />
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-wrap justify-between gap-2">
                <Button v-if="!nouvelle" label="PDF à signer" icon="pi pi-file-pdf" severity="secondary" outlined size="small" @click="emit('telecharger-pdf', 1)" />
                <ActionsWorkflow :actions="actions" :etape="1" :envoi="envoi" @executer="emit('executer', $event)" />
            </div>
        </Card>

        <Card :class="etape.bordure" v-if="!nouvelle" title="Document signé" subtitle="Demande émargée par le demandeur ou son responsable" icon="pi pi-file-pdf" color="blue">
            <template #header-actions>
                <Tag :severity="commande?.pdfSigne ? 'success' : 'secondary'" :value="commande?.pdfSigne ? 'Déposé' : 'Non déposé'" />
            </template>
            <p v-if="peutDecider" class="text-sm text-gray-600 dark:text-gray-400">
                Téléchargez le PDF de la demande, faites-le signer, puis déposez le document <strong>avec la décision</strong> :
                elle est enregistrée telle quelle, sans ressaisie de la demande.
            </p>
            <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                Document de la demande signé par le directeur ou le chef de service (PDF, 10 Mo au plus).
            </p>
            <input id="demande-document" ref="fichier" type="file" accept="application/pdf" class="hidden" @change="fichierSelectionne" />
            <div class="mt-3 flex flex-wrap gap-2">
                <Button v-if="commande?.pdfSigne" label="Consulter le document signé" icon="pi pi-eye" severity="secondary" outlined size="small" @click="emit('consulter-document')" />
                <template v-if="peutDecider">
                    <Button label="Avis favorable : déposer le document signé" icon="pi pi-check" severity="success" size="small" :loading="depotEnCours" @click="choisirFichier('favorable')" />
                    <Button label="La demande est refusée" icon="pi pi-times" severity="danger" outlined size="small" :disabled="depotEnCours" @click="ouvrirRefus" />
                </template>
                <Button v-else-if="peutDeposer" :label="commande?.pdfSigne ? 'Remplacer le document' : 'Déposer le PDF signé'" icon="pi pi-upload" size="small" :loading="depotEnCours" @click="choisirFichier(null)" />
            </div>
        </Card>

        <Card :class="etape.bordure" v-if="!nouvelle" title="Avis du directeur ou du chef de service" icon="pi pi-compass" color="blue">
            <template #header-actions>
                <Tag :severity="avis.severite" :value="avis.valeur" />
            </template>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div><dt class="q-label">Date</dt><dd>{{ formatDate(commande?.dateAvisDirecteur) }}</dd></div>
                <div><dt class="q-label">Par</dt><dd>{{ commande?.signataireDirecteur ? nomPersonne(commande.signataireDirecteur) : (commande?.avisDirecteur ? 'Document signé déposé' : '—') }}</dd></div>
                <div class="sm:col-span-3" v-if="commande?.avisDirecteurCommentaire">
                    <dt class="q-label">Commentaire</dt><dd class="whitespace-pre-line">{{ commande.avisDirecteurCommentaire }}</dd>
                </div>
            </dl>
        </Card>

        <Dialog v-model:visible="dialogueRefus" header="La demande est refusée" modal class="w-full max-w-lg">
            <form id="form-refus" class="q-form" @submit.prevent="validerRefus">
                <p class="text-sm text-gray-600 dark:text-gray-400">La demande sera rejetée et le motif lui sera communiqué.</p>
                <div class="flex flex-col gap-1">
                    <label for="refus-motif" class="q-label q-label-required">Motif du refus</label>
                    <textarea id="refus-motif" v-model="refus.motif" rows="4" required class="q-input" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="refus-fichier" class="q-label">Document reçu (facultatif)</label>
                    <input id="refus-fichier" type="file" accept="application/pdf" class="q-input" @change="refus.fichier = $event.target.files?.[0] ?? null" />
                </div>
            </form>
            <template #footer>
                <Button label="Annuler" severity="secondary" outlined @click="dialogueRefus = false" />
                <Button type="submit" form="form-refus" label="Enregistrer le refus" severity="danger" :disabled="!refus.motif.trim()" />
            </template>
        </Dialog>
    </div>
</template>
