<script setup>
import {onMounted, ref} from "vue";
import {useEtablissementStore} from "@stores";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton, Access } from "@components";

const etablissementStore = useEtablissementStore()
const etablissement = ref()
const isLoadingEtablissement = ref(true)
const hasError = ref(false)
const formValid = ref(true);
const formErrors = ref({});
const logoFile = ref(null);

onMounted(async () => {
  try {
    etablissement.value = await etablissementStore.etablissement;
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching etablissement:', error)
  } finally {
    isLoadingEtablissement.value = false;
  }
})

const updateEtablissement = async () => {

}

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};
</script>

<template>
    <div class="card">
      <h2 class="text-2xl! mb-0! font-bold">Données de l'Établissement</h2>
      <em>Gestion des données d'identification de l'établissement</em>
      <Divider/>

      <ErrorView v-if="hasError"/>
      <template v-else>
        <ListSkeleton v-if="isLoadingEtablissement"/>
        <div v-else>
          <form @submit.prevent="updateEtablissement()" class="flex flex-col">
            <div class="flex flex-row gap-4 items-center">
              <ValidatedInput
                  v-model="etablissement.libelle"
                  name="libelle"
                  label="Libellé"
                  type="text"
                  :rules="[validationRules.required]"
                  @validation="result => handleValidation('libelle', result)"
                  help-text="Entrez le nom de l'établissement"
                  class="w-full"
              />

              <ValidatedInput
                  v-model="logoFile"
                  name="logo"
                  label="Logo"
                  type="file"
                  :rules="[validationRules.required]"
                  @validation="result => handleValidation('logo', result)"
                  help-text="Sélectionnez le logo de l'établissement"
                  class="w-full"
              />
            </div>
          </form>
        </div>
      </template>
    </div>
</template>
