<script setup>
import { computed, onMounted, ref } from 'vue';
import Chart from 'primevue/chart';
import { addDays, startOfMonth, subMonths } from 'date-fns';
import { Card, EmptyState, Kpi, ListSkeleton, ValidatedInput, validationRules, validateField } from '@components';
import { enregistrerParcautoService, exporterParcautoService, getTableauBordService } from '../service/parcautoService';
import { libelleOrdreMission } from './ordreMission';
import { dateHeure, instant, jourLocal, messageErreur } from './parcautoDates';

const props = defineProps({ vehicules: Array });
const choixVehicules = computed(() => props.vehicules.map(v => ({
  value: v.id, label: v.immatriculation + ' · ' + v.libelle,
})));
const obligatoire = [validationRules.required];
const periode = ref({ debut: jourLocal(startOfMonth(subMonths(new Date(), 11))), fin: jourLocal(addDays(new Date(), 1)) });
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
  ['vehicule', 'Véhicule'], ['immatriculation', 'Immatriculation'], ['dernierDepart', 'Dernier départ avant la fin de période'],
  ['utilisation', 'Utilisation (%)'], ['missions', 'Missions'], ['kilometres', 'Distance (km)'],
  ['litres', 'Carburant (L)'], ['consommation', 'L/100 km'], ['depenses', 'Dépenses (€)'],
  ['coutTotal', 'Coût de possession estimé (€)'], ['coutKm', '€/km'], ['reforme', 'Réforme estimée'], ['alertes', 'Alertes'],
];
const statuts = { demandee: 'Demandées', validee: 'Confirmées', en_cours: 'En cours', annulee: 'Annulées', terminee: 'Terminées' };
const graphique = computed(() => {
  const mois = rapport.value?.activiteMensuelle ?? [];
  return {
    labels: mois.map(m => new Date(m.mois + '-01T12:00:00').toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })),
    datasets: [
      { label: 'Trajets classiques', data: mois.map(m => m.classiques), backgroundColor: '#2563eb' },
      { label: 'Urgences', data: mois.map(m => m.urgences), backgroundColor: '#d97706' },
    ],
  };
});
const optionsGraphique = {
  responsive: true, maintainAspectRatio: false,
  scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
};
const urgences = computed(() => rapport.value?.activiteMensuelle.reduce((n, m) => n + m.urgences, 0) ?? 0);
const departs = computed(() => rapport.value?.activiteMensuelle.reduce((n, m) => n + m.classiques + m.urgences, 0) ?? 0);
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
        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <Kpi label="Missions terminées" :value="total('missions')" icon="pi pi-calendar" color="violet" />
          <Kpi label="Kilomètres parcourus" :value="total('kilometres')" icon="pi pi-map" color="blue" />
          <Kpi label="Dépenses enregistrées" :value="euros.format(total('depenses'))" icon="pi pi-wallet" color="blue" />
          <Kpi label="Coût de possession estimé" :value="euros.format(total('coutTotal'))" icon="pi pi-euro" color="emerald" />
        </div>
        <h3 class="mb-2 font-semibold">Réservations par statut · départ prévu sur la période</h3>
        <div class="mb-5 flex flex-wrap gap-2">
          <Tag v-for="(libelle, statut) in statuts" :key="statut" severity="secondary" :value="libelle + ' · ' + (rapport.statuts[statut] ?? 0)" />
        </div>
        <DataTable :value="rapport.vehicules" data-key="immatriculation" paginator :rows="10">
          <template #empty><EmptyState title="Aucun véhicule dans le rapport" icon="pi pi-chart-bar" compact /></template>
          <Column v-for="[champ, label] in colonnes" :key="champ" :field="champ" :header="label" sortable>
            <template #body="{ data }">
              <span v-if="champ === 'consommation' && data.consommation == null" class="text-sm text-muted-color">
                <i class="pi pi-info-circle" aria-hidden="true" />
                Deux pleins avec un kilométrage croissant sont nécessaires sur la période.
              </span>
              <span v-else-if="champ === 'alertes' && !data.alertes" class="text-sm text-muted-color">
                Aucune alerte détectée
              </span>
              <Tag v-else-if="champ === 'alertes'" severity="warn" :value="data.alertes" />
              <span v-else-if="champ === 'dernierDepart' && !data.dernierDepart">Aucun départ enregistré</span>
              <template v-else>{{ data[champ] }}</template>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <Card v-if="rapport" title="Trajets commencés sur 12 mois" icon="pi pi-chart-bar">
      <p class="mb-3">{{ urgences }} en urgence sur {{ departs }} trajet(s) · jusqu’au {{ new Date(periodeChargee.fin + 'T00:00:00').toLocaleDateString('fr-FR') }} exclu</p>
      <Chart type="bar" :data="graphique" :options="optionsGraphique" class="h-72" aria-label="Départs mensuels classiques et urgents" />
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
          <strong>{{ mission.nomConducteur }}</strong> · {{ libelleOrdreMission(mission) }} · {{ dateHeure(mission.departLe) }}
          – {{ mission.retourLe ? dateHeure(mission.retourLe) : 'retour en attente' }}
        </li>
      </ul>
    </Card>
  </div>
</template>
