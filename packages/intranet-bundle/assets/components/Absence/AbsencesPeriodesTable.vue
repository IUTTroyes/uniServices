<script setup>
import {computed, ref, watch} from 'vue';
import {EdtEventRow, ButtonInfo} from '@components';
const props = defineProps({
  absences: {type: Array, default: () => []},
  totalAbsences: {type: Number, default: 0},
  offset: {type: Number, default: 0},
  limit: {type: Number, default: 5},
  rowOptions: {type: Array, default: () => [5, 10, 20, 50]},
});
const emit = defineEmits(['page']);
const selectedEpisodeId = ref(null);
const selectedEpisode = computed(() => props.absences.find(item => item.id === selectedEpisodeId.value));
const showDetailsDialog = ref(false);
watch(selectedEpisode, episode => {
  if (!episode) showDetailsDialog.value = false;
});
const formatEventForRow = (event) => {
  const debutEvent = event?.debut
      ? new Date(event.debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      : '--:--';
  const finEvent = event?.fin
      ? new Date(event.fin).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      : '--:--';

  return {
    id: event?.id,
    heure: `${debutEvent} - ${finEvent}`,
    groupe: event?.libGroupe || event?.codeGroupe || '-',
    cours: event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || event),
    salle: event?.salle || '-',
    color: event?.couleur,
    intervenant: event?.intervenant || event?.personnel?.display || '-',
  };
};

const formatEpisodePeriod = (absence) => {
  if (!absence?.dateDebut) return '-';

  const dateDebut = new Date(absence.dateDebut);
  const dateFin = absence?.dateFin ? new Date(absence.dateFin) : dateDebut;
  const sameDay = absence.dateDebut === absence.dateFin;

  if (sameDay) {
    return dateDebut.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
  }

  const memeMoisEtAnnee = dateDebut.getMonth() === dateFin.getMonth() && dateDebut.getFullYear() === dateFin.getFullYear();
  if (memeMoisEtAnnee) {
    return `${dateDebut.toLocaleDateString('fr-FR', { day: '2-digit' })}-${dateFin.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}`;
  }

  return `${dateDebut.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })} - ${dateFin.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })}`;
};

const getEpisodeSummary = (absence) => {
  const joursCount = absence?.joursCount || 0;
  const creneauxCount = absence?.creneauxCount || 0;
  const mode = absence?.mode || 'episode';

  if (mode === 'flat') {
    const event = absence?.events?.[0];
    const module = event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || '-');
    return `${creneauxCount} créneau${creneauxCount > 1 ? 'x' : ''} - ${module}`;
  }

  return `${joursCount} jour${joursCount > 1 ? 's' : ''}, ${creneauxCount} créneau${creneauxCount > 1 ? 'x' : ''}`;
};

const detailAbsences = computed(() => selectedEpisode.value?.absences || []);

const openDetailsModal = (episode) => {
  selectedEpisodeId.value = episode.id;
  showDetailsDialog.value = true;
};

const formatLifecycleDate = (value) => {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};


</script>
<template>
          <DataTable
              :value="absences"
              lazy
              striped-rows
              class="w-full"
              paginator
              :first="offset"
              :rows="limit"
              :rowsPerPageOptions="rowOptions"
              :totalRecords="totalAbsences"
              @page="emit('page', $event)"

          >
            <Column header="Étudiant">
              <template #body="slotProps">
                {{ slotProps.data.etudiantDisplay || '-' }}
              </template>
            </Column>
            <Column>
              <template #header>
                <span class="font-semibold">
                Période
                </span>
              </template>
              <template #body="slotProps">
                <div class="flex flex-col gap-2">
                  <div class="font-semibold">
                    {{ formatEpisodePeriod(slotProps.data) }}
                  </div>
                  <small class="text-muted-color">
                    {{ getEpisodeSummary(slotProps.data) }}
                  </small>
                </div>
              </template>
            </Column>
            <Column field="justifiee" header="Justifiée">
              <template #body="slotProps">
                <Badge v-if="slotProps.data.justifiee" severity="success">Oui</Badge>
                <Badge v-else severity="danger">Non</Badge>
              </template>
            </Column>
            <Column>
              <template #header>
                <span class="font-semibold">
                Actions
                </span>
              </template>
              <template #body="slotProps">
                <ButtonInfo tooltip="Voir les détails" @click="openDetailsModal(slotProps.data)" />

              </template>
            </Column>
            <template #footer> {{ totalAbsences }} résultat(s).</template>
          </DataTable>

          <Dialog
              header="Détail de la période"
              :visible="showDetailsDialog"
              modal
              dismissable-mask
              :style="{ width: '90vw' }"
              :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
              @update:visible="showDetailsDialog = $event"
          >
            <div class="flex flex-col gap-4">
              <div>
                <div class="font-semibold">{{ selectedEpisode?.etudiantDisplay || '-' }}</div>
                <div class="font-semibold">{{ formatEpisodePeriod(selectedEpisode) }}</div>
                <small class="text-muted-color">{{ getEpisodeSummary(selectedEpisode) }}</small>
              </div>
              <DataTable :value="detailAbsences" striped-rows class="w-full">
                <Column header="Événement">
                  <template #body="slotProps">
                    <EdtEventRow :item="formatEventForRow(slotProps.data.event)" />
                  </template>
                </Column>
                <Column header="Saisie par">
                  <template #body="slotProps">
                    {{ slotProps.data.personnelDisplay || '-' }}
                  </template>
                </Column>
                <Column header="Horodatage">
                  <template #body="slotProps">
                    <div class="flex flex-col gap-1">
                      <small>Saisie le : {{ formatLifecycleDate(slotProps.data.created) }}</small>
                      <small v-if="formatLifecycleDate(slotProps.data.created) !== formatLifecycleDate(slotProps.data.updated)">Modifiée : {{ formatLifecycleDate(slotProps.data.updated) }}</small>
                    </div>
                  </template>
                </Column>

                <Column v-if="$slots['detail-actions']" header="Actions">
                  <template #body="{data}">
                    <slot name="detail-actions" :absence="data" />
                  </template>
                </Column>
              </DataTable>
            </div>
          </Dialog>
</template>
