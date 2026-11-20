<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { addDays, differenceInCalendarDays } from 'date-fns';
import { Card, EmptyState, ListSkeleton, PermissionGuard, ValidatedInput } from '@components';
import { gestionParcauto } from '../permissions';
import { champEuros, champLitres, enCentimes, enMillilitres } from './parcautoUnites';
import FormulaireParcauto from './FormulaireParcauto.vue';
import { enregistrerParcautoService, getPlanningService } from '../service/parcautoService';
import { dateHeure, instant, jourLocal, messageErreur, periodePlanning } from './parcautoDates';

const props = defineProps({ vehicules: Array, gestion: Boolean });
const emit = defineEmits(['actualiser']);
const route = useRoute();
const toast = useToast();
const periode = ref(periodePlanning());
const reservationCible = computed(() => String(route.query.reservation ?? ''));
const selection = ref(null);
const jourSelectionne = ref(null);
const missions = ref([]);
const chargement = ref(false);
const sauvegarde = ref(false);
const erreur = ref('');
const dialogue = ref(null);
const formulaire = ref({});
const champs = computed(() => {
  switch (dialogue.value?.action) {
    case 'demander':
      return [
        {
          nom: 'vehicule', label: 'Véhicule', requis: true,
          options: props.vehicules.map(v => ({
            value: v.id, label: `${v.libelle} · ${v.site} · ${v.immatriculation}`, disabled: Boolean(v.blocage),
          })),
          optionDisabled: 'disabled',
        },
        { nom: 'debut', label: 'Départ', type: 'datetime-local', requis: true },
        { nom: 'fin', label: 'Retour prévu', type: 'datetime-local', requis: true },
        { nom: 'ordreMission', label: 'Référence de l’ordre de mission', max: 120, requis: true },
      ];
    case 'carburant':
      return [
        { nom: 'libelle', label: 'Station ou référence du plein', requis: true, max: 255 },
        { nom: 'kilometrage', label: 'Kilométrage au compteur', type: 'number', requis: true, min: dialogue.value.mission.kilometrageDepart },
        { nom: 'litres', label: 'Volume (L)', requis: true, ...champLitres },
        { nom: 'euros', label: 'Montant (€)', requis: true, ...champEuros },
      ];
    case 'incident':
      return [{ nom: 'libelle', label: 'Symptôme ou incident constaté', requis: true, max: 255 }];
    case 'depart':
    case 'retour':
      return [{ nom: 'kilometrage', label: 'Kilométrage au compteur', type: 'number', requis: true }];
    default:
      return [];
  }
});
const statuts = {
  demandee: ['À valider', 'warn'], validee: ['Validée', 'success'], en_cours: ['En cours', 'info'],
  terminee: ['Terminée', 'secondary'], annulee: ['Annulée', 'secondary'], occupee: ['Occupé', 'info'],
};
const noms = computed(() => new Map(props.vehicules.map((v) => [v.id, v.libelle + ' · ' + v.immatriculation])));
const filtrer = computed(() => missions.value.filter((mission) =>
  (!selection.value || mission.vehicule === selection.value)
  && (!reservationCible.value || String(mission.id) === reservationCible.value)
));
const formatJour = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });

function occupeJour(mission, jour) {
  const debut = new Date(jour + 'T00:00:00');
  const fin = addDays(debut, 1);
  return new Date(mission.debut) < fin
    && (new Date(mission.fin) > debut || mission.statut === 'en_cours'
      || (mission.enCours && mission.statut === 'occupee'));
}

const calendrier = computed(() => {
  const debut = new Date(periode.value.debut + 'T00:00:00');
  const fin = new Date(periode.value.fin + 'T00:00:00');
  const jours = Math.min(45, differenceInCalendarDays(fin, debut));
  return Array.from({ length: Number.isFinite(jours) ? Math.max(0, jours) : 0 }, (_, index) => {
    const date = addDays(debut, index);
    const jour = jourLocal(date);
    const nombre = filtrer.value.filter((mission) => mission.statut !== 'annulee' && occupeJour(mission, jour)).length;
    return { jour, label: formatJour.format(date), nombre };
  });
});
const jours = computed(() => {
  const groupes = new Map();
  for (const mission of filtrer.value) {
    if (jourSelectionne.value && !occupeJour(mission, jourSelectionne.value)) continue;
    const jour = new Date(mission.debut).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
    if (!groupes.has(jour)) groupes.set(jour, []);
    groupes.get(jour).push(mission);
  }
  return [...groupes];
});

