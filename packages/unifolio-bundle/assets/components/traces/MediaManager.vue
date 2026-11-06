<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ProgressBar from 'primevue/progressbar';
import MediaPreview from './MediaPreview.vue';
import { estIntegrable, estUrl } from './options';
import traceService, { messageErreur } from '../../requests/traceService';

/**
 * Preuves d'une trace : dépôt de plusieurs fichiers (glisser-déposer, sélection, collage),
 * liens, textes et extraits de code ; type modifiable après coup.
 * `assurerTrace` crée le brouillon à la volée si la trace n'existe pas encore et renvoie son uuid.
 */
const props = defineProps({
  uuid: { type: String, default: null },
  medias: { type: Array, default: () => [] },
  modifiable: { type: Boolean, default: true },
  assurerTrace: { type: Function, required: true },
});
const emit = defineEmits(['change', 'premier-fichier']);

const toast = useToast();
const confirm = useConfirm();

const liste = ref([...props.medias]);
watch(() => props.medias, (medias) => { liste.value = [...medias]; });

const survol = ref(false);
const envois = ref([]); // { id, nom, progression, erreur }
const champFichier = ref(null);
const nouvelleUrl = ref('');
const saisie = ref(null); // { type: 'texte'|'code', contenu, langage }
const occupe = computed(() => envois.value.some((e) => e.erreur === null && e.progression < 100));

const erreur = (summary, e) => toast.add({ severity: 'error', summary, detail: e?.response ? messageErreur(e) : (e?.message ?? String(e)), life: 7000 });

// ---------- Fichiers ----------

const deposerFichiers = async (fichiers) => {
  const liste_ = [...fichiers];
  if (!liste_.length || !props.modifiable) return;
  emit('premier-fichier', liste_[0]);

  let uuid;
  try {
    uuid = await props.assurerTrace(liste_[0]);
  } catch (e) {
    erreur('Trace non créée', e);
    return;
  }

  for (const fichier of liste_) {
    envois.value.push({ id: `${Date.now()}-${Math.random()}`, nom: fichier.name, progression: 0, erreur: null });
    // Référence réactive (proxy) pour que la progression s'affiche.
    const envoi = envois.value[envois.value.length - 1];
    try {
      await traceService.ajouterFichier(uuid, fichier, { onProgress: (p) => { envoi.progression = p; } });
      envoi.progression = 100;
      envois.value = envois.value.filter((e) => e.id !== envoi.id);
    } catch (e) {
      envoi.erreur = messageErreur(e, 'Envoi impossible.');
    }
  }
  emit('change');
};

const surDrop = (event) => {
  survol.value = false;
  if (!props.modifiable) return;
  const fichiers = event.dataTransfer?.files;
  if (fichiers?.length) {
    deposerFichiers(fichiers);
    return;
  }
  const texte = event.dataTransfer?.getData('text/uri-list') || event.dataTransfer?.getData('text/plain');
  if (texte && estUrl(texte.split('\n')[0])) ajouterUrl(texte.split('\n')[0].trim());
};

const surCollage = (event) => {
  if (!props.modifiable) return;
  const fichiers = [...(event.clipboardData?.files ?? [])];
  if (fichiers.length) {
    event.preventDefault();
    deposerFichiers(fichiers);
  }
};

const surSelection = (event) => {
  deposerFichiers(event.target.files ?? []);
  event.target.value = '';
};

const retirerEnvoi = (id) => { envois.value = envois.value.filter((e) => e.id !== id); };

// ---------- Liens ----------

const ajouterUrl = async (url = nouvelleUrl.value.trim()) => {
  if (!estUrl(url)) {
    toast.add({ severity: 'warn', summary: 'Adresse invalide', detail: 'Saisissez une adresse commençant par http:// ou https://', life: 4000 });
    return;
  }
  try {
    const uuid = await props.assurerTrace();
    await traceService.ajouterMedia(uuid, { type: estIntegrable(url) ? 'embed' : 'lien', url });
    nouvelleUrl.value = '';
    emit('change');
  } catch (e) {
    erreur('Lien non ajouté', e);
  }
};

