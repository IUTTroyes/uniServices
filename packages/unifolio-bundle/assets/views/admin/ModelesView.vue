<script setup>
import { showDanger } from '@helpers/toast';
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState, Card, LoadingBar } from '@components';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import { modeleService, messagesErreur } from '../../requests/portfolioService';
import campagneService from '../../requests/campagneService';

const router = useRouter();
const modeles = ref([]);
const formations = ref([]);
const chargement = ref(true);
const dialogue = ref(false);
const envoi = ref(false);
const form = reactive({ libelle: '', description: '', portee: 'departement', diplome: null });

const charger = async () => {
  try {
    const [{ data: liste }, { data: options }] = await Promise.all([modeleService.liste(), campagneService.options()]);
    modeles.value = liste;
    formations.value = options.formations;
  } catch (e) {
    showDanger(messagesErreur(e).join(' '), 'Chargement impossible', 6000);
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const ouvrir = () => {
  Object.assign(form, { libelle: '', description: '', portee: 'departement', diplome: null });
  dialogue.value = true;
};

const creer = async () => {
  envoi.value = true;
  try {
    const { data } = await modeleService.creer(form);
    dialogue.value = false;
    router.push(`/unifolio/admin/modeles/${data.uuid}`);
  } catch (e) {
    showDanger(messagesErreur(e).join(' '), 'Création impossible', 7000);
  } finally {
    envoi.value = false;
  }
};

const adapter = async (m) => {
  try {
    const { data } = await modeleService.dupliquer(m.uuid, { portee: 'departement', libelle: `${m.libelle} (département)` });
    router.push(`/unifolio/admin/modeles/${data.uuid}`);
  } catch (e) {
    showDanger(messagesErreur(e).join(' '), 'Copie impossible', 7000);
  }
};

const severite = { national: 'secondary', departement: 'info', diplome: 'success' };
const libellePortee = { national: 'National', departement: 'Département', diplome: 'Formation' };
</script>

<template>
  <HeaderComponent
    icon="pi pi-clone"
    titre="Modèles de portfolio"
    description="Pages et blocs de départ d'un portfolio académique, dont certains peuvent être imposés aux étudiants."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Button label="Nouveau modèle" icon="pi pi-plus" @click="ouvrir" />
    </template>
  </HeaderComponent>

  <LoadingBar v-if="chargement" />
  <EmptyState
    v-else-if="modeles.length === 0"
    icon="pi pi-clone"
    color="emerald"
    title="Aucun modèle"
    description="Sans modèle, un portfolio académique reçoit la structure par défaut : une page de présentation, une page par compétence de l'année et un CV."
  />

  <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <Card bare v-for="m in modeles" :key="m.uuid" class="p-4 m-0 flex flex-col gap-3" :class="{ 'opacity-60': !m.actif }">
      <div class="flex items-start justify-between gap-2">
        <span class="font-semibold">{{ m.libelle }}</span>
        <Tag :value="libellePortee[m.portee]" :severity="severite[m.portee]" />
      </div>
      <p v-if="m.description" class="m-0 text-sm text-muted-color">{{ m.description }}</p>
      <div class="flex flex-wrap gap-2 text-xs">
        <Tag :value="`${m.nbPages} page(s)`" severity="secondary" />
        <Tag :value="`${m.nbCampagnes} campagne(s)`" :severity="m.nbCampagnes ? 'success' : 'secondary'" />
        <Tag v-if="!m.actif" value="Désactivé" severity="warn" />
        <span class="text-muted-color">{{ m.libellePortee }}</span>
      </div>
      <div class="flex gap-2 mt-auto">
        <Button :label="m.modifiable ? 'Modifier' : 'Consulter'" :icon="m.modifiable ? 'pi pi-pencil' : 'pi pi-eye'" size="small" outlined @click="router.push(`/unifolio/admin/modeles/${m.uuid}`)" />
        <Button v-if="!m.modifiable" label="Adapter pour mon département" icon="pi pi-copy" size="small" text @click="adapter(m)" />
      </div>
    </Card>
  </div>

  <Dialog v-model:visible="dialogue" header="Nouveau modèle" modal :style="{ width: '34rem' }" :breakpoints="{ '640px': '95vw' }">
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-1">
        <label for="m-libelle" class="font-medium">Libellé</label>
        <InputText id="m-libelle" v-model="form.libelle" />
      </div>
      <div class="flex flex-col gap-1">
        <span class="font-medium">S'applique à</span>
        <SelectButton v-model="form.portee" :options="[{ v: 'departement', l: 'Mon département' }, { v: 'diplome', l: 'Une formation' }]" option-label="l" option-value="v" :allow-empty="false" />
        <Select v-if="form.portee === 'diplome'" v-model="form.diplome" :options="formations" option-label="libelle" option-value="id" filter placeholder="Choisir la formation" class="mt-2" />
      </div>
      <div class="flex flex-col gap-1">
        <label for="m-desc" class="font-medium">Description</label>
        <Textarea id="m-desc" v-model="form.description" rows="3" auto-resize />
      </div>
    </div>
    <template #footer>
      <Button label="Annuler" severity="secondary" outlined @click="dialogue = false" />
      <Button label="Créer" icon="pi pi-check" :loading="envoi" :disabled="!form.libelle.trim() || (form.portee === 'diplome' && !form.diplome)" @click="creer" />
    </template>
  </Dialog>
</template>