let requete = 0;
async function charger() {
  const version = ++requete;
  chargement.value = true;
  erreur.value = '';
  try {
    const resultat = await getPlanningService(periode.value);
    if (version === requete) missions.value = resultat;
  } catch (e) {
    if (version === requete) {
      missions.value = [];
      erreur.value = messageErreur(e);
    }
  } finally {
    if (version === requete) chargement.value = false;
  }
}

function deplacer(delta) {
  periode.value = periodePlanning(addDays(new Date(periode.value.debut + 'T12:00:00'), delta));
}

function ouvrir(action, mission = null) {
  dialogue.value = { action, mission };
  const jour = jourSelectionne.value;
  const vehicule = props.vehicules.find((v) => v.id === selection.value && !v.blocage);
  formulaire.value = {
    vehicule: vehicule?.id ?? null,
    debut: jour ? jour + 'T08:00' : '',
    fin: jour ? jour + 'T09:00' : '',
    ordreMission: '',
    kilometrage: null,
  };
  erreur.value = '';
}

async function enregistrer() {
  const { action, mission } = dialogue.value;
  sauvegarde.value = true;
  erreur.value = '';
  try {
    const donnees = formulaire.value;
    if (action === 'demander') {
      await enregistrerParcautoService('/reservations', {
        vehicule: donnees.vehicule, debut: instant(donnees.debut), fin: instant(donnees.fin), ordreMission: donnees.ordreMission,
      });
    } else if (action === 'carburant' || action === 'incident') {
      await enregistrerParcautoService(`/reservations/${mission.id}/journal`, {
        type: action,
        libelle: donnees.libelle,
        ...(action === 'carburant' ? {
          kilometrage: donnees.kilometrage,
          volumeMillilitres: enMillilitres(donnees.litres),
          montantCentimes: enCentimes(donnees.euros),
        } : {}),
      });
      toast.add({
        severity: 'success',
        summary: action === 'carburant' ? 'Plein enregistré' : 'Incident enregistré',
        life: 3500,
      });
    } else {
      await enregistrerParcautoService(`/reservations/${mission.id}/${action}`, { kilometrage: donnees.kilometrage });
    }
    dialogue.value = null;
    emit('actualiser');
    await charger();
    if (action === 'demander' && jourLocal(new Date(donnees.debut)) >= periode.value.fin) {
      periode.value = periodePlanning(new Date(donnees.debut));
    }
  } catch (e) {
    erreur.value = messageErreur(e);
  } finally {
    sauvegarde.value = false;
  }
}

