<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { createTypeDiplomeService } from '@requests';
import { ErrorView } from '@components';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isSubmitting = ref(false);

const form = ref({
  libelle: '',
  sigle: '',
  apc: false
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

  if (!form.value.sigle || !form.value.sigle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le sigle est obligatoire.',
      life: 3000
    });
    return;
  }

  try {
    isSubmitting.value = true;
    await createTypeDiplomeService({
      libelle: form.value.libelle.trim(),
      sigle: form.value.sigle.trim().toUpperCase(),
      apc: Boolean(form.value.apc)
    });

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Le type de diplôme a été créé avec succès.',
      life: 3000
    });

    router.push('/intranet/super-administration/types-diplomes');
  } catch (error) {
    console.error('Erreur lors de la création du type de diplôme:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de la création du type de diplôme.',
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
        <h1 class="text-2xl font-bold">Nouveau type de diplôme</h1>
        <p class="text-muted-color">Créez un nouveau type de diplôme dans l'établissement.</p>
      </div>
      <Button
        label="Retour à la liste"
        icon="pi pi-arrow-left"
        severity="secondary"
        outlined
        @click="router.push('/intranet/super-administration/types-diplomes')"
      />
    </div>

    <ErrorView v-if="hasError" />
    <div v-else class="max-w-2xl">
      <form @submit.prevent="save" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="sigle" class="font-semibold text-sm">Sigle <span class="text-red-500">*</span></label>
          <InputText
            id="sigle"
            v-model="form.sigle"
            placeholder="Ex: BUT, LP, DUT, MASTER..."
            class="w-full uppercase font-mono font-bold"
            required
            autofocus
          />
          <small class="text-muted-color">Sigle usuel du diplôme (ex: BUT, LP, etc.).</small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="libelle" class="font-semibold text-sm">Libellé complet <span class="text-red-500">*</span></label>
          <InputText
            id="libelle"
            v-model="form.libelle"
            placeholder="Ex: Bachelor Universitaire de Technologie"
            class="w-full"
            required
          />
          <small class="text-muted-color">Libellé officiel complet du type de formation.</small>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <Checkbox
            id="apc"
            v-model="form.apc"
            :binary="true"
          />
          <label for="apc" class="font-medium text-sm cursor-pointer select-none">
            Diplôme sous Approche Par Compétences (APC)
          </label>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            type="submit"
            label="Enregistrer le type de diplôme"
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
            @click="router.push('/intranet/super-administration/types-diplomes')"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
</style>
