<script setup>
/**
 * Modèle de convention de stage (super administrateur).
 * La convention PDF est générée depuis ce modèle s'il est enregistré ; sinon, depuis le gabarit intégré au package.
 * Le modèle est un document HTML ; les balises « {objet.champ} » sont remplacées par les données du dossier.
 */
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import api from '@helpers/axios';
import { getStageTemplatesService, updateStageTemplateService, createStageTemplateService } from '@/requests/stage_template';
import { messageErreurApi } from '@/utils/erreurApi';

const CODE = 'default_convention';
const toast = useToast();

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

const charger = async () => {
  chargement.value = true;
  try {
    const [modeles, listeBalises] = await Promise.all([
      getStageTemplatesService(),
      api.get('/api/stage/modele-convention/balises').then(r => r.data)
    ]);
    balises.value = listeBalises || [];
    modele.value = (modeles || []).find(m => m.code === CODE) || null;
    texte.value = modele.value?.texte || '';
    texteEnregistre.value = texte.value;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de charger le modèle.'), life: 5000 });
  } finally {
    chargement.value = false;
  }
};

const partirDuModeleParDefaut = async () => {
  if (texte.value.trim() && !confirm('Remplacer le texte en cours par le modèle livré avec l\'application ?')) return;
  try {
    const { data } = await api.get('/api/stage/modele-convention/defaut');
    texte.value = data.texte || '';
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de charger le modèle par défaut.'), life: 5000 });
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
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible d\'enregistrer le modèle.'), life: 5000 });
  } finally {
    enregistrement.value = false;
  }
};

const supprimer = async () => {
  if (!modele.value?.id || !confirm('Supprimer le modèle ? Les conventions seront de nouveau générées avec le gabarit intégré.')) return;
  try {
    await api.delete(`/api/stage_convention_templates/${modele.value.id}`);
    modele.value = null;
    texte.value = '';
    texteEnregistre.value = '';
    apercu.value = '';
    toast.add({ severity: 'info', summary: 'Modèle supprimé', detail: 'Le gabarit intégré est de nouveau utilisé.', life: 3000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de supprimer le modèle.'), life: 5000 });
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
    const { data } = await api.post('/api/stage/modele-convention/apercu', { texte: texte.value });
    apercu.value = data.html;
    apercuDossier.value = data.dossier;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Aperçu impossible', detail: messageErreurApi(error, 'Impossible de construire l\'aperçu.'), life: 5000 });
  } finally {
    apercuEnCours.value = false;
  }
};

onMounted(charger);
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <div class="flex flex-wrap items-center gap-4 justify-between border-b border-slate-200 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-cog text-teal-600"></i>
          <span>Modèle de convention de stage</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          <template v-if="modele">Les conventions sont générées avec <strong>ce modèle</strong>.</template>
          <template v-else>Aucun modèle enregistré : les conventions sont générées avec le <strong>gabarit intégré</strong>.</template>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button label="Partir du modèle par défaut" icon="pi pi-file-import" severity="secondary" outlined @click="partirDuModeleParDefaut" />
        <Button v-if="modele" label="Supprimer le modèle" icon="pi pi-trash" severity="danger" outlined @click="supprimer" />
        <Button label="Enregistrer" icon="pi pi-save" :loading="enregistrement" :disabled="!modifie" @click="enregistrer" />
      </div>
    </div>

    <div v-if="chargement" class="text-xs text-slate-400">Chargement…</div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-12 gap-6">
      <div class="xl:col-span-7 space-y-3">
        <label for="modele-texte" class="text-xs font-bold text-slate-600 dark:text-slate-300">
          Modèle (HTML){{ modifie ? ' — modifications non enregistrées' : '' }}
        </label>
        <textarea id="modele-texte" ref="zoneTexte" v-model="texte" spellcheck="false"
          placeholder="Collez ou rédigez le document HTML de la convention, ou partez du modèle par défaut."
          class="w-full h-[60vh] p-3 font-mono text-[11px] leading-relaxed border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"></textarea>
        <p class="text-[11px] text-slate-400">
          Les valeurs insérées à la place des balises sont échappées : le texte saisi par un étudiant ne peut pas modifier la mise en page.
        </p>
      </div>

      <div class="xl:col-span-5 space-y-3">
        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 space-y-2">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-200">Balises ({{ balises.length }})</h2>
            <input v-model="filtreBalise" type="search" placeholder="Filtrer…" aria-label="Filtrer les balises"
              class="p-1.5 text-xs border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 w-36" />
          </div>
          <div class="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto">
            <button v-for="b in balisesFiltrees" :key="b" type="button" @click="insererBalise(b)"
              class="px-2 py-0.5 rounded-md font-mono text-[10px] bg-teal-50 dark:bg-teal-950/40 text-teal-800 dark:text-teal-300 border border-teal-200 dark:border-teal-800 hover:bg-teal-100">
              {{ b }}
            </button>
          </div>
        </div>

        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-3 space-y-2">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-200">Aperçu</h2>
            <Button label="Actualiser" icon="pi pi-refresh" size="small" severity="secondary" :loading="apercuEnCours" :disabled="!texte.trim()" @click="actualiserApercu" />
          </div>
          <p v-if="apercuDossier" class="text-[11px] text-slate-400">Avec les données du dossier de {{ apercuDossier }}.</p>
          <!-- Document isolé : aucun script, aucun accès à l'application -->
          <iframe v-if="apercu" :srcdoc="apercu" sandbox="" title="Aperçu de la convention"
            class="w-full h-[55vh] bg-white rounded-lg border border-slate-200 dark:border-slate-700"></iframe>
          <p v-else class="text-[11px] text-slate-400">Cliquez sur « Actualiser » pour voir le modèle rempli avec un vrai dossier.</p>
        </div>
      </div>
    </div>
  </div>
</template>
