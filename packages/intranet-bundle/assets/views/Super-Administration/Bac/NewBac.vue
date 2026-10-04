<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { createBacService } from '@requests';
import { ErrorView } from '@components';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isSubmitting = ref(false);

const form = ref({
  libelle: '',
  libelleLong: '',
  codeApogee: ''
});

const save = async () => {
  if (!form.value.libelle || !form.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé court est obligatoire.',
      life: 3000
    });
    return;
  }

  try {
    isSubmitting.value = true;
    await createBacService({
      libelle: form.value.libelle.trim(),
      libelleLong: form.value.libelleLong ? form.value.libelleLong.trim() : form.value.libelle.trim(),
      codeApogee: form.value.codeApogee ? form.value.codeApogee.trim() : null
    });

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Le type de bac a été créé avec succès.',
      life: 3000
    });

    router.push('/intranet/super-administration/bacs');
  } catch (error) {
    console.error('Erreur lors de la création du bac:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de la création du bac.',
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
        <h1 class="text-2xl font-bold">Nouveau type de bac</h1>
        <p class="text-muted-color">Créez un nouveau type de baccalauréat dans l'établissement.</p>
      </div>
      <Button
        label="Retour à la liste"
        icon="pi pi-arrow-left"
        severity="secondary"
        outlined
        @click="router.push('/intranet/super-administration/bacs')"
      />
    </div>

    <ErrorView v-if="hasError" />
    <div v-else class="max-w-2xl">
      <form @submit.prevent="save" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="libelle" class="font-semibold text-sm">Libellé court <span class="text-red-500">*</span></label>
          <InputText
            id="libelle"
            v-model="form.libelle"
            placeholder="Ex: Général, STI2D, STMG, Pro..."
            class="w-full"
            required
            autofocus
          />
          <small class="text-muted-color">Libellé synthétique affiché dans les tableaux et filtres.</small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="libelle-long" class="font-semibold text-sm">Libellé long</label>
          <InputText
            id="libelle-long"
            v-model="form.libelleLong"
            placeholder="Ex: Baccalauréat Général"
            class="w-full"
          />
          <small class="text-muted-color">Description complète du baccalauréat.</small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="code-apogee" class="font-semibold text-sm">Code Apogée</label>
          <InputText
            id="code-apogee"
            v-model="form.codeApogee"
            placeholder="Ex: S, ES, L, 0001..."
            class="w-full font-mono"
          />
          <small class="text-muted-color">Code de synchronisation Apogée (facultatif).</small>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            type="submit"
            label="Enregistrer le bac"
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
            @click="router.push('/intranet/super-administration/bacs')"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
</style>
