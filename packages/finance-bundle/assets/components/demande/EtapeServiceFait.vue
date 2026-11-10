<script setup>
import { etapeParId, formatDate, formatMontant, nomPersonne, dateSaisie, messageErreurApi } from '@/utils/finance';
import { ref, computed, watch } from 'vue';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { Card, EmptyState } from '@components';
import ActionsWorkflow from '../ActionsWorkflow.vue';
import {
    ajouterBonsLivraisonService,
    getBonLivraisonBlobService,
    supprimerBonLivraisonService,
    viserReceptionService,
} from '@/requests/financeService';

/**
 * Étape 4 : service fait, en deux temps.
 *  1. Le demandeur constate la réception (totale ou partielle) et dépose le ou les bons de livraison (PDF).
 *  2. Le service financier vise chaque réception (numéros MIGO possibles) ; le dernier visa certifie le service fait.
 * Le service financier complète aussi les informations SIFAC de chaque réception.
 */
const props = defineProps({
    commande: { type: Object, required: true },
    atteinte: { type: Boolean, default: false },
    gererReceptions: { type: Boolean, default: false },
    /** Ajout / retrait de bons de livraison sur les réceptions non visées. */
    bonsLivraison: { type: Boolean, default: false },
    /** Visa des réceptions (service financier). */
    viserReceptions: { type: Boolean, default: false },
    actions: { type: Array, default: () => [] },
    envoi: { type: String, default: null },
    receptionEnregistree: { type: [Number, String], default: null },
});
const emit = defineEmits(['executer', 'enregistrer-reception', 'recharger']);
const toast = useToast();

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

