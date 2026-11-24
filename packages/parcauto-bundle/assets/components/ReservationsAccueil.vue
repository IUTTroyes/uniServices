<script setup>
import { Card, EmptyState } from '@components';
import { dateHeure, jourLocal } from './parcautoDates';

defineProps({
  titre: String,
  icon: String,
  groupe: Object,
  vehicules: Map,
  vide: String,
  couleur: String,
  gestion: Boolean,
});

function lien(reservation) {
  return {
    name: 'parcauto-planning',
    query: {
      date: jourLocal(reservation.enCours ? new Date() : new Date(reservation.debut)),
      reservation: String(reservation.id),
    },
  };
}
</script>

<template>
  <Card :title="titre" :icon="icon" :color="couleur">
    <EmptyState v-if="!groupe.reservations.length" :title="vide" :icon="icon" compact />
    <ul v-else class="divide-y divide-surface-200 dark:divide-surface-700">
      <li v-for="reservation in groupe.reservations" :key="reservation.id">
        <RouterLink :to="lien(reservation)" class="flex items-center justify-between gap-3 py-3 hover:text-primary">
          <div>
            <div class="font-semibold">{{ vehicules.get(reservation.vehicule)?.libelle }} · {{ vehicules.get(reservation.vehicule)?.immatriculation }}</div>
            <Tag v-if="reservation.urgence" severity="warn" value="Urgence · sans validation préalable" />
            <div class="text-sm text-muted-color">
              <span v-if="gestion">{{ reservation.nomConducteur }} · </span>
              {{ reservation.enCours ? 'Retour prévu ' + dateHeure(reservation.fin) : dateHeure(reservation.debut) + ' – ' + dateHeure(reservation.fin) }}
            </div>
          </div>
          <i class="pi pi-chevron-right" aria-hidden="true" />
        </RouterLink>
      </li>
    </ul>
    <RouterLink v-if="groupe.total > groupe.reservations.length" :to="{ name: 'parcauto-planning' }" class="mt-3 inline-block text-sm font-semibold text-primary">
      Voir le planning des réservations
    </RouterLink>
  </Card>
</template>
