<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import adminService from '../../requests/adminService';
import { messageErreur } from '../../requests/traceService';
import { GRANULARITES, PORTEES, formatDate } from '../../components/grilles/options';

const router = useRouter();
const toast = useToast();
const grilles = ref([]);
const formations = ref([]);
const chargement = ref(true);
const filtre = ref(null);

const dialogue = ref(false);
const envoi = ref(false);
const form = reactive({ libelle: '', granularite: 'ac', portee: 'departement', diplome: null, description: '' });

const charger = async () => {
  chargement.value = true;
  try {
    const [{ data: g }, { data: f }] = await Promise.all([adminService.grilles(), adminService.formations()]);
    grilles.value = g;
    formations.value = f.formations;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Chargement impossible', detail: messageErreur(e), life: 6000 });
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const visibles = computed(() => (filtre.value ? grilles.value.filter((g) => g.granularite.value === filtre.value) : grilles.value));

const ouvrir = () => {
  Object.assign(form, { libelle: '', granularite: 'ac', portee: 'departement', diplome: null, description: '' });
  dialogue.value = true;
};

const creer = async () => {
  envoi.value = true;
  try {
    const { data } = await adminService.creerGrille(form);
    dialogue.value = false;
    router.push(`/unifolio/admin/grilles/${data.uuid}`);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Création impossible', detail: messageErreur(e), life: 7000 });
  } finally {
    envoi.value = false;
  }
};

const adapter = async (grille) => {
  try {
    const { data } = await adminService.dupliquer(grille.uuid, { portee: 'departement', libelle: `${grille.libelle} (département)` });
    toast.add({ severity: 'success', summary: 'Copie créée', detail: 'Adaptez-la puis publiez-la.', life: 4000 });
    router.push(`/unifolio/admin/grilles/${data.uuid}`);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Copie impossible', detail: messageErreur(e), life: 7000 });
  }
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-table"
    titre="Grilles d'évaluation"
    description="Critères, échelle et versions. Une version publiée est figée : les évaluations y restent attachées."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Button label="Nouvelle grille" icon="pi pi-plus" @click="ouvrir" />
    </template>
  </HeaderComponent>

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />

  <div v-else class="flex flex-col gap-4">
    <SelectButton
      v-model="filtre"
      :options="GRANULARITES"
      option-label="label"
      option-value="value"
      aria-label="Filtrer par granularité"
    />

    <EmptyState
      v-if="visibles.length === 0"
      icon="pi pi-table"
      color="emerald"
      title="Aucune grille"
      description="Créez une grille ou demandez l'installation des grilles nationales (commande unifolio:grilles:init)."
    />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="g in visibles" :key="g.uuid" class="card p-4 m-0 flex flex-col gap-3" :class="{ 'opacity-60': !g.actif }">
        <div class="flex items-start justify-between gap-2">
          <span class="font-semibold">{{ g.libelle }}</span>
          <Tag :value="PORTEES[g.portee].label" :severity="PORTEES[g.portee].severity" :icon="PORTEES[g.portee].icon" />
        </div>
        <span class="text-sm text-muted-color">{{ g.granularite.libelle }} · {{ g.libellePortee }}</span>
        <div class="flex flex-wrap gap-2">
          <Tag v-if="g.versionPubliee" :value="`v${g.versionPubliee} publiée le ${formatDate(g.publieeLe)}`" severity="success" />
          <Tag v-if="g.brouillon" :value="`v${g.brouillon} en brouillon`" severity="warn" />
          <Tag v-if="!g.actif" value="Désactivée" severity="secondary" />
        </div>
        <div class="flex gap-2 mt-auto">
          <Button :label="g.modifiable ? 'Modifier' : 'Consulter'" :icon="g.modifiable ? 'pi pi-pencil' : 'pi pi-eye'" size="small" outlined @click="router.push(`/unifolio/admin/grilles/${g.uuid}`)" />
          <Button v-if="!g.modifiable" label="Adapter pour mon département" icon="pi pi-copy" size="small" text @click="adapter(g)" />
        </div>
      </div>
    </div>
  </div>

  <Dialog v-model:visible="dialogue" header="Nouvelle grille" modal :style="{ width: '36rem' }" :breakpoints="{ '640px': '95vw' }">
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-1">
        <label for="g-libelle" class="font-medium">Libellé</label>
        <InputText id="g-libelle" v-model="form.libelle" />
      </div>
      <div class="flex flex-col gap-1">
        <label for="g-gran" class="font-medium">Évalue</label>
        <Select id="g-gran" v-model="form.granularite" :options="GRANULARITES" option-label="label" option-value="value" />
      </div>
      <div class="flex flex-col gap-1">
        <span class="font-medium">Portée</span>
        <SelectButton
          v-model="form.portee"
          :options="[{ value: 'departement', label: 'Mon département' }, { value: 'diplome', label: 'Une formation' }]"
          option-label="label"
          option-value="value"
          :allow-empty="false"
        />
        <Select
          v-if="form.portee === 'diplome'"
          v-model="form.diplome"
          :options="formations"
          option-label="libelle"
          option-value="id"
          placeholder="Choisir la formation"
          class="mt-2"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label for="g-desc" class="font-medium">Description</label>
        <Textarea id="g-desc" v-model="form.description" rows="3" auto-resize />
      </div>
    </div>
    <template #footer>
      <Button label="Annuler" severity="secondary" outlined @click="dialogue = false" />
      <Button label="Créer" icon="pi pi-check" :loading="envoi" :disabled="!form.libelle.trim() || (form.portee === 'diplome' && !form.diplome)" @click="creer" />
    </template>
  </Dialog>
</template>
