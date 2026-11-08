<script setup>
/**
 * Modèle de convention de stage (super administrateur).
 * La convention PDF est générée depuis ce modèle s'il est enregistré ; sinon, depuis le gabarit intégré au package.
 * Le modèle est un document HTML ; les balises « {objet.champ} » sont remplacées par les données du dossier.
 */
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import { DocumentTextIcon, ArrowDownTrayIcon, TrashIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { ActionButtonVertical, Card, EmptyState, HeaderComponent, ListSkeleton } from '@components';
import {
  getStageTemplatesService,
  updateStageTemplateService,
  createStageTemplateService,
  deleteStageTemplateService,
  getModeleConventionDefautService,
  getBalisesModeleConventionService,
  apercuModeleConventionService
} from '@/requests/stage_service';
import { useConfirmation } from '@/composables/useConfirmation';
import { messageErreurApi } from '@/utils/erreurApi';

const CODE = 'default_convention';
const toast = useToast();
const { confirmer } = useConfirmation();

const chargement = ref(true);
const enregistrement = ref(false);
const modele = ref(null);        // modèle enregistré en base (null : le gabarit intégré est utilisé)
const texte = ref('');
const texteEnregistre = ref('');
const balises = ref([]);
const filtreBalise = ref('');
const apercu = ref('');
const apercuDossier = ref('');
const apercuEnCours = ref(false);
const zoneTexte = ref(null);

const modifie = computed(() => texte.value !== texteEnregistre.value);
const balisesFiltrees = computed(() => balises.value.filter(b => b.includes(filtreBalise.value.trim().toLowerCase())));
const erreur = (error, defaut) => toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, defaut), life: 5000 });

const charger = async () => {
  chargement.value = true;
  try {
    const [modeles, listeBalises] = await Promise.all([getStageTemplatesService(), getBalisesModeleConventionService()]);
    balises.value = listeBalises || [];
    modele.value = [...(modeles?.member || modeles || [])].find(m => m.code === CODE) || null;
    texte.value = modele.value?.texte || '';
    texteEnregistre.value = texte.value;
  } catch (error) {
    erreur(error, 'Impossible de charger le modèle.');
  } finally {
    chargement.value = false;
  }
};

const partirDuModeleParDefaut = async () => {
  if (texte.value.trim() && !await confirmer({ message: 'Remplacer le texte en cours par le modèle livré avec l\'application ?', libelle: 'Remplacer' })) return;
  try {
    texte.value = (await getModeleConventionDefautService()).texte || '';
  } catch (error) {
    erreur(error, 'Impossible de charger le modèle par défaut.');
  }
};

const enregistrer = async () => {
  if (!texte.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Modèle vide', detail: 'Pour revenir au gabarit intégré, supprimez le modèle.', life: 4000 });
    return;
  }
  enregistrement.value = true;
  try {
    modele.value = modele.value?.id
      ? await updateStageTemplateService(modele.value.id, { texte: texte.value })
      : await createStageTemplateService({ code: CODE, libelle: 'Convention de stage', texte: texte.value });
    texteEnregistre.value = texte.value;
    toast.add({ severity: 'success', summary: 'Modèle enregistré', detail: 'Les prochaines conventions seront générées avec ce modèle.', life: 3000 });
  } catch (error) {
    erreur(error, 'Impossible d\'enregistrer le modèle.');
  } finally {
    enregistrement.value = false;
  }
};

const supprimer = async () => {
  if (!modele.value?.id || !await confirmer({
    message: 'Supprimer le modèle ? Les conventions seront de nouveau générées avec le gabarit intégré.',
    titre: 'Supprimer le modèle', libelle: 'Supprimer', danger: true
  })) return;
  try {
    await deleteStageTemplateService(modele.value.id);
    modele.value = null;
    texte.value = '';
    texteEnregistre.value = '';
    apercu.value = '';
    toast.add({ severity: 'info', summary: 'Modèle supprimé', detail: 'Le gabarit intégré est de nouveau utilisé.', life: 3000 });
  } catch (error) {
    erreur(error, 'Impossible de supprimer le modèle.');
  }
};