// ---------- Texte / code ----------

const ouvrirSaisie = (type) => { saisie.value = { type, contenu: '', langage: '' }; };

const ajouterSaisie = async () => {
  if (!saisie.value?.contenu.trim()) return;
  try {
    const uuid = await props.assurerTrace();
    await traceService.ajouterMedia(uuid, { type: saisie.value.type, contenu: saisie.value.contenu, langage: saisie.value.langage || null });
    saisie.value = null;
    emit('change');
  } catch (e) {
    erreur('Contenu non ajouté', e);
  }
};

// ---------- Preuves existantes ----------

const modifier = async (media, champs, rafraichir = false) => {
  try {
    await traceService.modifierMedia(props.uuid, media.id, champs);
    if (rafraichir) emit('change');
  } catch (e) {
    erreur('Modification non enregistrée', e);
    emit('change');
  }
};

const supprimer = (media) => {
  confirm.require({
    message: 'Supprimer définitivement cette preuve ?',
    header: 'Supprimer la preuve',
    icon: 'pi pi-trash',
    acceptProps: { label: 'Supprimer', severity: 'danger' },
    rejectProps: { label: 'Annuler', severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await traceService.supprimerMedia(props.uuid, media.id);
        emit('change');
      } catch (e) {
        erreur('Suppression impossible', e);
      }
    },
  });
};

const reordonner = async () => {
  try {
    await traceService.ordonnerMedias(props.uuid, liste.value.map((m) => m.id));
  } catch (e) {
    erreur('Ordre non enregistré', e);
    emit('change');
  }
};
</script>

