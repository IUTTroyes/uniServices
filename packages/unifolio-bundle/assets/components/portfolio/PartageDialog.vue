<script setup>
import { Alert, LoadingBar } from '@components';
import { showSuccess, showWarn } from '@helpers/toast';
import { formatDateFr } from '@helpers/date';
import { computed, reactive, ref, watch } from 'vue';
import { useConfirmation } from '@composables/useConfirmation';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import RadioButton from 'primevue/radiobutton';
import Tag from 'primevue/tag';
import { portfolioService, messagesErreur } from '../../requests/portfolioService';

const props = defineProps({
  visible: { type: Boolean, default: false },
  portfolio: { type: Object, required: true },
  /** Des modifications de page ne sont pas encore enregistrées : elles ne seront pas publiées. */
  modifsEnAttente: { type: Boolean, default: false },
});
const emit = defineEmits(['update:visible', 'change']);

const { confirmer } = useConfirmation();
const etat = ref(null);
const chargement = ref(false);
const enregistrement = ref(false);
const publication = ref(false);
const erreurs = ref([]);
const form = reactive({ visibilite: 'prive', slug: '', indexable: false, description: '' });

const explications = {
  prive: 'Vous seul·e (et vos évaluateurs pour un portfolio académique) voyez ce portfolio.',
  etablissement: 'Tout compte connecté de l\'établissement peut le consulter.',
  lien_secret: 'Toute personne disposant du lien peut le consulter ; il n\'est pas référencé.',
  public: 'Visible par tout le monde sur Internet, à une adresse que vous choisissez.',
};
const icones = { prive: 'pi pi-lock', etablissement: 'pi pi-building', lien_secret: 'pi pi-link', public: 'pi pi-globe' };

const verser = (e) => {
  etat.value = e;
  Object.assign(form, { visibilite: e.visibilite.value, slug: e.slug ?? '', indexable: e.indexable, description: e.description ?? '' });
};

const charger = async () => {
  chargement.value = true;
  erreurs.value = [];
  try {
    const { data } = await portfolioService.partage(props.portfolio.uuid);
    verser(data);
  } catch (e) {
    erreurs.value = messagesErreur(e, 'Impossible de charger les réglages de partage.');
  } finally {
    chargement.value = false;
  }
};
watch(() => props.visible, (v) => { if (v) charger(); }, { immediate: true });

const modifie = computed(() => etat.value && (
  form.visibilite !== etat.value.visibilite.value
  || (form.visibilite === 'public' && (form.slug || '') !== (etat.value.slug ?? ''))
  || form.indexable !== etat.value.indexable
  || (form.description || '') !== (etat.value.description ?? '')
));
const actif = computed(() => etat.value?.publicationActive ?? null);
const url = computed(() => etat.value?.url ?? null);

const appliquer = async (extra = {}) => {
  enregistrement.value = true;
  erreurs.value = [];
  try {
    const charge = { visibilite: form.visibilite, description: form.description, indexable: form.visibilite === 'public' && form.indexable, ...extra };
    if (form.visibilite === 'public' && form.slug) charge.slug = form.slug;
    const { data } = await portfolioService.modifierPartage(props.portfolio.uuid, charge);
    verser(data);
    emit('change');
    return true;
  } catch (e) {
    erreurs.value = messagesErreur(e);
    return false;
  } finally {
    enregistrement.value = false;
  }
};

const publier = async () => {
  if (modifie.value && !(await appliquer())) return;
  publication.value = true;
  erreurs.value = [];
  try {
    const { data } = await portfolioService.publier(props.portfolio.uuid);
    verser(data);
    emit('change');
    showSuccess(`Version ${data.publicationActive.numero} en ligne.`, 'Portfolio publié', 4000);
  } catch (e) {
    erreurs.value = messagesErreur(e, 'La publication a échoué.');
  } finally {
    publication.value = false;
  }
};