/** Insère la balise à la position du curseur. */
const insererBalise = (balise) => {
  const el = zoneTexte.value;
  if (!el) {
    texte.value += balise;
    return;
  }
  const debut = el.selectionStart ?? texte.value.length;
  const fin = el.selectionEnd ?? debut;
  texte.value = texte.value.slice(0, debut) + balise + texte.value.slice(fin);
  requestAnimationFrame(() => {
    el.focus();
    el.selectionStart = el.selectionEnd = debut + balise.length;
  });
};

const actualiserApercu = async () => {
  apercuEnCours.value = true;
  try {
    const data = await apercuModeleConventionService(texte.value);
    apercu.value = data.html;
    apercuDossier.value = data.dossier;
  } catch (error) {
    erreur(error, 'Impossible de construire l\'aperçu.');
  } finally {
    apercuEnCours.value = false;
  }
};

onMounted(charger);
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent titre="Modèle de convention de stage" icon="pi pi-cog" color="teal" :show-back="false"
      description="Document HTML utilisé pour générer les conventions ; sans modèle enregistré, le gabarit intégré au package est utilisé.">
      <template #actions>
        <div class="flex items-center gap-2">
          <ActionButtonVertical :icon="ArrowDownTrayIcon" label="Modèle par défaut" @click="partirDuModeleParDefaut" />
          <ActionButtonVertical v-if="modele" :icon="TrashIcon" label="Supprimer" severity="danger" @click="supprimer" />
          <ActionButtonVertical :icon="CheckIcon" label="Enregistrer" severity="primary" :disabled="!modifie || enregistrement" @click="enregistrer" />
        </div>
      </template>
    </HeaderComponent>

    <ListSkeleton v-if="chargement" />

    <div v-else class="grid grid-cols-1 xl:grid-cols-12 gap-6">
      <Card class="xl:col-span-7" title="Modèle (HTML)" :icon="DocumentTextIcon" color="teal"
        :badge="modele ? (modifie ? 'Modifié' : 'Utilisé pour les conventions') : 'Gabarit intégré utilisé'"
        :badge-severity="modele ? (modifie ? 'warn' : 'success') : 'secondary'" body-class="space-y-2">
        <label for="modele-texte" class="sr-only">Modèle HTML</label>
        <textarea id="modele-texte" ref="zoneTexte" v-model="texte" spellcheck="false"
          placeholder="Collez ou rédigez le document HTML de la convention, ou partez du modèle par défaut."
          class="q-input font-mono !text-xs leading-relaxed h-[60vh]"></textarea>
        <p class="q-hint">Les valeurs insérées à la place des balises sont échappées : le texte saisi par un étudiant ne peut pas modifier la mise en page.</p>
      </Card>

      <div class="xl:col-span-5 space-y-6">
        <Card :title="`Balises (${balises.length})`" icon="pi pi-tags" color="teal">
          <template #header-actions>
            <input v-model="filtreBalise" type="search" placeholder="Filtrer…" aria-label="Filtrer les balises" class="q-input q-input-sm w-36" />
          </template>
          <div class="flex flex-wrap gap-1.5 max-h-48 overflow-y-auto">
            <Button v-for="b in balisesFiltrees" :key="b" :label="b" size="small" severity="secondary" outlined class="!font-mono"
              v-tooltip.top="'Insérer à la position du curseur'" @click="insererBalise(b)" />
          </div>
        </Card>

        <Card title="Aperçu" icon="pi pi-eye" color="teal" :subtitle="apercuDossier ? `Avec les données du dossier de ${apercuDossier}` : ''">
          <template #header-actions>
            <Button label="Actualiser" icon="pi pi-refresh" size="small" severity="secondary" :loading="apercuEnCours" :disabled="!texte.trim()" @click="actualiserApercu" />
          </template>
          <!-- Document isolé : aucun script, aucun accès à l'application -->
          <iframe v-if="apercu" :srcdoc="apercu" sandbox="" title="Aperçu de la convention" class="w-full h-[55vh] bg-white rounded-lg border border-slate-200 dark:border-slate-700"></iframe>
          <EmptyState v-else compact icon="pi pi-eye" title="Pas encore d'aperçu" description="Actualisez pour voir le modèle rempli avec un vrai dossier." />
        </Card>
      </div>
    </div>
  </div>
</template>
