<script setup>
import { computed, onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import adminService from '../../requests/adminService';
import { messageErreur } from '../../requests/traceService';
import { GRANULARITES, libelleGranularite, PORTEES } from '../../components/grilles/options';

const toast = useToast();
const donnees = ref(null);
const lignes = ref([]);
const chargement = ref(true);
const erreur = ref(null);

const versLigne = (f, anneeCourante) => ({
  ...f,
  form: {
    granularite: f.parametrage?.granularite ?? null,
    agregation: f.parametrage?.agregation ?? 'max',
    grille: f.parametrage?.grille ?? null,
    applicableDepuis: anneeCourante?.id ?? null,
  },
  enregistrement: false,
});

const charger = async () => {
  chargement.value = true;
  try {
    const { data } = await adminService.formations();
    donnees.value = data;
    lignes.value = data.formations.map((f) => versLigne(f, data.anneeCourante));
  } catch (e) {
    erreur.value = messageErreur(e, 'Impossible de charger les formations.');
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const grillesPour = (ligne) => [
  { uuid: null, libelle: 'Automatique (grille la plus spécifique)' },
  ...ligne.grilles
    .filter((g) => g.granularite === ligne.form.granularite)
    .map((g) => ({ ...g, libelle: `${g.libelle} — ${PORTEES[g.portee]?.label ?? ''}` })),
];

const modifiee = (ligne) =>
  ligne.form.granularite !== (ligne.parametrage?.granularite ?? null)
  || ligne.form.agregation !== (ligne.parametrage?.agregation ?? 'max')
  || ligne.form.grille !== (ligne.parametrage?.grille ?? null)
  || (ligne.parametrage && ligne.form.applicableDepuis !== donnees.value.anneeCourante?.id);

const surGranularite = (ligne) => {
  if (!grillesPour(ligne).some((g) => g.uuid === ligne.form.grille)) ligne.form.grille = null;
};

const enregistrer = async (ligne) => {
  ligne.enregistrement = true;
  try {
    await adminService.parametrerFormation(ligne.id, ligne.form);
    toast.add({ severity: 'success', summary: 'Paramétrage enregistré', detail: ligne.libelle, life: 3000 });
    const { data } = await adminService.formations();
    donnees.value = data;
    const maj = data.formations.find((f) => f.id === ligne.id);
    if (maj) Object.assign(ligne, versLigne(maj, data.anneeCourante));
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Enregistrement impossible', detail: messageErreur(e), life: 7000 });
  } finally {
    ligne.enregistrement = false;
  }
};

const nonParametrees = computed(() => lignes.value.filter((l) => !l.parametrage).length);
</script>

<template>
  <HeaderComponent
    icon="pi pi-sliders-h"
    titre="Paramétrage des formations"
    description="Pour chaque BUT : sur quoi les traces sont rattachées et évaluées, avec quelle grille."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  />

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState v-else-if="lignes.length === 0" icon="pi pi-sliders-h" color="emerald" title="Aucune formation BUT" description="Aucun BUT n'est rattaché à votre département." />

  <div v-else class="flex flex-col gap-4">
    <Message v-if="nonParametrees" severity="warn">
      {{ nonParametrees }} formation(s) non paramétrée(s) : leurs étudiants peuvent rattacher leurs traces à n'importe quel niveau du référentiel.
    </Message>

    <div class="grid gap-2 sm:grid-cols-3 text-sm">
      <div v-for="g in GRANULARITES" :key="g.value" class="card p-3 m-0">
        <strong>{{ g.label }}</strong>
        <p class="m-0 text-muted-color">{{ g.description }}</p>
      </div>
    </div>

    <div v-for="ligne in lignes" :key="ligne.id" class="card p-4 flex flex-col gap-3">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
          <div class="font-semibold">{{ ligne.libelle }}</div>
          <div class="text-sm text-muted-color">{{ [ligne.parcours, ligne.departement].filter(Boolean).join(' · ') }}</div>
        </div>
        <Tag
          v-if="ligne.parametrage"
          :value="`${libelleGranularite(ligne.parametrage.granularite)} depuis ${ligne.parametrage.applicableDepuis.libelle}`"
          severity="success"
        />
        <Tag v-else value="Non paramétrée" severity="warn" />
      </div>

      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="flex flex-col gap-1">
          <label :for="`gran-${ligne.id}`" class="text-sm font-medium">Rattacher et évaluer par</label>
          <Select :id="`gran-${ligne.id}`" v-model="ligne.form.granularite" :options="GRANULARITES" option-label="label" option-value="value" placeholder="Choisir" @change="surGranularite(ligne)" />
        </div>
        <div class="flex flex-col gap-1">
          <label :for="`grille-${ligne.id}`" class="text-sm font-medium">Grille</label>
          <Select :id="`grille-${ligne.id}`" v-model="ligne.form.grille" :options="grillesPour(ligne)" option-label="libelle" option-value="uuid" :disabled="!ligne.form.granularite" />
        </div>
        <div class="flex flex-col gap-1">
          <label :for="`agr-${ligne.id}`" class="text-sm font-medium">Plusieurs traces sur un même élément</label>
          <Select :id="`agr-${ligne.id}`" v-model="ligne.form.agregation" :options="donnees.agregations" option-label="libelle" option-value="value" />
        </div>
        <div class="flex flex-col gap-1">
          <label :for="`annee-${ligne.id}`" class="text-sm font-medium">Applicable à partir de</label>
          <Select :id="`annee-${ligne.id}`" v-model="ligne.form.applicableDepuis" :options="donnees.anneesUniversitaires" option-label="libelle" option-value="id" />
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-2">
        <small v-if="ligne.historique.length > 1" class="text-muted-color">
          Historique :
          {{ ligne.historique.map((h) => `${h.applicableDepuis.libelle} → ${libelleGranularite(h.granularite)}`).join(' · ') }}
        </small>
        <span v-else />
        <Button
          label="Enregistrer"
          icon="pi pi-save"
          size="small"
          :disabled="!ligne.form.granularite || !modifiee(ligne)"
          :loading="ligne.enregistrement"
          @click="enregistrer(ligne)"
        />
      </div>
    </div>
  </div>
</template>
