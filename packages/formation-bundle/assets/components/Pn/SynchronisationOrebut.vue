<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { getDiplomesService } from '@requests';
import { getSpecialitesOrebutService, synchroniserOrebutService } from '../../service/orebutService.js';

const visible = defineModel('visible', { type: Boolean, default: false });
const props = defineProps({ departementId: { type: Number, default: null } });
const emit = defineEmits(['synchronise']);
const toast = useToast();

const diplomes = ref([]);
const specialites = ref([]);
const diplomeId = ref(null);
const sigle = ref(null);
const parcours = ref(null);
const rapport = ref(null);
const erreur = ref('');
const isLoading = ref(false);
const isSyncing = ref(false);

const parcoursDisponibles = computed(() => specialites.value.find((s) => s.sigle === sigle.value)?.parcours ?? []);
const familles = [
  { cle: 'competences', libelle: 'Compétences' },
  { cle: 'niveaux', libelle: 'Niveaux' },
  { cle: 'apprentissagesCritiques', libelle: 'Apprentissages critiques' },
  { cle: 'parcours', libelle: 'Parcours' },
];
const lignes = computed(() => familles.map((f) => ({ libelle: f.libelle, ...rapport.value?.compteurs?.[f.cle] })));

const message = (e) => e?.response?.data?.message ?? 'La synchronisation a échoué.';

const charger = async () => {
  isLoading.value = true;
  erreur.value = '';
  rapport.value = null;
  diplomeId.value = null;
  try {
    // Toutes les années : un diplôme sans programme doit pouvoir recevoir son référentiel.
    diplomes.value = await getDiplomesService({ departement: props.departementId }, '', false, 'toutes');
    specialites.value = await getSpecialitesOrebutService();
  } catch (e) {
    erreur.value = message(e);
  } finally {
    isLoading.value = false;
  }
};

watch(visible, (ouvert) => {
  if (ouvert) charger();
});

// Propose la spécialité dont le sigle est celui du diplôme.
watch(diplomeId, (id) => {
  const diplome = diplomes.value.find((d) => d.id === id);
  const trouvee = specialites.value.find((s) => s.sigle.toLowerCase() === diplome?.sigle?.toLowerCase());
  sigle.value = trouvee?.sigle ?? null;
});
watch(sigle, () => { parcours.value = null; });
watch([diplomeId, sigle, parcours], () => { rapport.value = null; erreur.value = ''; });

const lancer = async (simulation) => {
  isSyncing.value = true;
  erreur.value = '';
  try {
    const resultat = await synchroniserOrebutService({
      diplomeId: diplomeId.value,
      specialite: sigle.value,
      parcours: parcours.value,
      simulation,
    });
    if (simulation) {
      rapport.value = resultat;
    } else {
      toast.add({ severity: 'success', summary: 'Référentiel synchronisé', detail: resultat.referentiel.libelle, life: 4000 });
      visible.value = false;
      emit('synchronise');
    }
  } catch (e) {
    erreur.value = message(e);
  } finally {
    isSyncing.value = false;
  }
};
</script>

<template>
  <Dialog v-model:visible="visible" modal header="Synchroniser depuis ORéBUT" class="w-full max-w-2xl">
    <div class="flex flex-col gap-4 pt-2">
      <div class="flex flex-col gap-1.5">
        <label for="orebut-diplome" class="font-medium text-sm">Diplôme</label>
        <Select v-model="diplomeId" input-id="orebut-diplome" :options="diplomes" option-label="libelle" option-value="id" :loading="isLoading" placeholder="Choisir un diplôme" class="w-full" />
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="orebut-specialite" class="font-medium text-sm">Spécialité ORéBUT</label>
        <Select v-model="sigle" input-id="orebut-specialite" :options="specialites" :option-label="(s) => `${s.sigle} — ${s.libelle}`" option-value="sigle" :loading="isLoading" filter placeholder="Choisir une spécialité" class="w-full" />
      </div>
      <div v-if="parcoursDisponibles.length" class="flex flex-col gap-1.5">
        <label for="orebut-parcours" class="font-medium text-sm">Parcours de ce diplôme (facultatif)</label>
        <Select v-model="parcours" input-id="orebut-parcours" :options="parcoursDisponibles" option-label="libelle" option-value="code" show-clear placeholder="Ne pas modifier" class="w-full" />
      </div>

      <Message v-if="erreur" severity="error">{{ erreur }}</Message>

      <template v-if="rapport">
        <Message severity="info">
          {{ rapport.referentiel.nouveau ? 'Nouveau référentiel' : 'Mise à jour du référentiel' }}
          « {{ rapport.referentiel.libelle }} » ({{ rapport.referentiel.annee ?? 'année inconnue' }}). Rien n'est encore enregistré.
        </Message>
        <DataTable :value="lignes" size="small">
          <Column field="libelle" header="Élément" />
          <Column field="ajouts" header="Ajouts" />
          <Column field="modifications" header="Modifications" />
          <Column field="inchanges" header="Inchangés" />
        </DataTable>
        <Message v-for="alerte in rapport.alertes" :key="alerte" severity="warn">{{ alerte }}</Message>
        <div v-if="rapport.absents.length">
          <p class="m-0! text-sm font-medium">Absents d'ORéBUT, conservés :</p>
          <ul class="max-h-32 overflow-y-auto text-sm text-muted-color">
            <li v-for="(absent, i) in rapport.absents" :key="i">{{ absent }}</li>
          </ul>
        </div>
      </template>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="visible = false" />
      <Button label="Prévisualiser" icon="pi pi-eye" severity="secondary" :loading="isSyncing && !rapport" :disabled="!diplomeId || !sigle || isSyncing" @click="lancer(true)" />
      <Button label="Appliquer" icon="pi pi-check" :loading="isSyncing && !!rapport" :disabled="!rapport || isSyncing" @click="lancer(false)" />
    </template>
  </Dialog>
</template>
