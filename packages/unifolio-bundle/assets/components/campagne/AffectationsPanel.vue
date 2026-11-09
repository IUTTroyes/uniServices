<script setup>
import { computed, onMounted, ref } from 'vue';
import { showDanger, showSuccess } from '@helpers/toast';
import { EmptyState, Card } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import campagneService from '../../requests/campagneService';
import { messageErreur } from '../../requests/traceService';

/**
 * Affectation des évaluateurs d'une campagne : par étudiants cochés, par groupe ou pour toute la campagne.
 * Signale par `change` toute affectation / retrait, pour que la campagne (compteurs) soit rechargée.
 */
const props = defineProps({
  campagne: { type: Object, required: true },
  cloturee: { type: Boolean, default: false },
});
const emit = defineEmits(['change']);

const affectations = ref(null);
const evaluateur = ref(null);
const portee = ref('tous');
const groupe = ref(null);
const cochees = ref([]);
const filtre = ref('');
const occupe = ref(false);

const chargerAffectations = async () => {
  const { data } = await campagneService.affectations(props.campagne.uuid);
  affectations.value = data;
};
onMounted(async () => {
  try {
    await chargerAffectations();
  } catch (e) {
    showDanger(messageErreur(e), 'Affectations indisponibles', 6000);
  }
});

const etudiants = computed(() => {
  const q = filtre.value.trim().toLowerCase();
  return (affectations.value?.etudiants ?? []).filter((e) => !q || `${e.nom} ${e.prenom}`.toLowerCase().includes(q));
});
const nomEvaluateur = (id) => affectations.value?.candidats.find((c) => c.id === id)?.nom ?? affectations.value?.evaluateurs.find((c) => c.id === id)?.nom ?? `#${id}`;
const sansEvaluateur = computed(() => (affectations.value?.etudiants ?? []).filter((e) => e.evaluateurs.length === 0).length);

const peutAgir = computed(() => evaluateur.value && !props.cloturee && (portee.value === 'tous' || (portee.value === 'groupe' && groupe.value) || (portee.value === 'selection' && cochees.value.length)));
const donneesAction = () => ({
  evaluateur: evaluateur.value,
  ...(portee.value === 'groupe' ? { groupe: groupe.value } : {}),
  ...(portee.value === 'selection' ? { etudiants: cochees.value } : {}),
});

const appliquer = async (appel, succes, echec) => {
  occupe.value = true;
  try {
    const { data } = await appel();
    showSuccess(undefined, succes(data), 3000);
    cochees.value = [];
    await chargerAffectations();
    emit('change');
  } catch (e) {
    showDanger(messageErreur(e), echec, 8000);
  } finally {
    occupe.value = false;
  }
};

const affecter = () => appliquer(
  () => campagneService.affecter(props.campagne.uuid, donneesAction()),
  (data) => `${data.crees} affectation(s) créée(s)`,
  'Affectation impossible',
);

const retirer = () => appliquer(
  () => campagneService.desaffecter(props.campagne.uuid, {
    evaluateur: evaluateur.value,
    ...(portee.value === 'selection' ? { etudiants: cochees.value } : {}),
  }),
  (data) => `${data.retirees} affectation(s) retirée(s)`,
  'Retrait impossible',
);

const toutCocher = (coche) => { cochees.value = coche ? etudiants.value.map((e) => e.id) : []; };
</script>

<template>
  <Card bare tag="section" class="p-4 flex flex-col gap-4 xl:col-span-2">
    <div class="flex flex-wrap items-center gap-2">
      <h3 class="text-base font-semibold m-0 flex-1">Affectation des évaluateurs</h3>
      <Tag :value="`${campagne.nbAffectes} / ${campagne.nbEtudiants} étudiants affectés`" :severity="sansEvaluateur === 0 && campagne.nbEtudiants > 0 ? 'success' : 'warn'" />
    </div>

    <div v-if="affectations" class="rounded-lg border border-surface-200 p-3 flex flex-col gap-3">
      <div class="grid gap-3 md:grid-cols-2">
        <div class="flex flex-col gap-1">
          <label for="a-eval" class="text-sm font-medium">Enseignant</label>
          <Select id="a-eval" v-model="evaluateur" :options="affectations.candidats" option-label="nom" option-value="id" filter placeholder="Choisir un enseignant" :disabled="cloturee" />
        </div>
        <div class="flex flex-col gap-1">
          <span class="text-sm font-medium">Étudiants concernés</span>
          <SelectButton
            v-model="portee"
            :options="[{ v: 'tous', l: 'Tous' }, { v: 'groupe', l: 'Un groupe' }, { v: 'selection', l: 'Sélection' }]"
            option-label="l"
            option-value="v"
            :allow-empty="false"
            :disabled="cloturee"
          />
        </div>
      </div>
      <Select
        v-if="portee === 'groupe'"
        v-model="groupe"
        :options="affectations.groupes"
        :option-label="(g) => `${g.libelle} (${g.nbEtudiants})`"
        option-value="id"
        placeholder="Choisir le groupe"
      />
      <p v-if="portee === 'selection'" class="text-sm text-muted-color m-0">Cochez les étudiants dans la liste ci‑dessous ({{ cochees.length }} sélectionné(s)).</p>
      <div class="flex flex-wrap gap-2">
        <Button label="Affecter" icon="pi pi-user-plus" size="small" :disabled="!peutAgir || portee === 'groupe' && !groupe" :loading="occupe" @click="affecter" />
        <Button label="Retirer" icon="pi pi-user-minus" size="small" severity="secondary" outlined :disabled="!evaluateur || cloturee || portee === 'groupe'" :loading="occupe" @click="retirer" />
      </div>
    </div>

    <EmptyState v-if="affectations && affectations.etudiants.length === 0" icon="pi pi-users" color="amber" title="Aucun étudiant" description="Aucun étudiant n'est inscrit dans cette formation pour l'année choisie." compact />

    <div v-else-if="affectations" class="flex flex-col gap-2">
      <InputText v-model="filtre" placeholder="Filtrer les étudiants" aria-label="Filtrer les étudiants" />
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="text-muted-color text-xs border-b border-surface-200">
              <th class="p-2 w-10"><Checkbox binary :model-value="cochees.length > 0 && cochees.length === etudiants.length" aria-label="Tout cocher" @update:model-value="toutCocher" /></th>
              <th class="font-medium p-2">Étudiant</th>
              <th class="font-medium p-2">Année</th>
              <th class="font-medium p-2">Groupes</th>
              <th class="font-medium p-2">Évaluateurs</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in etudiants" :key="e.id" class="border-b border-surface-100">
              <td class="p-2"><Checkbox v-model="cochees" :value="e.id" :aria-label="`Sélectionner ${e.nom} ${e.prenom}`" /></td>
              <td class="p-2 font-medium">{{ e.nom }} {{ e.prenom }}</td>
              <td class="p-2 text-muted-color">{{ e.annee }}</td>
              <td class="p-2 text-muted-color">{{ e.groupes.join(', ') }}</td>
              <td class="p-2">
                <span v-if="e.evaluateurs.length === 0" class="text-amber-700">—</span>
                <Tag v-for="id in e.evaluateurs" :key="id" :value="nomEvaluateur(id)" severity="secondary" class="mr-1" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </Card>
</template>
