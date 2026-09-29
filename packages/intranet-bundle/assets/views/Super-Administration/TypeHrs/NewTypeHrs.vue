<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { createTypeHrsService } from '@requests';
import { ErrorView } from '@components';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isSubmitting = ref(false);

const typeOptions = [
  { label: 'HRS', value: 'HRS' },
  { label: 'PCA', value: 'PCA' },
  { label: 'PRP', value: 'PRP' },
  { label: 'Suivi', value: 'Suivi' },
  { label: 'Autre', value: 'Autre' }
];

const form = ref({
  libelle: '',
  type: 'HRS',
  incluService: false,
  maximum: 96
});

const save = async () => {
  if (!form.value.libelle || !form.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé est obligatoire.',
      life: 3000
    });
    return;
  }

  try {
    isSubmitting.value = true;
    await createTypeHrsService({
      libelle: form.value.libelle.trim(),
      type: form.value.type,
      incluService: Boolean(form.value.incluService),
      maximum: form.value.maximum !== null ? Number(form.value.maximum) : null
    });

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Le type d\'heures a été créé avec succès.',
      life: 3000
    });

    router.push('/intranet/super-administration/types-hrs');
  } catch (error) {
    console.error('Erreur lors de la création du type d\'heures:', error);
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
        <h1 class="text-2xl font-bold">Nouveau type d'heures (HRS / PCA / PRP)</h1>
        <p class="text-muted-color">Créez un nouveau type d'heures complémentaires ou de responsabilités.</p>
      </div>
      <Button
        label="Retour à la liste"
        icon="pi pi-arrow-left"
        severity="secondary"
        outlined
        @click="router.push('/intranet/super-administration/types-hrs')"
      />
    </div>

    <ErrorView v-if="hasError" />
    <div v-else class="max-w-2xl">
      <form @submit.prevent="save" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="type" class="font-semibold text-sm">Catégorie <span class="text-red-500">*</span></label>
          <Select
            id="type"
            v-model="form.type"
            :options="typeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Sélectionner une catégorie"
            class="w-full font-bold"
            required
          />
          <small class="text-muted-color">Classification du type d'heure (HRS, PCA, PRP...).</small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="libelle" class="font-semibold text-sm">Libellé <span class="text-red-500">*</span></label>
          <InputText
            id="libelle"
            v-model="form.libelle"
            placeholder="Ex: Responsabilité de diplôme, Suivi stage..."
            class="w-full"
            required
            autofocus
          />
          <small class="text-muted-color">Intitulé exact de la fonction ou responsabilité.</small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="maximum" class="font-semibold text-sm">Plafond maximum (heures)</label>
          <InputNumber
            id="maximum"
            v-model="form.maximum"
            :min="0"
            :max="1000"
            :minFractionDigits="0"
            :maxFractionDigits="2"
            suffix=" h"
            class="w-full"
          />
          <small class="text-muted-color">Nombre maximal d'heures pouvant être attribuées.</small>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <Checkbox
            id="inclu-service"
            v-model="form.incluService"
            :binary="true"
          />
          <label for="inclu-service" class="font-medium text-sm cursor-pointer select-none">
            Inclus dans le calcul du service obligatoire
          </label>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            type="submit"
            label="Enregistrer le type d'heures"
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
            @click="router.push('/intranet/super-administration/types-hrs')"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
</style>
