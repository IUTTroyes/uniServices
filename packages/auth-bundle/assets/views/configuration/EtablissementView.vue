<script setup>
import {onMounted, ref} from "vue";
import {useEtablissementStore} from "@stores";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton, Access } from "@components";
import {updateEtablissementService, uploadEtablissementLogoService} from "@requests"

const etablissementStore = useEtablissementStore()
const etablissement = ref()
const isLoadingEtablissement = ref(true)
const hasError = ref(false)
const formValid = ref(true);
const formErrors = ref({});
const logoFile = ref(null);

const ensureAdresseObject = (adresse) => {
  if (adresse && typeof adresse === 'object') {
    return {
      adresse: adresse.adresse ?? '',
      complement1: adresse.complement1 ?? '',
      complement2: adresse.complement2 ?? '',
      ville: adresse.ville ?? '',
      codePostal: adresse.codePostal ?? '',
      pays: adresse.pays ?? 'France'
    };
  }

  if (typeof adresse === 'string') {
    return {
      adresse,
      complement1: '',
      complement2: '',
      ville: '',
      codePostal: '',
      pays: 'France'
    };
  }

  return {
    adresse: '',
    complement1: '',
    complement2: '',
    ville: '',
    codePostal: '',
    pays: 'France'
  };
};

onMounted(async () => {
  try {
    etablissement.value = await etablissementStore.etablissement;
    etablissement.value.adresse = ensureAdresseObject(etablissement.value?.adresse);
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching etablissement:', error)
  } finally {
    isLoadingEtablissement.value = false;
  }
})

const updateEtablissement = async () => {
  try {
    etablissement.value.adresse = ensureAdresseObject(etablissement.value?.adresse);

    await updateEtablissementService(etablissement.value.id, {
      libelle: etablissement.value.libelle,
      adresse: etablissement.value.adresse,
      site_web: etablissement.value.site_web,
    }, true);

    if (logoFile.value) {
      const formData = new FormData();
      formData.append('file', logoFile.value);
      await uploadEtablissementLogoService(etablissement.value.id, formData, true);
    }

  } catch (error) {
    hasError.value = true;
    console.error('Error updating etablissement:', error)
  } finally {
    isLoadingEtablissement.value = false;
  }
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
            <div>

              <ValidatedInput
                  v-model="logoFile"
                  name="logo"
                  label="Logo"
                  type="file"
                  :rules="[]"
                  @validation="result => handleValidation('logo', result)"
                  help-text="Sélectionnez le logo de l'établissement"
                  class="w-full"
              />
            </div>
          </div>
          <ValidatedInput
              v-model="etablissement.site_web"
              name="site_web"
              label="Site web"
              type="text"
              :rules="[]"
              @validation="result => handleValidation('site_web', result)"
              help-text="Entrez l'URL du site web de l'établissement"
              class="w-full"
          />
          <ValidatedInput
              v-model="etablissement.adresse"
              name="adresse"
              label="Adresse"
              type="address"
              :rules="[]"
              @validation="result => handleValidation('adresse', result)"
              placeholder="Entrez l'adresse de l'établissement"
              help-text="Commencez à saisir l'adresse pour obtenir des suggestions"
              class="w-full"
          />
          <Button label="Enregistrer" class="w-full" type="submit" :disabled="!formValid"/>
        </form>
      </div>
    </template>
  </div>
</template>
