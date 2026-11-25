<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { addDays, addMonths, startOfMonth, startOfWeek } from 'date-fns';
import { VueCal } from 'vue-cal';
import 'vue-cal/style';
import DetailReservation from './DetailReservation.vue';
import { Card, EmptyState, ListSkeleton } from '@components';
import { champEuros, champLitres, enCentimes, enMillilitres } from './parcautoUnites';
import FormulaireParcauto from './FormulaireParcauto.vue';
import { enregistrerParcautoService, getPlanningService } from '../service/parcautoService';
import { dateHeure, instant, jourLocal, messageErreur } from './parcautoDates';

const props = defineProps({ vehicules: Array, passTelepeage: Array, portatifs: Array, gestion: Boolean });
const emit = defineEmits(['actualiser']);
const route = useRoute();
const mois = ref(startOfMonth(new Date()));
const periode = computed(() => {
  const debut = startOfWeek(mois.value, { weekStartsOn: 1 });
  return { debut: jourLocal(debut), fin: jourLocal(addDays(debut, 42)) };
});
const titreMois = computed(() => mois.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }));
const detail = ref(null);
const reservationCible = computed(() => String(route.query.reservation ?? ''));
const selection = ref(null);
const jourSelectionne = ref(null);
const missions = ref([]);
const chargement = ref(false);
const sauvegarde = ref(false);
const erreur = ref('');
const dialogue = ref(null);
const formulaire = ref({});
const ouiNon = [{ value: 'oui', label: 'Oui' }, { value: 'non', label: 'Non' }];
function champsPlein(mission) {
  return [
        { nom: 'libelle', label: 'Station ou référence du plein', requis: true, max: 255 },
        { nom: 'numeroFacture', label: 'N° de facture ou de ticket (si disponible)', max: 120 },
        { nom: 'kilometrage', label: 'Kilométrage au compteur', type: 'number', requis: true, min: mission.kilometrageDepart },
        { nom: 'litres', label: 'Volume (L)', requis: true, ...champLitres },
        { nom: 'euros', label: 'Montant (€)', requis: true, ...champEuros },
        ];
}

const champEquipements = computed(() => {
  const vehicule = dialogue.value?.mission?.vehicule ?? formulaire.value.vehicule;
  return {
    nom: 'equipements', label: 'Équipements emportés', type: 'multiple',
    options: props.portatifs.filter(e => e.vehicule == null || e.vehicule === vehicule)
      .map(e => ({ ...e, label: e.sorti ? e.label + ' (sorti)' : e.label })),
    optionDisabled: 'sorti',
  };
});
watch(() => formulaire.value.vehicule, () => {
  if (dialogue.value?.action === 'urgence') {
    const disponibles = champEquipements.value.options.filter(e => !e.sorti).map(e => e.value);
    formulaire.value.equipements = formulaire.value.equipements.filter(id => disponibles.includes(id));
  }
});