<template>
  <div class="flex flex-col gap-4">
    <div
      v-if="modifiable"
      class="rounded-xl border-2 border-dashed p-6 flex flex-col items-center gap-3 text-center transition-colors outline-none focus-visible:ring-2"
      :class="survol ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950' : 'border-surface-300'"
      tabindex="0"
      role="region"
      aria-label="Zone de dépôt des preuves : glissez des fichiers ou collez une capture"
      @dragenter.prevent="survol = true"
      @dragover.prevent="survol = true"
      @dragleave.prevent="survol = false"
      @drop.prevent="surDrop"
      @paste="surCollage"
    >
      <i class="pi pi-cloud-upload text-4xl text-emerald-600" />
      <div>
        <p class="font-medium m-0">Glissez vos fichiers ici</p>
        <p class="text-sm text-muted-color m-0">
          Images, PDF, vidéos, archives, documents… plusieurs à la fois. Le type est reconnu automatiquement.
        </p>
      </div>
      <div class="flex flex-wrap justify-center gap-2">
        <Button label="Choisir des fichiers" icon="pi pi-folder-open" size="small" @click="champFichier?.click()" />
        <Button label="Texte" icon="pi pi-align-left" size="small" severity="secondary" outlined @click="ouvrirSaisie('texte')" />
        <Button label="Code" icon="pi pi-code" size="small" severity="secondary" outlined @click="ouvrirSaisie('code')" />
      </div>
      <input ref="champFichier" type="file" multiple class="hidden" @change="surSelection" />
      <div class="flex w-full max-w-xl gap-2">
        <InputText
          v-model="nouvelleUrl"
          placeholder="…ou collez un lien (site, YouTube, Figma, dépôt Git…)"
          class="flex-1"
          aria-label="Ajouter un lien"
          @keydown.enter.prevent="ajouterUrl()"
        />
        <Button icon="pi pi-link" label="Ajouter" size="small" :disabled="!nouvelleUrl.trim()" @click="ajouterUrl()" />
      </div>
      <small class="text-muted-color">Astuce : cliquez dans cette zone puis collez (Ctrl/Cmd + V) une capture d'écran.</small>
    </div>

    <div v-if="saisie" class="border border-surface-200 rounded-lg p-3 flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <span class="font-medium">{{ saisie.type === 'code' ? 'Extrait de code' : 'Texte' }}</span>
        <Button icon="pi pi-times" text rounded size="small" aria-label="Annuler" @click="saisie = null" />
      </div>
      <InputText v-if="saisie.type === 'code'" v-model="saisie.langage" placeholder="Langage (ex. php, js, python)" size="small" />
      <Textarea v-model="saisie.contenu" rows="6" auto-resize :class="saisie.type === 'code' ? 'font-mono text-sm' : ''" />
      <Button label="Ajouter" icon="pi pi-check" size="small" class="self-end" :disabled="!saisie.contenu.trim()" @click="ajouterSaisie" />
    </div>

    <div v-for="envoi in envois" :key="envoi.id" class="flex items-center gap-3 text-sm">
      <i :class="envoi.erreur ? 'pi pi-exclamation-triangle text-red-500' : 'pi pi-upload'" />
      <span class="truncate flex-1">{{ envoi.nom }}</span>
      <ProgressBar v-if="!envoi.erreur" :value="envoi.progression" class="w-40" style="height: 6px" :show-value="false" />
      <template v-else>
        <span class="text-red-600">{{ envoi.erreur }}</span>
        <Button icon="pi pi-times" text rounded size="small" aria-label="Masquer" @click="retirerEnvoi(envoi.id)" />
      </template>
    </div>

    <p v-if="liste.length === 0 && !occupe && !modifiable" class="text-muted-color text-sm m-0">Aucune preuve.</p>

    <draggable v-model="liste" handle=".poignee" :disabled="!modifiable" class="grid gap-3 md:grid-cols-2" @end="reordonner">
      <div v-for="media in liste" :key="media.id" class="border border-surface-200 rounded-lg p-3 flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <i v-if="modifiable" class="pi pi-bars poignee cursor-move text-muted-color" title="Déplacer" />
          <Select
            v-if="modifiable && media.typesPossibles?.length > 1"
            :model-value="media.type"
            :options="media.typesPossibles"
            option-label="libelle"
            option-value="value"
            size="small"
            class="flex-1"
            aria-label="Type de preuve"
            @update:model-value="(t) => modifier(media, { type: t }, true)"
          >
            <template #value="{ value }">
              <span class="flex items-center gap-2"><i :class="media.typesPossibles.find((o) => o.value === value)?.icone" /> {{ media.typesPossibles.find((o) => o.value === value)?.libelle }}</span>
            </template>
            <template #option="{ option }">
              <span class="flex items-center gap-2"><i :class="option.icone" /> {{ option.libelle }}</span>
            </template>
          </Select>
          <span v-else class="flex-1 flex items-center gap-2 text-sm text-muted-color"><i :class="media.icone" /> {{ media.libelleType }}</span>
          <Button v-if="modifiable" icon="pi pi-trash" severity="danger" text rounded size="small" aria-label="Supprimer la preuve" @click="supprimer(media)" />
        </div>

        <MediaPreview v-if="!modifiable || media.fichier" :media="media" compact />

        <template v-if="modifiable">
          <InputText
            v-if="media.url"
            v-model="media.url"
            size="small"
            aria-label="Adresse"
            @change="modifier(media, { url: media.url }, true)"
          />
          <template v-if="['texte', 'code'].includes(media.type)">
            <InputText
              v-if="media.type === 'code'"
              :model-value="media.metadata?.langage ?? ''"
              placeholder="Langage"
              size="small"
              @change="(e) => modifier(media, { langage: e.target.value || null }, true)"
            />
            <Textarea
              v-model="media.contenu"
              rows="4"
              auto-resize
              :class="media.type === 'code' ? 'font-mono text-sm' : ''"
              aria-label="Contenu"
              @change="modifier(media, { contenu: media.contenu })"
            />
          </template>
          <InputText v-model="media.legende" placeholder="Légende (facultatif)" size="small" aria-label="Légende" @change="modifier(media, { legende: media.legende })" />
        </template>
      </div>
    </draggable>
  </div>
</template>