watch(() => route.query.date, (date) => {
  if (typeof date !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return;
  const jour = new Date(date + 'T12:00:00');
  if (!Number.isNaN(jour.getTime()) && jourLocal(jour) === date) periode.value = periodePlanning(jour);
}, { immediate: true });

watch(() => route.query.action, (action) => {
  if (action === 'reserver') ouvrir('demander');
}, { immediate: true });

watch(periode, () => {
  jourSelectionne.value = null;
  charger();
}, { immediate: true, deep: true });
</script>

<template>
  <Card title="Calendrier des réservations" icon="pi pi-calendar">
    <template #header-actions>
      <Button label="Réserver" icon="pi pi-plus" @click="ouvrir('demander')" />
    </template>
    <RouterLink v-if="reservationCible" :to="{ name: 'parcauto-planning' }" class="mb-4 inline-block text-sm font-semibold text-primary">
      Voir toutes les réservations
    </RouterLink>
    <div v-if="!reservationCible" class="mb-5 flex flex-wrap items-end gap-3">
      <ValidatedInput v-model="periode.debut" name="planning-du" label="Du" type="native-date" />
      <ValidatedInput v-model="periode.fin" name="planning-au" label="Au (exclu)" type="native-date" />
      <Button icon="pi pi-chevron-left" aria-label="Période précédente" severity="secondary" @click="deplacer(-14)" />
      <Button icon="pi pi-chevron-right" aria-label="Période suivante" severity="secondary" @click="deplacer(14)" />
      <Select v-model="selection" :options="vehicules" option-label="libelle" option-value="id" show-clear placeholder="Tous les véhicules" aria-label="Filtrer par véhicule" />
    </div>
    <div v-if="!reservationCible && calendrier.length && !chargement && !erreur" class="mb-5">
      <div v-if="jourSelectionne" class="mb-2">
        <Button label="Toute la période" severity="secondary" text size="small" @click="jourSelectionne = null" />
      </div>
      <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7" role="group" aria-label="Jours du calendrier">
        <Button v-for="jour in calendrier" :key="jour.jour" :label="jour.label + ' · ' + jour.nombre" size="small"
                :severity="jourSelectionne === jour.jour ? 'primary' : 'secondary'" :outlined="jourSelectionne !== jour.jour"
                :aria-pressed="jourSelectionne === jour.jour" :aria-label="jour.label + ', ' + jour.nombre + ' créneau(x)'"
                @click="jourSelectionne = jourSelectionne === jour.jour ? null : jour.jour" />
      </div>
    </div>
    <Message v-if="erreur && !dialogue" severity="error">{{ erreur }}</Message>
    <ListSkeleton v-if="chargement" />
    <EmptyState v-else-if="!jours.length" :title="jourSelectionne ? 'Aucune réservation ce jour' : 'Aucune réservation sur la période'" icon="pi pi-calendar" compact />
    <section v-for="[jour, reservations] in jours" v-else :key="jour" class="mb-5">
      <h2 class="mb-2 text-base font-semibold capitalize">{{ jour }}</h2>
      <ul class="grid gap-2">
        <li v-for="(mission, index) in reservations" :key="mission.id ?? index" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
          <div>
            <div class="font-semibold">{{ noms.get(mission.vehicule) }}</div>
            <div class="text-sm">{{ dateHeure(mission.debut) }} – {{ dateHeure(mission.fin) }}</div>
            <div v-if="mission.nomConducteur" class="text-sm">
              {{ mission.nomConducteur }} · OM {{ mission.ordreMission }}{{ mission.ordreVerifie ? ' (vérifié par OMER)' : '' }}
              <span v-if="mission.kilometrageDepart !== null"> · Départ {{ mission.kilometrageDepart }} km</span>
              <span v-if="mission.kilometrageRetour !== null"> · Retour {{ mission.kilometrageRetour }} km</span>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Tag :value="statuts[mission.statut][0]" :severity="statuts[mission.statut][1]" />
            <PermissionGuard v-if="gestion && mission.statut === 'demandee'" :permission="gestionParcauto">
              <Button label="Valider" size="small" @click="ouvrir('valider', mission)" />
            </PermissionGuard>
            <PermissionGuard :permission="mission.proprietaire">
              <Button v-if="mission.statut === 'validee'" label="Départ" size="small" @click="ouvrir('depart', mission)" />
              <template v-if="mission.statut === 'en_cours'">
                <Button label="Déclarer un plein" size="small" severity="secondary" @click="ouvrir('carburant', mission)" />
                <Button label="Signaler un incident" size="small" severity="secondary" @click="ouvrir('incident', mission)" />
              </template>
              <Button v-if="mission.statut === 'en_cours'" label="Retour" size="small" @click="ouvrir('retour', mission)" />
            </PermissionGuard>
            <PermissionGuard v-if="(mission.proprietaire || gestion) && ['demandee', 'validee'].includes(mission.statut)"
                             :permission="mission.proprietaire || gestionParcauto">
              <Button label="Annuler" size="small" severity="secondary" text @click="ouvrir('annuler', mission)" />
            </PermissionGuard>
          </div>
        </li>
      </ul>
    </section>
  </Card>

  <Dialog :visible="Boolean(dialogue)" modal :closable="!sauvegarde" :dismissable-mask="false" :style="{ width: 'min(34rem, 95vw)' }"
          :header="{ demander: 'Demander une réservation', valider: 'Valider la réservation', annuler: 'Annuler la réservation', depart: 'Enregistrer le départ', retour: 'Enregistrer le retour', carburant: 'Déclarer un plein', incident: 'Signaler un incident mécanique' }[dialogue?.action]"
          @update:visible="!$event && (dialogue = null)">
    <FormulaireParcauto v-if="dialogue" :key="dialogue.action" v-model="formulaire" :champs="champs"
                        :erreur="erreur" :sauvegarde="sauvegarde" libelle-action="Confirmer" @submit="enregistrer">
      <p v-if="!champs.length">{{ noms.get(dialogue.mission.vehicule) }} · {{ dateHeure(dialogue.mission.debut) }} · {{ dialogue.mission.nomConducteur }}</p>
    </FormulaireParcauto>
  </Dialog>
</template>
