<script setup>
import { ref, onMounted, computed } from 'vue';
import { HeaderComponent, Kpi, Card, ConfigOptionItem, ListSkeleton, ErrorView } from '@components';
import { useUsersStore } from '@stores';
import { getPersonnelsService } from '@requests';
import { getReglagesService, updateReglagesService } from '@/requests/questionnaire_service';

/**
 * Réglages des questionnaires du département actif, enregistrés dans les options du département (clé « questionnaire »).
 * Les valeurs propres à une enquête (anonymat, modification des réponses, seuil, date de clôture) sont recopiées sur
 * chaque questionnaire à sa création ou à sa publication : les modifier ici ne change pas les questionnaires existants.
 */
const userStore = useUsersStore();

const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');
const reglages = ref(null);
const personnels = ref([]);
const loadingKeys = ref({});

const departement = computed(() => userStore.departementDefaut);

const personnelOptions = computed(() => [
  { label: 'Aucun référent', value: '' },
  ...(personnels.value || []).map(p => ({
    label: `${p.nom || ''} ${p.prenom || ''}`.trim() || p.display || `Personnel n° ${p.id}`,
    value: String(p.id),
  })),
]);

async function loadData() {
  if (!departement.value?.id) {
    isLoading.value = false;
    return;
  }
  isLoading.value = true;
  hasError.value = false;
  try {
    const [r, liste] = await Promise.all([
      getReglagesService(departement.value.id),
      getPersonnelsService({ departement: departement.value.id, pagination: false }).catch(() => []),
    ]);
    reglages.value = r;
    personnels.value = liste?.member || liste || [];
  } catch (error) {
    hasError.value = true;
    errorMessage.value = error?.response?.status === 403
      ? 'Ces réglages sont réservés aux gestionnaires des questionnaires du département.'
      : 'Impossible de charger les réglages des questionnaires.';
  } finally {
    isLoading.value = false;
  }
}

async function updateOption(key, value) {
  loadingKeys.value[key] = true;
  try {
    // Le serveur renvoie les réglages complets enregistrés : ils font foi.
    reglages.value = await updateReglagesService(departement.value.id, { [key]: value }, true);
  } catch (e) {
    // apiCall a affiché l'erreur ; on recharge pour ne pas afficher une valeur non enregistrée.
    await loadData();
  } finally {
    loadingKeys.value[key] = false;
  }
}

