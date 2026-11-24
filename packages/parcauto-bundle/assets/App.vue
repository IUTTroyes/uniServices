<script setup>
import { computed, onMounted, ref } from 'vue';
import { ActionButtonVertical, Card, EmptyState, HeaderComponent, Kpi, ListSkeleton, PermissionGuard } from '@components';
import ReservationsAccueil from './components/ReservationsAccueil.vue';
import { messageErreur } from './components/parcautoDates';
import { getAccueilService, getParcService } from './service/parcautoService';

const accueil = ref(null);
const chargement = ref(false);
const erreur = ref('');
const vehicules = computed(() => new Map(accueil.value?.parc.vehicules.map(v => [v.id, v])));
const bloques = computed(() => (accueil.value?.parc.vehicules ?? []).filter(v => v.blocage));
const bornes = computed(() => (accueil.value?.parc.equipements ?? []).filter(e => e.type === 'borne' && e.actif));
const gestion = computed(() => accueil.value?.parc.gestion);
const planning = { name: 'parcauto-planning' };
const parc = { name: 'parcauto-vehicules' };
const sectionsPersonnelles = [
  { cle: 'mesMissions', titre: 'Mes missions en cours', vide: 'Aucune mission en cours', icon: 'pi pi-car', couleur: 'blue' },
  { cle: 'mesDeparts', titre: 'Mes départs confirmés', vide: 'Aucun départ confirmé à venir', icon: 'pi pi-calendar', couleur: 'emerald' },
  { cle: 'mesDemandes', titre: 'Mes demandes en attente', vide: 'Aucune demande en attente', icon: 'pi pi-clock', couleur: 'amber' },
];
const sectionsGestion = [
  { cle: 'aValider', titre: 'Demandes à valider', vide: 'Aucune demande à valider', icon: 'pi pi-calendar-plus', couleur: 'amber' },
  { cle: 'retoursEnRetard', titre: 'Retours en retard', vide: 'Aucun retour en retard', icon: 'pi pi-clock', couleur: 'red' },
];

async function charger() {
  chargement.value = true;
  erreur.value = '';
  try {
    const [parc, reservations] = await Promise.all([getParcService(), getAccueilService()]);
    accueil.value = { parc, reservations };
  } catch (e) {
    accueil.value = null;
    erreur.value = messageErreur(e);
  } finally {
    chargement.value = false;
  }
}

onMounted(charger);
</script>

<template>
  <HeaderComponent titre="Tableau de bord du parc automobile"
                   :description="gestion ? 'Supervision des réservations, des retours et des véhicules bloqués.' : 'Demandes, prochains départs et missions en cours.'"
                   icon="pi pi-car" color="blue">
    <template #actions>
      <div class="flex flex-wrap gap-2">
        <ActionButtonVertical label="Réserver un véhicule" icon="pi pi-plus" :to="{ ...planning, query: { action: 'reserver' } }" />
        <ActionButtonVertical label="Actualiser" icon="pi pi-refresh" severity="secondary" :disabled="chargement" @click="charger" />
      </div>
    </template>
  </HeaderComponent>
  <Message v-if="erreur" severity="error">{{ erreur }}</Message>
  <ListSkeleton v-if="!accueil && chargement" />
  <div v-if="accueil" class="grid gap-5">
    <PermissionGuard :permission="Boolean(gestion) && 'canManageParcauto'" show-fallback>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Kpi label="Demandes à valider" :value="accueil.reservations.aValider.total" icon="pi pi-calendar-plus" color="amber" />
        <Kpi label="Retours en retard" :value="accueil.reservations.retoursEnRetard.total" icon="pi pi-clock" color="red" />
        <Kpi label="Véhicules éligibles à la réservation" :value="vehicules.size - bloques.length" icon="pi pi-car" color="emerald" :to="parc" />
        <Kpi label="Véhicules bloqués" :value="bloques.length" icon="pi pi-ban" color="red" :to="parc" />
      </div>
      <div class="grid gap-4 lg:grid-cols-2">
        <ReservationsAccueil v-for="section in sectionsGestion" :key="section.cle"
                             v-bind="section" :groupe="accueil.reservations[section.cle]" :vehicules="vehicules" gestion />
      </div>
      <Card title="Véhicules bloqués" icon="pi pi-wrench" color="red">
        <template #header-actions>
          <RouterLink :to="parc" class="text-sm font-semibold text-primary">Consulter le parc</RouterLink>
        </template>
        <EmptyState v-if="!bloques.length" title="Aucun véhicule bloqué" icon="pi pi-check" compact />
        <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          <li v-for="vehicule in bloques.slice(0, 6)" :key="vehicule.id">
            <RouterLink :to="{ ...parc, query: { vehicule: String(vehicule.id) } }" class="font-semibold hover:text-primary">
              {{ vehicule.libelle }} · {{ vehicule.immatriculation }}
            </RouterLink>
            <div class="text-sm text-muted-color">{{ vehicule.site }} · {{ vehicule.blocage }}</div>
          </li>
        </ul>
      </Card>
      <h2 class="text-lg font-semibold">Mes réservations</h2>
      <template #fallback>
        <div class="grid gap-3 sm:grid-cols-3">
          <Kpi v-for="section in sectionsPersonnelles" :key="section.cle" :label="section.titre"
               :value="accueil.reservations[section.cle].total" :icon="section.icon" :color="section.couleur" />
        </div>
      </template>
    </PermissionGuard>
    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
      <ReservationsAccueil v-for="section in sectionsPersonnelles" :key="section.cle"
                           v-bind="section" :groupe="accueil.reservations[section.cle]" :vehicules="vehicules" />
    </div>
    <Card v-if="!gestion" title="Bornes de recharge URCA" icon="pi pi-bolt" color="blue">
      <template #header-actions>
        <RouterLink :to="parc" class="text-sm font-semibold text-primary">Voir les équipements</RouterLink>
      </template>
      <EmptyState v-if="!bornes.length" title="Aucune borne active renseignée" icon="pi pi-bolt" compact />
      <ul v-else class="grid gap-3 sm:grid-cols-3">
        <li v-for="borne in bornes.slice(0, 3)" :key="borne.id">
          <div class="font-semibold">{{ borne.libelle }}</div>
          <div class="text-sm text-muted-color">{{ borne.coordonnees }}</div>
        </li>
      </ul>
    </Card>
  </div>
</template>