const champs = computed(() => {
  switch (dialogue.value?.action) {
    case 'urgence':
    case 'demander':
      return [
        {
          nom: 'vehicule', label: 'Véhicule', requis: true,
          options: props.vehicules.map(v => ({
            value: v.id, label: `${v.libelle} · ${v.site} · ${v.immatriculation}`, disabled: Boolean(v.blocage),
          })),
          optionDisabled: 'disabled',
        },
        dialogue.value.action === 'urgence'
          ? { nom: 'kilometrage', label: 'Kilométrage au départ', type: 'number', requis: true }
          : { nom: 'debut', label: 'Départ', type: 'datetime-local', requis: true },
        ...(dialogue.value.action === 'urgence' ? [champEquipements.value] : []),
        { nom: 'fin', label: 'Retour prévu', type: 'datetime-local', requis: true },
        {
          nom: 'typeOm', label: 'Votre OM permanent couvre-t-il l’ensemble du déplacement ?', requis: true,
          options: [
            { value: 'permanent', label: 'Oui, toutes les destinations sont couvertes' },
            { value: 'ponctuel', label: 'Non, ou je n’ai pas d’OM permanent' },
          ],
        },
        { nom: 'ordreMission', label: 'Référence de l’OM ponctuel', max: 120, requis: true, visible: d => d.typeOm === 'ponctuel' },
        { nom: 'motif', label: 'Motif du déplacement', max: 255, requis: true },
        { nom: 'destinations', label: 'Lieu(x) de destination', max: 500, requis: true },
      ];
    case 'retour': {
      const mission = dialogue.value.mission;
      const siTelepeage = d => d.telepeage === 'oui';
      return [
        { nom: 'kilometrage', label: 'Kilométrage au retour', type: 'number', requis: true, min: mission.kilometrageDepart },
        { nom: 'plein', label: mission.pleinsDeclares ? 'Avez-vous un autre plein à déclarer ?' : 'Avez-vous fait un plein ?', options: ouiNon, requis: true },
        { nom: 'telepeage', label: 'Avez-vous utilisé le télépéage ?', options: ouiNon, requis: true },
        { nom: 'incident', label: 'Avez-vous constaté un incident ?', options: ouiNon, requis: true },
        ...champsPlein(mission).map(champ => ({
          ...champ, nom: champ.nom === 'kilometrage' ? 'kmPlein' : champ.nom,
          label: champ.nom === 'kilometrage' ? 'Kilométrage lors du plein' : champ.label,
          visible: d => d.plein === 'oui',
        })),
        { nom: 'pass', label: 'Pass utilisé (si identifié)', options: props.passTelepeage.filter(p => p.vehicule == null || p.vehicule === mission.vehicule), visible: siTelepeage },
        { nom: 'facturePeage', label: 'N° de facture de télépéage (si disponible)', max: 120, visible: siTelepeage },
        { nom: 'prixPeage', label: 'Montant total des péages (€), si connu', ...champEuros, visible: siTelepeage },
        { nom: 'descriptionIncident', label: 'Décrivez l’incident constaté', max: 255, requis: true, visible: d => d.incident === 'oui' },
      ];
    }
    case 'refuser':
      return [{ nom: 'motifRefus', label: 'Motif du refus (facultatif)', max: 255 }];
    case 'depart':
      return [{ nom: 'kilometrage', label: 'Kilométrage au compteur', type: 'number', requis: true }, champEquipements.value];
    default:
      return [];
  }
});
const statuts = {
  demandee: ['À valider', 'warn'], validee: ['Autorisée', 'success'], en_cours: ['En cours', 'info'],
  terminee: ['Terminée', 'secondary'], refusee: ['Refusée', 'danger'], annulee: ['Annulée', 'secondary'], occupee: ['Occupé', 'info'],
};
const noms = computed(() => new Map(props.vehicules.map((v) => [v.id, v.libelle + ' · ' + v.immatriculation])));
const filtrer = computed(() => missions.value.filter((mission) =>
  (!selection.value || mission.vehicule === selection.value)
  && (!reservationCible.value || String(mission.id) === reservationCible.value)
));
function plage(mission) {
  const debut = new Date(mission.debut);
  const fin = new Date(mission.fin);
  const jour = date => date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const heure = date => date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  if (jourLocal(debut) === jourLocal(fin)) return jour(debut) + ' · ' + heure(debut) + ' – ' + heure(fin);
  return 'Du ' + jour(debut) + ' à ' + heure(debut) + ' au ' + jour(fin) + ' à ' + heure(fin);
}

const evenements = computed(() => filtrer.value.map(mission => ({
  start: new Date(mission.enCours ? mission.debutOccupation : mission.debut),
  end: mission.enCours ? new Date(Math.max(Date.parse(mission.fin), Date.now() + 1)) : new Date(mission.fin),
  title: noms.value.get(mission.vehicule),
  mission,
})));
const listeMois = computed(() => evenements.value.filter(e =>
  e.start < addMonths(mois.value, 1) && e.end > mois.value
).map(e => e.mission));
const reservationsListe = computed(() => reservationCible.value ? filtrer.value : listeMois.value);

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