const depublier = () => confirmer({
  header: 'Retirer la publication',
  message: 'Le portfolio ne sera plus accessible à l\'adresse partagée. Vous pourrez le republier à tout moment.',
  icon: 'pi pi-eye-slash',
  libelle: 'Retirer',
  danger: true,
  accept: async () => {
    try {
      const { data } = await portfolioService.depublier(props.portfolio.uuid);
      verser(data);
      emit('change');
    } catch (e) {
      erreurs.value = messagesErreur(e);
    }
  },
});

const regenerer = () => confirmer({
  header: 'Régénérer le lien',
  message: 'L\'ancien lien cessera immédiatement de fonctionner. Les personnes à qui vous l\'avez donné devront recevoir le nouveau.',
  icon: 'pi pi-refresh',
  libelle: 'Régénérer',
  accept: () => appliquer({ regenererLien: true }),
});

const copier = async () => {
  try {
    await navigator.clipboard.writeText(url.value);
    showSuccess(undefined, 'Lien copié', 2000);
  } catch {
    showWarn('Sélectionnez le lien et copiez-le manuellement.', 'Copie impossible', 4000);
  }
};

const date = (iso) => formatDateFr(iso, 'moyenneHeure', '');
</script>

<template>
  <Dialog
    :visible="visible"
    header="Partager et publier"
    modal
    :style="{ width: '42rem' }"
    :breakpoints="{ '768px': '95vw' }"
    @update:visible="emit('update:visible', $event)"
  >
    <LoadingBar v-if="chargement" />
    <div v-else-if="etat" class="flex flex-col gap-5">
      <Alert icon-native v-if="erreurs.length" severity="error" :closable="true" @close="erreurs = []">
        <ul class="m-0 pl-4 flex flex-col gap-1"><li v-for="e in erreurs" :key="e">{{ e }}</li></ul>
      </Alert>
      <Alert icon-native v-if="modifsEnAttente" severity="warn" :closable="false">
        Des modifications de la page en cours ne sont pas enregistrées : elles ne seront pas incluses dans la publication.
      </Alert>
      <Alert icon-native v-if="actif?.moderee" severity="error" :closable="false">
        <strong>Cette publication a été retirée par la modération.</strong> {{ actif.motifModeration }}
      </Alert>

      <!-- Qui peut voir -->
      <fieldset class="border-0 p-0 m-0 flex flex-col gap-2">
        <legend class="font-semibold mb-1">Qui peut voir ce portfolio ?</legend>
        <label
          v-for="v in etat.visibilitesAutorisees"
          :key="v.value"
          class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer"
          :class="form.visibilite === v.value ? 'border-emerald-500 bg-emerald-50/50' : 'border-surface-200'"
        >
          <RadioButton v-model="form.visibilite" :value="v.value" name="visibilite" class="mt-1" />
          <span class="flex flex-col">
            <span class="font-medium"><i :class="icones[v.value]" class="mr-1" /> {{ v.libelle }}</span>
            <span class="text-sm text-muted-color">{{ explications[v.value] }}</span>
          </span>
        </label>
      </fieldset>

      <div v-if="form.visibilite === 'public'" class="flex flex-col gap-3">
        <div class="flex flex-col gap-1">
          <label for="pf-slug" class="font-medium">Adresse</label>
          <InputText id="pf-slug" v-model="form.slug" placeholder="prenom-nom" maxlength="58" />
          <small class="text-muted-color">3 à 58 caractères : lettres minuscules, chiffres, tirets. Laissez vide pour en générer une.</small>
        </div>
        <label class="flex items-center gap-3 cursor-pointer">
          <ToggleSwitch v-model="form.indexable" />
          <span>Autoriser les moteurs de recherche à référencer ce portfolio</span>
        </label>
      </div>

      <div v-if="form.visibilite !== 'prive'" class="flex flex-col gap-1">
        <label for="pf-desc" class="font-medium">Description <span class="text-muted-color font-normal">(aperçu lors d'un partage)</span></label>
        <Textarea id="pf-desc" v-model="form.description" rows="2" maxlength="300" auto-resize />
      </div>

      <div class="flex justify-end">
        <Button label="Enregistrer les réglages" icon="pi pi-save" severity="secondary" outlined :disabled="!modifie" :loading="enregistrement" @click="appliquer()" />
      </div>

      <!-- Publication -->
      <section class="rounded-lg bg-surface-50 p-4 flex flex-col gap-3">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="m-0 text-base font-semibold flex-1">Publication</h3>
          <Tag v-if="actif && !actif.moderee" :value="`Version ${actif.numero} en ligne`" severity="success" icon="pi pi-check" />
          <Tag v-else-if="actif?.moderee" value="Retirée" severity="danger" />
          <Tag v-else value="Non publié" severity="secondary" />
        </div>
        <p v-if="form.visibilite === 'prive'" class="m-0 text-sm text-muted-color">Choisissez d'abord qui peut voir ce portfolio : un portfolio privé n'est pas publié.</p>
        <p v-else class="m-0 text-sm text-muted-color">
          Publier fige une copie du contenu enregistré : le public voit cette version jusqu'à votre prochaine publication.
        </p>
        <div v-if="url && actif" class="flex gap-2">
          <InputText :model-value="url" readonly class="flex-1" aria-label="Adresse de partage" @focus="$event.target.select()" />
          <Button icon="pi pi-copy" severity="secondary" outlined aria-label="Copier le lien" @click="copier" />
          <a :href="url" target="_blank" rel="noopener"><Button icon="pi pi-external-link" severity="secondary" outlined aria-label="Ouvrir" /></a>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            :label="actif ? 'Publier une nouvelle version' : 'Publier'"
            icon="pi pi-send"
            :disabled="form.visibilite === 'prive' || !etat.renduDisponible"
            :loading="publication"
            @click="publier"
          />
          <Button v-if="actif" label="Retirer" icon="pi pi-eye-slash" severity="danger" outlined @click="depublier" />
          <Button v-if="form.visibilite === 'lien_secret' && url" label="Régénérer le lien" icon="pi pi-refresh" severity="secondary" outlined @click="regenerer" />
        </div>
        <small v-if="!etat.renduDisponible" class="text-red-600">Le service de rendu n'est pas installé sur ce serveur : la publication est indisponible.</small>
      </section>

      <!-- Historique et exports -->
      <section v-if="etat.historique.length" class="flex flex-col gap-2">
        <h3 class="m-0 text-base font-semibold">Versions</h3>
        <ul class="list-none m-0 p-0 flex flex-col gap-1 text-sm">
          <li v-for="h in etat.historique" :key="h.uuid" class="flex flex-wrap items-center gap-2">
            <span class="font-medium">Version {{ h.numero }}</span>
            <span class="text-muted-color">{{ date(h.genereLe ?? h.creeLe) }}</span>
            <Tag v-if="h.active" value="En ligne" severity="success" />
            <Tag v-if="h.statut.value === 'erreur'" value="Échec" severity="danger" :title="h.erreur" />
          </li>
        </ul>
        <div class="flex flex-wrap gap-2 mt-1">
          <a :href="portfolioService.exportZip(portfolio.uuid)"><Button label="Archive ZIP" icon="pi pi-download" severity="secondary" outlined size="small" /></a>
          <a :href="portfolioService.exportPdf(portfolio.uuid)"><Button label="Document PDF" icon="pi pi-file-pdf" severity="secondary" outlined size="small" /></a>
          <a :href="etat.apercu" target="_blank" rel="noopener"><Button label="Voir la dernière version" icon="pi pi-eye" severity="secondary" outlined size="small" /></a>
        </div>
      </section>
    </div>
    <template #footer>
      <Button label="Fermer" severity="secondary" outlined @click="emit('update:visible', false)" />
    </template>
  </Dialog>
</template>
