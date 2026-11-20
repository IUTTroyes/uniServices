<script setup>
import { computed, onMounted, ref } from 'vue';
import { addDays, subDays } from 'date-fns';
import { Card, EmptyState, Kpi, ListSkeleton, ValidatedInput, validationRules, validateField } from '@components';
import { enregistrerParcautoService, exporterParcautoService, getTableauBordService } from '../service/parcautoService';
import { dateHeure, instant, jourLocal, messageErreur } from './parcautoDates';

const props = defineProps({ vehicules: Array });
const choixVehicules = computed(() => props.vehicules.map(v => ({
  value: v.id, label: v.immatriculation + ' · ' + v.libelle,
})));
const obligatoire = [validationRules.required];
const periode = ref({ debut: jourLocal(subDays(new Date(), 30)), fin: jourLocal(addDays(new Date(), 1)) });
const rapport = ref(null);
const periodeChargee = ref(null);
const recherche = ref({ vehicule: null, date: '' });
const resultats = ref(null);
const chargement = ref(false);
const exportEnCours = ref(false);
const rechercheEnCours = ref(false);
const erreur = ref('');
const erreurRecherche = ref('');
const euros = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
const total = (champ) => rapport.value?.vehicules.reduce((somme, v) => somme + v[champ], 0) ?? 0;
const colonnes = [
  ['vehicule', 'Véhicule'], ['immatriculation', 'Immatriculation'], ['site', 'Site'],
  ['utilisation', 'Utilisation (%)'], ['missions', 'Missions'], ['kilometres', 'Distance (km)'],
  ['litres', 'Carburant (L)'], ['consommation', 'L/100 km'], ['depenses', 'Dépenses (€)'],
  ['coutTotal', 'Coût de possession estimé (€)'], ['coutKm', '€/km'], ['reforme', 'Réforme estimée'], ['alertes', 'Alertes'],
];
const exportDisponible = computed(() => rapport.value?.vehicules.length && !chargement.value);

async function charger() {
  const dates = { ...periode.value };
  if (!Object.values(dates).every(date => validateField(date, obligatoire).isValid)) {
    erreur.value = 'Renseignez les deux dates du rapport.';
    return;
  }
  chargement.value = true;
  erreur.value = '';
  try {
    rapport.value = await getTableauBordService(dates);
    periodeChargee.value = dates;
  } catch (e) {
    rapport.value = null;
    erreur.value = messageErreur(e);
  } finally {
    chargement.value = false;
  }
}

async function exporter() {
  exportEnCours.value = true;
  erreur.value = '';
  try {
    await exporterParcautoService(periodeChargee.value);
  } catch (e) {
    erreur.value = messageErreur(e);
  } finally {
    exportEnCours.value = false;
  }
}

async function rechercher() {
  resultats.value = null;
  if (!Object.values(recherche.value).every(valeur => validateField(valeur, obligatoire).isValid)) {
    erreurRecherche.value = 'Renseignez le véhicule, la date et l’heure de l’infraction.';
    return;
  }
  rechercheEnCours.value = true;
  erreurRecherche.value = '';
  try {
    resultats.value = await enregistrerParcautoService('/infractions', {
      vehicule: recherche.value.vehicule,
      date: instant(recherche.value.date),
    });
  } catch (e) {
    erreurRecherche.value = messageErreur(e);
  } finally {
    rechercheEnCours.value = false;
  }
}

onMounted(charger);
</script>

<template>
  <div class="grid gap-5">
    <Card title="Tableau de bord du parc" icon="pi pi-chart-bar">
      <template #header-actions>
        <Button label="Exporter en CSV" icon="pi pi-download" severity="secondary" :loading="exportEnCours" :disabled="!exportDisponible" @click="exporter" />
      </template>
      <form class="mb-4 flex flex-wrap items-end gap-3" @submit.prevent="charger">
        <ValidatedInput v-model="periode.debut" name="pilotage-du" label="Du" type="native-date" :rules="obligatoire" />
        <ValidatedInput v-model="periode.fin" name="pilotage-au" label="Au (exclu)" type="native-date" :rules="obligatoire" />
        <Button type="submit" label="Actualiser" :loading="chargement" />
      </form>
      <Message v-if="erreur" severity="error">{{ erreur }}</Message>
      <ListSkeleton v-if="chargement" />
      <template v-else-if="rapport">
        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
          <Kpi label="Missions terminées" :value="total('missions')" icon="pi pi-calendar" color="violet" />
          <Kpi label="Kilomètres parcourus" :value="total('kilometres')" icon="pi pi-map" color="blue" />
          <Kpi label="Coût de possession estimé" :value="euros.format(total('coutTotal'))" icon="pi pi-euro" color="emerald" />
        </div>
        <DataTable :value="rapport.vehicules" data-key="immatriculation" paginator :rows="10">
          <template #empty><EmptyState title="Aucun véhicule dans le rapport" icon="pi pi-chart-bar" compact /></template>
          <Column v-for="[champ, label] in colonnes" :key="champ" :field="champ" :header="label" sortable />
        </DataTable>
      </template>
    </Card>
    <Card v-if="rapport" title="Permutations à étudier" icon="pi pi-arrow-right-arrow-left">
      <EmptyState v-if="!rapport.permutations.length" title="Aucune permutation suggérée sur la période" icon="pi pi-car" compact />
      <ul v-else class="grid gap-2">
        <li v-for="suggestion in rapport.permutations" :key="suggestion">{{ suggestion }}</li>
      </ul>
    </Card>
    <Card title="Identifier un conducteur" icon="pi pi-search">
      <form class="mb-4 flex flex-wrap items-end gap-3" @submit.prevent="rechercher">
        <ValidatedInput v-model="recherche.vehicule" name="vehicule-infraction" label="Véhicule" type="select"
                        :options="choixVehicules" :rules="obligatoire" filter />
        <ValidatedInput v-model="recherche.date" name="date-infraction" label="Date et heure de l’infraction"
                        type="datetime-local" :step="1" :rules="obligatoire" />
        <Button type="submit" label="Rechercher" :loading="rechercheEnCours" />
      </form>
      <Message v-if="erreurRecherche" severity="error">{{ erreurRecherche }}</Message>
      <EmptyState v-if="resultats && !resultats.length" title="Aucun départ enregistré pour cet instant" icon="pi pi-search" compact />
      <ul v-else-if="resultats" class="grid gap-2">
        <li v-for="mission in resultats" :key="mission.id">
          <strong>{{ mission.nomConducteur }}</strong> · OM {{ mission.ordreMission }} · {{ dateHeure(mission.departLe) }}
          – {{ mission.retourLe ? dateHeure(mission.retourLe) : 'retour en attente' }}
        </li>
      </ul>
    </Card>
  </div>
</template>