function ouvrir(action, mission = null) {
  detail.value = null;
  dialogue.value = { action, mission };
  const jour = jourSelectionne.value;
  const vehicule = props.vehicules.find((v) => v.id === selection.value && !v.blocage);
  formulaire.value = {
    vehicule: vehicule?.id ?? null,
    equipements: [],
    debut: jour ? jour + 'T08:00' : '',
    fin: jour ? jour + 'T09:00' : '',
    typeOm: null,
    motif: '',
    destinations: '',
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
    if (action === 'demander' || action === 'urgence') {
      await enregistrerParcautoService(action === 'urgence' ? '/urgences' : '/reservations', {
        vehicule: donnees.vehicule, ...(action === 'urgence' ? { kilometrage: donnees.kilometrage, equipements: donnees.equipements } : { debut: instant(donnees.debut) }), fin: instant(donnees.fin),
        typeOm: donnees.typeOm, motif: donnees.motif, destinations: donnees.destinations,
        ...(donnees.typeOm === 'ponctuel' ? { ordreMission: donnees.ordreMission } : {}),
      });
    } else if (action === 'retour') {
      const plein = donnees.plein === 'oui';
      const telepeage = donnees.telepeage === 'oui';
      await enregistrerParcautoService(`/reservations/${mission.id}/retour`, {
        kilometrage: donnees.kilometrage, plein, telepeage,
        descriptionIncident: donnees.incident === 'oui' ? donnees.descriptionIncident : undefined,
        pleinDetails: plein ? { type: 'carburant', libelle: donnees.libelle, numeroFacture: donnees.numeroFacture,
          kilometrage: donnees.kmPlein, volumeMillilitres: enMillilitres(donnees.litres), montantCentimes: enCentimes(donnees.euros) } : undefined,
        telepeageDetails: telepeage ? { type: 'telepeage', libelle: 'Télépéage', pass: donnees.pass, numeroFacture: donnees.facturePeage,
          montantCentimes: donnees.prixPeage == null ? null : enCentimes(donnees.prixPeage) } : undefined,
      });
    } else {
      await enregistrerParcautoService(`/reservations/${mission.id}/${action}`, action === 'refuser' ? { motifRefus: donnees.motifRefus } : { kilometrage: donnees.kilometrage, equipements: donnees.equipements });
    }
    dialogue.value = null;
    emit('actualiser');
    await charger();
    if (action === 'demander') {
      mois.value = startOfMonth(new Date(donnees.debut));
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
  if (!Number.isNaN(jour.getTime()) && jourLocal(jour) === date) mois.value = startOfMonth(jour);
}, { immediate: true });

watch(() => route.query.action, (action) => {
  if (action === 'reserver') ouvrir('demander');
}, { immediate: true });

watch(periode, () => {
  jourSelectionne.value = null;
  detail.value = null;
  missions.value = [];
  charger();
}, { immediate: true, deep: true });
</script>

<template>
  <Card title="Calendrier des réservations" icon="pi pi-calendar">
    <template #header-actions>
      <Button label="Prendre un véhicule en urgence" severity="warn" outlined @click="ouvrir('urgence')" />
      <Button label="Réserver" icon="pi pi-plus" @click="ouvrir('demander')" />
    </template>
    <RouterLink v-if="reservationCible" :to="{ name: 'parcauto-planning' }" class="mb-4 inline-block text-sm font-semibold text-primary">
      Voir toutes les réservations
    </RouterLink>
    <div v-if="!reservationCible" class="mb-4 flex flex-wrap items-center gap-3">
      <h2 class="mr-2 text-xl font-semibold capitalize" aria-live="polite">{{ titreMois }}</h2>
      <div class="flex items-center gap-1">
        <Button icon="pi pi-chevron-left" aria-label="Mois précédent" severity="secondary" text rounded size="small" @click="mois = addMonths(mois, -1)" />
        <Button label="Aujourd’hui" severity="secondary" outlined size="small" @click="mois = startOfMonth(new Date())" />
        <Button icon="pi pi-chevron-right" aria-label="Mois suivant" severity="secondary" text rounded size="small" @click="mois = addMonths(mois, 1)" />
      </div>
      <Select class="w-full sm:ml-auto sm:w-auto" v-model="selection" :options="vehicules" option-label="libelle" option-value="id" show-clear placeholder="Tous les véhicules" aria-label="Filtrer par véhicule" />
    </div>
    <Message v-if="erreur && !dialogue" severity="error">{{ erreur }}</Message>
    <ListSkeleton v-if="chargement" />
    <template v-else-if="!erreur || dialogue">
      <div v-if="!reservationCible" class="hidden md:block">
        <VueCal locale="fr" :views="['month']" view="month" :view-date="mois" :events="evenements"
                events-on-month-view multiday-events :title-bar="false" :views-bar="false" :editable-events="false"
                class="calendrier-parc" @event-click="detail = $event.event.mission"
                @cell-click="jourSelectionne = jourLocal($event.date)">
          <template #event="{ event }">
            <button type="button" class="reservation" :data-statut="event.mission.statut"
                    :aria-label="event.title + ' · ' + statuts[event.mission.statut][0] + ' · ' + plage(event.mission)"
                    :title="event.title + ' · ' + plage(event.mission)" @click.stop="detail = event.mission">
              <span class="block truncate font-semibold">{{ event.title }}</span>
              <span class="flex items-center gap-1.5"><span class="pastille" aria-hidden="true" />{{ statuts[event.mission.statut][0] }}</span>
            </button>
          </template>
        </VueCal>
      </div>
      <div :class="{ 'md:hidden': !reservationCible }">
        <EmptyState v-if="!reservationsListe.length" title="Aucune réservation sur la période" compact />
        <ul v-else class="grid gap-2">
          <li v-for="(mission, index) in reservationsListe" :key="mission.id ?? 'anonyme-' + index" class="rounded-lg border border-surface-200 p-3">
            <DetailReservation :mission="mission" :vehicule="noms.get(mission.vehicule)" :plage="plage(mission)"
                               :statut="statuts[mission.statut]" :gestion="gestion" @action="ouvrir" />
          </li>
        </ul>
      </div>
    </template>
  </Card>

  <Dialog :visible="Boolean(detail)" header="Détail de la réservation" modal :style="{ width: 'min(36rem, 95vw)' }"
          @update:visible="!$event && (detail = null)">
    <DetailReservation v-if="detail" :mission="detail" :vehicule="noms.get(detail.vehicule)" :plage="plage(detail)"
                       :statut="statuts[detail.statut]" :gestion="gestion" @action="ouvrir" />
  </Dialog>
  <Dialog :visible="Boolean(dialogue)" modal :closable="!sauvegarde" :dismissable-mask="false" :style="{ width: 'min(34rem, 95vw)' }"
          :header="{ urgence: 'Départ immédiat en urgence', demander: 'Demander une réservation', valider: 'Valider la réservation', refuser: 'Refuser la demande', annuler: 'Annuler la réservation', depart: 'Enregistrer le départ', retour: 'Enregistrer le retour' }[dialogue?.action]"
          @update:visible="!$event && (dialogue = null)">
    <FormulaireParcauto v-if="dialogue" :key="dialogue.action" v-model="formulaire" :champs="champs"
                        :erreur="erreur" :sauvegarde="sauvegarde" libelle-action="Confirmer" @submit="enregistrer">
      <Message v-if="['depart', 'urgence'].includes(dialogue.action)" severity="info">Au retour, indiquez les pleins, les passages au télépéage et tout incident constaté. Gardez les références et montants disponibles.</Message>
      <Message v-if="dialogue.action === 'retour'" severity="info">Retour prévu le {{ dateHeure(dialogue.mission.fin) }}. Vous pouvez confirmer votre retour en avance ou en retard. L’heure de confirmation sera enregistrée.</Message>
      <Message v-if="dialogue.action === 'retour' && dialogue.mission.equipementsEmportes?.length" severity="info">Équipements à restituer : {{ dialogue.mission.equipementsEmportes.join(', ') }}.</Message>
      <Message v-if="dialogue.action === 'retour' && dialogue.mission.pleinsDeclares" severity="info">Pleins déjà enregistrés pour ce trajet : {{ dialogue.mission.pleinsDeclares }}.</Message>
      <p v-if="!champs.length">{{ noms.get(dialogue.mission.vehicule) }} · {{ dateHeure(dialogue.mission.debut) }} · {{ dialogue.mission.nomConducteur }}</p>
    </FormulaireParcauto>
  </Dialog>
</template>

<style scoped>
.calendrier-parc {
  --vuecal-primary-color: var(--p-primary-color);
  --vuecal-base-color: var(--p-text-color);
  --vuecal-secondary-color: var(--p-content-background);
  --vuecal-border-color: var(--p-content-border-color);
  --vuecal-height: clamp(35rem, 66vh, 48rem);
  overflow: hidden;
  border: 1px solid var(--p-content-border-color);
  border-radius: 0.875rem;
}

.calendrier-parc :deep(.vuecal__weekdays-headings) {
  background: var(--p-content-hover-background);
  color: var(--p-text-muted-color);
  font-size: 0.8rem;
  font-weight: 600;
}

.calendrier-parc :deep(.vuecal__cell:not(.vuecal__cell--today, .vuecal__cell--selected)::before),
.calendrier-parc :deep(.vuecal__cell:not(.vuecal__cell--today, .vuecal__cell--selected) .vuecal__cell-date) {
  background: transparent;
}

.calendrier-parc :deep(.vuecal__scrollable--month-view .vuecal__event) {
  padding: 0 0.2rem 0.2rem;
  border: 0;
  background: transparent;
  box-shadow: none;
}

.reservation {
  --accent: var(--p-sky-600);
  display: block;
  width: 100%;
  overflow: hidden;
  padding: 0.3rem 0.5rem;
  border: 0;
  border-radius: 0.45rem;
  background: color-mix(in srgb, var(--accent) 13%, var(--p-content-background));
  color: var(--p-text-color);
  font-size: 0.75rem;
  line-height: 1.4;
  text-align: left;
  cursor: pointer;
}
.reservation[data-statut="demandee"] { --accent: var(--p-amber-500); }
.reservation[data-statut="validee"] { --accent: var(--p-emerald-600); }
.reservation[data-statut="refusee"] { --accent: var(--p-red-500); }
.reservation:is([data-statut="annulee"], [data-statut="terminee"]) { --accent: var(--p-surface-500); }
.reservation:hover { background: color-mix(in srgb, var(--accent) 22%, var(--p-content-background)); }
.reservation:focus-visible { outline: 2px solid var(--p-primary-color); outline-offset: -2px; }
.pastille { width: 0.4rem; height: 0.4rem; border-radius: 50%; background: var(--accent); }
</style>