onMounted(loadData);
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      titre="Configuration des questionnaires"
      :description="`Politique du département ${departement?.libelle || ''} : anonymat, réponses, calendrier et restitution des résultats. Les valeurs sont recopiées sur chaque nouveau questionnaire.`"
      icon="pi pi-sliders-h"
      color="green"
      :show-back="false"
    />

    <ListSkeleton v-if="isLoading" />
    <ErrorView v-else-if="hasError" :message="errorMessage" />

    <div v-else-if="reglages" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Kpi label="Département" :value="departement?.libelle || '—'" icon="pi pi-building" color="green" />
        <Kpi label="Anonymat par défaut" :value="reglages.anonymat ? 'Activé' : 'Désactivé'" icon="pi pi-user-minus" :color="reglages.anonymat ? 'emerald' : 'amber'" />
        <Kpi label="Durée par défaut" :value="`${reglages.duree_jours} j`" icon="pi pi-calendar" color="blue" description="Si aucune date de clôture n'est fixée" />
      </div>

      <Card title="Enquêtes et répondants" subtitle="Valeurs recopiées sur chaque nouveau questionnaire" icon="pi pi-shield" color="green">
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Anonymat par défaut"
            description="Les réponses individuelles ne sont jamais présentées avec l'identité du répondant (modifiable sur chaque questionnaire avant publication)."
            icon="pi pi-lock"
            type="switch"
            :model-value="reglages.anonymat"
            :loading="loadingKeys.anonymat"
            @change="(val) => updateOption('anonymat', val)"
          />
          <ConfigOptionItem
            title="Modification des réponses envoyées"
            description="Les répondants peuvent revenir sur leurs réponses tant que le questionnaire est ouvert."
            icon="pi pi-pencil"
            type="switch"
            :model-value="reglages.modification_reponses"
            :loading="loadingKeys.modification_reponses"
            @change="(val) => updateOption('modification_reponses', val)"
          />
          <ConfigOptionItem
            title="Durée d'ouverture par défaut"
            description="À la publication d'un questionnaire sans date de clôture : clôture ce nombre de jours après l'ouverture."
            icon="pi pi-hourglass"
            type="number"
            :model-value="reglages.duree_jours"
            :min="1"
            :max="365"
            suffix="jour(s)"
            :loading="loadingKeys.duree_jours"
            @change="(val) => updateOption('duree_jours', Number(val))"
          />
          <ConfigOptionItem
            title="Clôture automatique"
            description="Clôture le questionnaire à sa date limite (ou quand tous ont répondu) et envoie le bilan. Sinon, la clôture se fait à la main depuis le suivi des réponses."
            icon="pi pi-calendar-times"
            type="switch"
            :model-value="reglages.cloture_auto"
            :loading="loadingKeys.cloture_auto"
            @change="(val) => updateOption('cloture_auto', val)"
          />
        </div>
      </Card>

      <Card title="Restitution des résultats" subtitle="Accès aux résultats et protection de l'anonymat" icon="pi pi-chart-line" color="teal">
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Référent qualité du département"
            description="Consulte les questionnaires et les résultats du département et reçoit le bilan de chaque questionnaire clôturé."
            icon="pi pi-user"
            type="select"
            :model-value="reglages.responsable_qualite ? String(reglages.responsable_qualite) : ''"
            :options="personnelOptions"
            :loading="loadingKeys.responsable_qualite"
            @change="(val) => updateOption('responsable_qualite', val ? Number(val) : null)"
          />
          <ConfigOptionItem
            title="Seuil minimal de réponses (questionnaires anonymes)"
            description="En dessous de ce nombre de réponses envoyées, le détail des résultats et les exports restent masqués pour éviter l'identification (0 = aucun seuil)."
            icon="pi pi-users"
            type="number"
            :model-value="reglages.seuil_min_reponses"
            :min="0"
            :max="50"
            suffix="réponse(s)"
            :loading="loadingKeys.seuil_min_reponses"
            @change="(val) => updateOption('seuil_min_reponses', Number(val))"
          />
          <ConfigOptionItem
            title="Publication automatique des résultats"
            description="À la fin de la relecture, les résultats sont publiés aux collègues destinataires (si la liste de diffusion a été validée). Sinon, la publication se fait à la main depuis la page de diffusion."
            icon="pi pi-send"
            type="switch"
            :model-value="reglages.publication_auto"
            :loading="loadingKeys.publication_auto"
            @change="(val) => updateOption('publication_auto', val)"
          />
          <ConfigOptionItem
            title="Durée de la relecture"
            description="Nombre de jours entre la clôture d'un questionnaire et la publication automatique de ses résultats."
            icon="pi pi-clock"
            type="number"
            :model-value="reglages.delai_relecture_jours"
            :min="0"
            :max="60"
            suffix="jour(s)"
            :loading="loadingKeys.delai_relecture_jours"
            @change="(val) => updateOption('delai_relecture_jours', Number(val))"
          />
          <ConfigOptionItem
            title="Durée de conservation des réponses"
            description="Après la clôture, les réponses individuelles sont supprimées au-delà de cette durée ; les statistiques agrégées restent consultables et comparables."
            icon="pi pi-trash"
            type="number"
            :model-value="reglages.conservation_annees"
            :min="1"
            :max="30"
            suffix="an(s)"
            :loading="loadingKeys.conservation_annees"
            @change="(val) => updateOption('conservation_annees', Number(val))"
          />
        </div>
      </Card>
    </div>
  </div>
</template>
