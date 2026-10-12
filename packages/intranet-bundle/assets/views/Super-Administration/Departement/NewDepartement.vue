<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { createDepartementService } from "@requests";
import { ErrorView, HeaderComponent, Card } from "@components";
import { useToast } from "primevue/usetoast";

const router = useRouter();
const toast = useToast();

const hasError = ref(false);
const isSubmitting = ref(false);

const form = ref({
  libelle: '',
  couleur: '3B82F6',
  telContact: '',
  siteWeb: '',
  description: '',
  actif: true
});

const save = async () => {
  if (!form.value.libelle || !form.value.libelle.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: 'Le libellé du département est obligatoire.',
      life: 3000
    });
    return;
  }

  try {
    isSubmitting.value = true;
    const payload = {
      libelle: form.value.libelle.trim(),
      couleur: form.value.couleur ? form.value.couleur.replace('#', '') : null,
      telContact: form.value.telContact ? form.value.telContact.trim() : null,
      siteWeb: form.value.siteWeb ? form.value.siteWeb.trim() : null,
      description: form.value.description ? form.value.description.trim() : null,
      actif: Boolean(form.value.actif)
    };

    await createDepartementService(payload);

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Le département a été créé avec succès.',
      life: 3000
    });

    router.push('/intranet/super-administration/departements');
  } catch (error) {
    console.error("Erreur lors de la création du département:", error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: "Une erreur est survenue lors de la création du département.",
      life: 3000
    });
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-building"
    color="indigo"
    titre="Nouveau département"
    description="Créez un nouveau département d'enseignement dans l'établissement."
    :show-back="true"
    back-url="/intranet/super-administration/departements"
  />

  <Card title="Informations du département" icon="pi pi-plus" color="indigo">
    <ErrorView v-if="hasError" />
    <div v-else class="max-w-2xl">
      <form @submit.prevent="save" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="libelle" class="font-semibold text-sm">Libellé <span class="text-red-500">*</span></label>
          <InputText
            id="libelle"
            v-model="form.libelle"
            placeholder="Ex: Informatique, MMI, GEII, TC..."
            class="w-full"
            required
            autofocus
          />
          <small class="text-muted-color">Nom officiel ou usuel du département.</small>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col gap-2">
            <label for="couleur" class="font-semibold text-sm">Couleur représentative</label>
            <div class="flex items-center gap-2">
              <ColorPicker id="couleur" v-model="form.couleur" />
              <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-color font-mono text-sm">#</span>
                <InputText
                  :model-value="form.couleur ? form.couleur.replace('#', '') : ''"
                  @update:model-value="val => form.couleur = val ? val.replace('#', '') : ''"
                  placeholder="3B82F6"
                  class="w-full font-mono text-sm pl-7"
                />
              </div>
            </div>
            <small class="text-muted-color">Couleur thématique dans l'interface.</small>
          </div>

          <div class="flex flex-col gap-2">
            <label for="telContact" class="font-semibold text-sm">Téléphone de contact</label>
            <InputText
              id="telContact"
              v-model="form.telContact"
              placeholder="Ex: 03 25 42 46 00"
              class="w-full"
            />
            <small class="text-muted-color">Numéro du secrétariat.</small>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label for="siteWeb" class="font-semibold text-sm">Site Web</label>
          <InputText
            id="siteWeb"
            v-model="form.siteWeb"
            placeholder="Ex: https://iut-troyes.univ-reims.fr"
            class="w-full"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="description" class="font-semibold text-sm">Description</label>
          <Textarea
            id="description"
            v-model="form.description"
            rows="3"
            placeholder="Description du département..."
            class="w-full"
          />
        </div>

        <div class="flex items-center gap-3 pt-2">
          <Checkbox
            id="actif"
            v-model="form.actif"
            :binary="true"
          />
          <label for="actif" class="font-medium text-sm cursor-pointer select-none">
            Département actif
          </label>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-surface-200 dark:border-surface-700">
          <Button
            type="submit"
            label="Enregistrer le département"
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
            @click="router.push('/intranet/super-administration/departements')"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </div>
  </Card>
</template>

<style scoped>
</style>


