<script setup>
import { PermissionGuard } from '@components';
import { dateHeure } from './parcautoDates';
import { libelleOrdreMission } from './ordreMission';

defineProps({ mission: Object, vehicule: String, plage: String, statut: Array, gestion: Boolean });
defineEmits(['action']);
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <div class="font-semibold">{{ vehicule }}</div>
      <div class="text-sm">{{ plage }}</div>
      <div v-if="mission.retourLe" class="text-sm">Retour enregistré le {{ dateHeure(mission.retourLe) }}</div>
      <div v-if="mission.nomConducteur" class="text-sm">
        {{ mission.nomConducteur }} · {{ libelleOrdreMission(mission) }}{{ mission.ordreVerifie ? ' (vérifié par OMER)' : '' }}
        <span v-if="mission.kilometrageDepart !== null"> · Départ {{ mission.kilometrageDepart }} km</span>
        <span v-if="mission.kilometrageRetour !== null"> · Retour {{ mission.kilometrageRetour }} km</span>
      </div>
      <div v-if="mission.motif" class="text-sm">Motif · {{ mission.motif }}</div>
      <div v-if="mission.motifRefus" class="text-sm">Motif du refus · {{ mission.motifRefus }}</div>
      <div v-if="mission.destinations" class="text-sm">Destination(s) · {{ mission.destinations }}</div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <Tag v-if="mission.urgence" severity="warn" value="Urgence · sans validation préalable" />
      <Tag :value="statut[0]" :severity="statut[1]" />
      <PermissionGuard :permission="mission.peutDecider">
        <Button label="Refuser" severity="danger" text size="small" @click="$emit('action', 'refuser', mission)" />
        <Button label="Valider" size="small" @click="$emit('action', 'valider', mission)" />
      </PermissionGuard>
      <PermissionGuard :permission="mission.proprietaire || (gestion && 'canManageParcauto')">
        <Button v-if="mission.statut === 'validee'" label="Départ" size="small" @click="$emit('action', 'depart', mission)" />
        <Button v-if="mission.statut === 'en_cours'" label="Signaler le retour" size="small" @click="$emit('action', 'retour', mission)" />
      </PermissionGuard>
      <PermissionGuard v-if="(mission.proprietaire || gestion) && ['demandee', 'validee'].includes(mission.statut)"
                       :permission="mission.proprietaire || 'canManageParcauto'">
        <Button label="Annuler" size="small" severity="secondary" text @click="$emit('action', 'annuler', mission)" />
      </PermissionGuard>
    </div>
  </div>
</template>