const MIGO = /^[A-Za-z0-9-]{1,30}$/;
const erreurMigo = ref({});
function ajouterMigo(id) {
    const numero = String(nouveauMigo.value[id] || '').trim();
    erreurMigo.value[id] = '';
    if (!numero) return;
    if (!MIGO.test(numero)) {
        erreurMigo.value[id] = 'Lettres, chiffres et tirets, 30 caractères au plus.';
        return;
    }
    if (!sifac.value[id].numerosMigo.includes(numero)) sifac.value[id].numerosMigo.push(numero);
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

const enAttenteDeVisa = computed(() => (props.commande.receptions || []).filter(r => !r.visee).length);
const modifiable = (r) => props.bonsLivraison && !r.visee;

// ---------- Bons de livraison ----------
const enCours = ref(null); // clé de l'opération en cours (affiche le chargement du bon bouton)

async function ouvrirBl(r, d) {
    enCours.value = `lire-${d.id}`;
    try {
        const blob = await getBonLivraisonBlobService(r.id, d.id);
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank', 'noopener');
        setTimeout(() => URL.revokeObjectURL(url), 60000);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Bon de livraison indisponible', detail: messageErreurApi(e), life: 5000 });
    } finally {
        enCours.value = null;
    }
}

async function ajouterBl(r, event) {
    const fichiers = [...(event.target.files || [])];
    event.target.value = '';
    if (!fichiers.length) return;
    enCours.value = `ajout-${r.id}`;
    try {
        await ajouterBonsLivraisonService(r.id, fichiers);
        toast.add({ severity: 'success', summary: fichiers.length > 1 ? 'Bons de livraison ajoutés' : 'Bon de livraison ajouté', life: 2500 });
        emit('recharger');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Dépôt impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        enCours.value = null;
    }
}

async function retirerBl(r, d) {
    enCours.value = `retrait-${d.id}`;
    try {
        await supprimerBonLivraisonService(r.id, d.id);
        toast.add({ severity: 'success', summary: 'Bon de livraison retiré', detail: d.nomFichier, life: 2500 });
        emit('recharger');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Retrait impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        enCours.value = null;
    }
}

// ---------- Visa du service financier ----------
async function viser(r) {
    enCours.value = `visa-${r.id}`;
    try {
        const resultat = await viserReceptionService(r.id, sifac.value[r.id]?.numerosMigo ?? []);
        toast.add({
            severity: 'success',
            summary: 'Réception visée',
            detail: resultat.certifie ? 'Toutes les réceptions sont visées : le service fait est certifié.' : undefined,
            life: 3500,
        });
        emit('recharger');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Visa impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        enCours.value = null;
    }
}

const etape = etapeParId(4);
</script>

<template>
    <div class="space-y-4">
        <Message v-if="!atteinte" severity="info" :closable="false">
            Cette étape s'ouvre une fois la commande validée.
        </Message>
        <Card :class="etape.bordure" title="Service fait" subtitle="1. Constatation et bons de livraison (demandeur) · 2. Visa de chaque réception (service financier)" icon="pi pi-box" color="teal">
            <template #header-actions>
                <Tag v-if="commande.dateCertificationServiceFait" severity="success" :value="`Certifié le ${formatDate(commande.dateCertificationServiceFait)}`" />
                <Tag v-else-if="enAttenteDeVisa" severity="warn" :value="`${enAttenteDeVisa} réception(s) à viser`" />
            </template>
            <EmptyState v-if="!commande.receptions?.length" title="Aucune réception constatée" description="À la livraison, le demandeur constate la réception (tout ou partie) et dépose le bon de livraison." icon="pi pi-box" compact />
            <ul v-else class="space-y-4">
                <li
                    v-for="(r, index) in commande.receptions"
                    :key="r.id"
                    class="rounded-lg border p-4"
                    :class="r.visee ? 'border-gray-200 dark:border-gray-700' : 'border-amber-300 dark:border-amber-700'"
                    :aria-label="`Réception ${index + 1}`"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold">Réception {{ index + 1 }} — {{ formatDate(r.dateEffectiveReception) }}</span>
                        <div class="flex flex-wrap gap-2">
                            <Tag :value="r.typeReception === 'PARTIELLE' ? 'Partielle' : 'Totale'" :severity="r.typeReception === 'PARTIELLE' ? 'warn' : 'info'" />
                            <Tag :value="r.prestationsConformes ? 'Conforme' : 'Non conforme'" :severity="r.prestationsConformes ? 'success' : 'danger'" />
                            <Tag v-if="r.visee" severity="success" icon="pi pi-check" :value="`Visée le ${formatDate(r.dateVisa)}`" />
                            <Tag v-else severity="warn" value="En attente de visa" />
                        </div>
                    </div>
                    <dl class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div><dt class="q-label">Constatée par</dt><dd>{{ nomPersonne(r.receptionnaire) }}<span v-if="r.qualiteReceptionnaire"> ({{ r.qualiteReceptionnaire }})</span></dd></div>
                        <div><dt class="q-label">Montant TTC constaté</dt><dd>{{ formatMontant(r.montantTtcConstate) }}</dd></div>
                        <div v-if="r.visee"><dt class="q-label">Visée par</dt><dd>{{ nomPersonne(r.viseur) }}</dd></div>
                        <div v-if="r.detailsReceptionPartielle" class="sm:col-span-3"><dt class="q-label">Reçu</dt><dd class="whitespace-pre-line">{{ r.detailsReceptionPartielle }}</dd></div>
                    </dl>

                    <!-- 1. Bons de livraison -->
                    <section class="mt-4" :aria-label="`Bons de livraison de la réception ${index + 1}`">
                        <h4 class="q-label mb-2">Bon(s) de livraison</h4>
                        <ul v-if="r.documents?.length" class="flex flex-col gap-1">
                            <li v-for="d in r.documents" :key="d.id" class="flex items-center justify-between gap-2 rounded border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm">
                                <button type="button" class="flex items-center gap-2 min-w-0 text-left hover:underline" :aria-label="`Ouvrir ${d.nomFichier}`" @click="ouvrirBl(r, d)">
                                    <i :class="enCours === `lire-${d.id}` ? 'pi pi-spin pi-spinner' : 'pi pi-file-pdf text-red-500'" aria-hidden="true" />
                                    <span class="truncate">{{ d.nomFichier }}</span>
                                </button>
                                <span class="flex items-center gap-2 shrink-0 text-xs text-gray-500">
                                    {{ formatDate(d.dateDepot) }}
                                    <Button v-if="modifiable(r)" icon="pi pi-trash" text rounded severity="danger" size="small" :aria-label="`Retirer ${d.nomFichier}`" :loading="enCours === `retrait-${d.id}`" @click="retirerBl(r, d)" />
                                </span>
                            </li>
                        </ul>
                        <Message v-else severity="warn" :closable="false">Aucun bon de livraison : il doit être déposé avant le visa.</Message>
                        <template v-if="modifiable(r)">
                            <label :for="`bl-ajout-${r.id}`" class="mt-2 inline-flex items-center gap-2 cursor-pointer rounded border border-dashed border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                                <i :class="enCours === `ajout-${r.id}` ? 'pi pi-spin pi-spinner' : 'pi pi-upload'" aria-hidden="true" />
                                Ajouter un bon de livraison (PDF)
                            </label>
                            <input :id="`bl-ajout-${r.id}`" type="file" accept="application/pdf" multiple class="sr-only" @change="ajouterBl(r, $event)" />
                        </template>
                    </section>

                    <!-- 2. Visa et informations SIFAC (service financier) -->
                    <section v-if="sifac[r.id]" class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 grid grid-cols-1 sm:grid-cols-3 gap-3" :aria-label="`Visa et SIFAC de la réception ${index + 1}`">
                        <div class="flex flex-col gap-1 sm:col-span-3">
                            <label :for="`migo-${r.id}`" class="q-label">Numéros MIGO (SIFAC)</label>
                            <div class="flex flex-wrap gap-2">
                                <Tag v-for="(m, i) in sifac[r.id].numerosMigo" :key="m" severity="secondary">
                                    <span class="text-sm">{{ m }}</span>
                                    <button v-if="gererReceptions" type="button" class="ml-1" :aria-label="`Retirer le numéro ${m}`" @click="sifac[r.id].numerosMigo.splice(i, 1)"><i class="pi pi-times text-sm" /></button>
                                </Tag>
                                <span v-if="!sifac[r.id].numerosMigo.length" class="text-sm text-gray-500">Aucun numéro</span>
                            </div>
                            <div v-if="gererReceptions" class="flex gap-2 mt-1">
                                <input :id="`migo-${r.id}`" v-model="nouveauMigo[r.id]" type="text" maxlength="30" class="q-input" placeholder="Ex. : 5000412345" @keydown.enter.prevent="ajouterMigo(r.id)" />
                                <Button label="Ajouter" icon="pi pi-plus" size="small" severity="secondary" outlined @click="ajouterMigo(r.id)" />
                            </div>
                            <small v-if="erreurMigo[r.id]" class="text-red-600">{{ erreurMigo[r.id] }}</small>
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
                        <div v-if="gererReceptions" class="sm:col-span-3 flex flex-wrap justify-end gap-2">
                            <Button label="Enregistrer" icon="pi pi-save" size="small" severity="secondary" outlined :loading="receptionEnregistree === r.id" @click="enregistrer(r.id)" />
                            <Button
                                v-if="viserReceptions && !r.visee"
                                label="Viser cette réception"
                                icon="pi pi-verified"
                                size="small"
                                :disabled="!r.documents?.length"
                                :title="r.documents?.length ? 'Les numéros MIGO saisis sont enregistrés avec le visa' : 'Le bon de livraison doit être déposé avant le visa'"
                                :loading="enCours === `visa-${r.id}`"
                                @click="viser(r)"
                            />
                        </div>
                    </section>
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
