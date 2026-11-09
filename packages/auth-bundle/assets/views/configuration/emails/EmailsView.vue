<script setup>
/**
 * Liste des e-mails de l'application, tous packages confondus (notifications comprises), avec ce qui s'applique :
 * modèle par défaut, modèle générique personnalisé, personnalisation de l'établissement ou du département.
 */
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { EnvelopeIcon, PaintBrushIcon } from '@heroicons/vue/24/outline';
import { ActionButtonVertical, ApplicationBadge, Card, EmptyState, HeaderComponent, Kpi, ListSkeleton } from '@components';
import { getEmailDefinitionsService, getEmailDepartementsService } from '@requests';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const chargement = ref(true);
const definitions = ref([]);
const departements = ref([]);
const departementId = ref(route.query.departement ? Number(route.query.departement) : null);
const recherche = ref('');

/** Ce qui s'applique pour un e-mail au niveau consulté (cf. EmailTemplateController::niveau). */
const PERSONNALISATIONS = {
  departement: { libelle: 'Personnalisé (département)', severite: 'success' },
  etablissement: { libelle: 'Personnalisé (établissement)', severite: 'success' },
  parent: { libelle: 'Modèle générique personnalisé', severite: 'info' },
  defaut: { libelle: 'Par défaut', severite: 'secondary' }
};
const personnalisation = (item) => PERSONNALISATIONS[item.personnalisation] || PERSONNALISATIONS.defaut;

const charger = async () => {
  chargement.value = true;
  try {
    definitions.value = await getEmailDefinitionsService(departementId.value);
  } catch {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les modèles d\'e-mails.', life: 4000 });
  } finally {
    chargement.value = false;
  }
};

const changerNiveau = () => {
  router.replace({ query: departementId.value ? { departement: departementId.value } : {} });
  charger();
};

const definitionsFiltrees = computed(() => {
  const q = recherche.value.trim().toLowerCase();
  if (!q) return definitions.value;
  return definitions.value
    .map(groupe => ({ ...groupe, items: groupe.items.filter(i => [i.label, i.key, i.description].some(t => t?.toLowerCase().includes(q))) }))
    .filter(groupe => groupe.items.length > 0);
});

const total = computed(() => definitions.value.reduce((n, g) => n + g.items.length, 0));
const personnalises = computed(() => definitions.value.reduce((n, g) => n + g.items.filter(i => i.isCustomized).length, 0));
const niveauLibelle = computed(() => departements.value.find(d => d.id === departementId.value)?.libelle || 'Établissement');

const ouvrir = (item) => router.push({
  name: 'email-edit',
  params: { key: encodeURIComponent(item.key) },
  query: departementId.value ? { departement: departementId.value } : {}
});

onMounted(async () => {
  try {
    departements.value = await getEmailDepartementsService();
  } catch {
    departements.value = [];
  }
  await charger();
});
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent titre="Modèles de mails" :icon="EnvelopeIcon" color="teal" :show-back="false"
      description="E-mails envoyés par toutes les applications, notifications comprises. Une personnalisation de l'établissement vaut pour tous les départements ; celle d'un département prime.">
      <template #actions>
        <ActionButtonVertical :icon="PaintBrushIcon" label="Habillage" :to="{ name: 'emails-habillage' }" />
      </template>
    </HeaderComponent>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <Kpi label="Modèles" :value="total" icon="pi pi-envelope" color="teal" :loading="chargement" />
      <Kpi :label="`Personnalisés — ${niveauLibelle}`" :value="personnalises" icon="pi pi-pencil" color="emerald" :loading="chargement" />
    </div>

    <Card title="Niveau de personnalisation" icon="pi pi-sitemap" color="teal" body-class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="emails-niveau" class="q-label">Personnaliser pour</label>
        <Select input-id="emails-niveau" v-model="departementId" :options="departements" option-label="libelle" option-value="id"
          placeholder="L'établissement (tous les départements)" show-clear filter class="w-full" @change="changerNiveau" />
        <p class="q-hint">Sans département : la personnalisation vaut pour tout l'établissement.</p>
      </div>
      <div>
        <label for="emails-recherche" class="q-label">Rechercher</label>
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText id="emails-recherche" v-model="recherche" placeholder="Nom, clé ou description…" class="w-full" />
        </IconField>
      </div>
    </Card>

    <ListSkeleton v-if="chargement" />

    <EmptyState v-else-if="definitionsFiltrees.length === 0" icon="pi pi-search" title="Aucun modèle"
      description="Aucun modèle ne correspond à votre recherche." />

    <div v-else class="space-y-6">
      <section v-for="groupe in definitionsFiltrees" :key="groupe.bundle" class="space-y-3">
        <div class="flex items-center gap-2">
          <ApplicationBadge :app="groupe.bundle" size="sm" show-dot />
          <span class="text-sm text-slate-500">{{ groupe.items.length }} modèle{{ groupe.items.length > 1 ? 's' : '' }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          <button v-for="item in groupe.items" :key="item.key" type="button" @click="ouvrir(item)"
            class="q-choice text-left flex-col !gap-2 hover:shadow-md">
            <span class="flex items-start justify-between gap-2 w-full">
              <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.label }}</span>
              <Tag :value="personnalisation(item).libelle" :severity="personnalisation(item).severite" class="shrink-0" />
            </span>
            <span v-if="item.description" class="q-choice-desc">{{ item.description }}</span>
            <span class="flex items-center justify-between gap-2 w-full">
              <span class="text-xs text-slate-400 font-mono truncate">{{ item.key }}</span>
              <span class="text-xs text-slate-500 shrink-0">{{ item.categorie === 'notification' ? (item.desactivable ? 'Notification' : 'Notification non désactivable') : 'Toujours envoyé' }}</span>
            </span>
          </button>
        </div>
      </section>
    </div>
  </div>
</template>
