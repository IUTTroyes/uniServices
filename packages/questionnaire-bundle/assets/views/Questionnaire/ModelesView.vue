<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ActionButtonVertical, Card, EmptyState, HeaderComponent, ListSkeleton } from '@components';
import { useUsersStore } from '@stores';
import { useConfirmation } from '@composables/useConfirmation';
import { createQuestionnaireService, deleteQuestionnaireService, getModelesService, instancierModeleService } from '@/requests/questionnaire_service';
import { useEtablissement } from '@/composables/useEtablissement';

/**
 * Bibliothèque de modèles : ceux des départements gérés et ceux de l'établissement (communs à tous les gestionnaires,
 * gérés par le super administrateur). Un modèle sert à créer les questionnaires de chaque campagne ; les campagnes d'un
 * même modèle se comparent d'une année sur l'autre.
 */
const router = useRouter();
const userStore = useUsersStore();
const { confirmer } = useConfirmation();
const { libelleGlobal } = useEtablissement();

const chargement = ref(true);
const modeles = ref<any[]>([]);
const choisi = ref<any>(null);
const titre = ref('');
const departement = ref<number | null>(null);
const creation = ref(false);

const departements = computed(() => (userStore.departements || []).map((d: any) => ({ label: d.libelle, value: d.id })));

async function charger() {
  chargement.value = true;
  try {
    const res = await getModelesService();
    modeles.value = res?.member || res || [];
  } finally {
    chargement.value = false;
  }
}

function utiliser(modele: any) {
  choisi.value = modele;
  titre.value = modele.title;
  departement.value = modele.departement?.id ?? userStore.departementDefaut?.id ?? null;
}

async function creerDepuisModele() {
  if (!choisi.value || !departement.value) return;
  creation.value = true;
  try {
    const res = await instancierModeleService(choisi.value.uuid, { title: titre.value, departement: departement.value });
    router.push({ name: 'questionnaire_builder', params: { id: res.uuid } });
  } catch (e) {
    // apiCall a affiché l'erreur.
  } finally {
    creation.value = false;
  }
}

async function nouveauModele() {
  const res = await createQuestionnaireService({
    title: 'Nouveau modèle',
    estModele: true,
    departement: userStore.departementDefaut?.id ? `/api/structure_departements/${userStore.departementDefaut.id}` : null,
  }, true);
  if (res?.uuid) router.push({ name: 'questionnaire_builder', params: { id: res.uuid } });
}

async function supprimer(modele: any) {
  const ok = await confirmer({
    titre: 'Supprimer le modèle',
    message: `Supprimer le modèle « ${modele.title} » ? Les questionnaires déjà créés à partir de lui sont conservés.`,
    libelle: 'Supprimer',
    danger: true,
  });
  if (!ok) return;
  await deleteQuestionnaireService(modele.uuid, true);
  await charger();
}

onMounted(charger);
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      titre="Modèles de questionnaires"
      description="Créez chaque campagne à partir d'un modèle : les résultats d'un même modèle se comparent d'une année sur l'autre."
      icon="pi pi-bookmark"
      color="green"
    >
      <template #actions>
        <ActionButtonVertical icon="pi pi-plus" label="Nouveau modèle" severity="primary" @click="nouveauModele" />
      </template>
    </HeaderComponent>

    <ListSkeleton v-if="chargement" />

    <EmptyState
      v-else-if="modeles.length === 0"
      icon="pi pi-bookmark"
      title="Aucun modèle"
      description="Créez un modèle, ou enregistrez un questionnaire existant comme modèle depuis la liste des questionnaires."
      action-label="Nouveau modèle"
      action-icon="pi pi-plus"
      @action="nouveauModele"
    />

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <Card v-for="modele in modeles" :key="modele.uuid" :title="modele.title" :subtitle="modele.description || ''" icon="pi pi-bookmark" color="green">
        <template #header-actions>
          <Tag :value="modele.departement?.libelle || libelleGlobal" :severity="modele.departement ? 'secondary' : 'info'" />
        </template>
        <div class="flex flex-wrap gap-2">
          <Button label="Utiliser" icon="pi pi-copy" size="small" @click="utiliser(modele)" />
          <Button label="Modifier" icon="pi pi-pencil" size="small" severity="secondary" outlined @click="router.push({ name: 'questionnaire_builder', params: { id: modele.uuid } })" />
          <Button label="Comparer les campagnes" icon="pi pi-chart-line" size="small" severity="secondary" text @click="router.push({ name: 'questionnaire_comparaison', params: { id: modele.uuid } })" />
          <Button icon="pi pi-trash" size="small" severity="danger" text :aria-label="`Supprimer le modèle ${modele.title}`" @click="supprimer(modele)" />
        </div>
      </Card>
    </div>

    <Dialog :visible="!!choisi" modal header="Créer un questionnaire depuis ce modèle" :style="{ width: '92vw', maxWidth: '520px' }" @update:visible="(v) => !v && (choisi = null)">
      <form class="q-form" @submit.prevent="creerDepuisModele">
        <label class="q-label" for="modele-titre">Titre du questionnaire</label>
        <input id="modele-titre" v-model="titre" class="q-input" maxlength="255" required />
        <label class="q-label" for="modele-departement">Département</label>
        <Select v-model="departement" input-id="modele-departement" :options="departements" option-label="label" option-value="value" placeholder="Choisir un département" class="w-full" />
        <p class="q-hint">Le questionnaire est créé en brouillon pour l'année universitaire en cours ; vous pouvez l'adapter avant de le publier.</p>
        <div class="q-actions">
          <Button label="Annuler" severity="secondary" outlined @click="choisi = null" />
          <Button type="submit" label="Créer" icon="pi pi-check" :loading="creation" :disabled="!departement" />
        </div>
      </form>
    </Dialog>
  </div>
</template>
