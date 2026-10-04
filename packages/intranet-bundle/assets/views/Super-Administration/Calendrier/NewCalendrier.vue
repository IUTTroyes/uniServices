<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { createCalendrierService, getAllAnneesUniversitairesService } from '@requests';
import { ErrorView } from '@components';
import { useAnneeUnivStore } from '@stores';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();
const anneeUnivStore = useAnneeUnivStore();

const hasError = ref(false);
const isSubmitting = ref(false);
const anneesUniv = ref([]);

const form = ref({
  anneeUniversitaire: null,
  semaineFormation: 1,
  semaineReelle: 36,
  dateLundi: new Date()
});

onMounted(async () => {
  try {
    const res = await getAllAnneesUniversitairesService();
    anneesUniv.value = res || [];
    if (anneeUnivStore.anneeUniv?.id) {
      form.value.anneeUniversitaire = `/api/structure_annee_universitaires/${anneeUnivStore.anneeUniv.id}`;
    } else if (anneesUniv.value.length > 0) {
      const active = anneesUniv.value.find(a => a.actif) || anneesUniv.value[0];
      form.value.anneeUniversitaire = `/api/structure_annee_universitaires/${active.id}`;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires:', error);
  }
});

const save = async () => {
  if (!form.value.dateLundi) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'La date du lundi est obligatoire.',
      life: 3000
    });
    return;
  }

  try {
    isSubmitting.value = true;
    const formattedDate = form.value.dateLundi instanceof Date
      ? form.value.dateLundi.toISOString().split('T')[0]
      : form.value.dateLundi;

    const payload = {
      semaineFormation: Number(form.value.semaineFormation),
      semaineReelle: Number(form.value.semaineReelle),
      dateLundi: formattedDate
    };

    if (form.value.anneeUniversitaire) {
      payload.anneeUniversitaire = form.value.anneeUniversitaire;
    }

    await createCalendrierService(payload);

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'La semaine a été ajoutée au calendrier avec succès.',
      life: 3000
    });

    router.push('/intranet/super-administration/calendriers');
  } catch (error) {
    console.error('Erreur lors de la création de la semaine de calendrier:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de la création.',
      life: 3000
    });
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">Nouvelle semaine de calendrier</h1>
        <p class="text-muted-color">Définissez une nouvelle semaine universitaire et sa date de début.</p>
      </div>
      <Button
        label="Retour à la liste"
        icon="pi pi-arrow-left"
        severity="secondary"
        outlined
        @click="router.push('/intranet/super-administration/calendriers')"
      />
    </div>

    <ErrorView v-if="hasError" />
    <div v-else class="max-w-2xl">
      <form @submit.prevent="save" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="annee-univ" class="font-semibold text-sm">Année Universitaire</label>
          <Select
            id="annee-univ"
            v-model="form.anneeUniversitaire"
            :options="anneesUniv.map(a => ({ label: a.libelle, value: `/api/structure_annee_universitaires/${a.id}` }))"
            optionLabel="label"
            optionValue="value"
            placeholder="Sélectionner une année universitaire"
            class="w-full"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label for="semaine-formation" class="font-semibold text-sm">Semaine Formation <span class="text-red-500">*</span></label>
            <InputNumber
              id="semaine-formation"
              v-model="form.semaineFormation"
              :min="1"
              :max="53"
              class="w-full"
              required
            />
            <small class="text-muted-color">Numéro de la semaine de cours (1 à 52).</small>
          </div>

          <div class="flex flex-col gap-2">
            <label for="semaine-reelle" class="font-semibold text-sm">Semaine Réelle (Calendrier) <span class="text-red-500">*</span></label>
            <InputNumber
              id="semaine-reelle"
              v-model="form.semaineReelle"
              :min="1"
              :max="53"
              class="w-full"
              required
            />
            <small class="text-muted-color">Numéro ISO de la semaine dans l'année civile.</small>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label for="date-lundi" class="font-semibold text-sm">Date du lundi <span class="text-red-500">*</span></label>
          <DatePicker
            id="date-lundi"
            v-model="form.dateLundi"
            dateFormat="dd/mm/yy"
            showIcon
            class="w-full"
            required
          />
          <small class="text-muted-color">Date du premier jour (lundi) de la semaine.</small>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            type="submit"
            label="Enregistrer la semaine"
            icon="pi pi-check"
            severity="primary"
            :loading="isSubmitting"
          />
          <Button
            type="button"
            label="Annuler"
            icon="pi pi-times"
            severity="secondary"
            outlined
            @click="router.push('/intranet/super-administration/calendriers')"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
</style>
