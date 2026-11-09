<script setup>
import {onMounted, ref} from "vue";
import {useEtablissementStore} from "@stores";
import { ValidatedInput, validationRules, ErrorState, ListSkeleton } from "@components";
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

// Université de rattachement (objet embarqué côté API : libellé, adresse, téléphone, président).
const ensureUniversite = (universite) => ({
  libelle: universite?.libelle ?? '',
  telephone: universite?.telephone ?? '',
  president: universite?.president ?? '',
  adresse: ensureAdresseObject(universite?.adresse)
});

const nullIfEmpty = (value) => (typeof value === 'string' && value.trim() === '' ? null : value ?? null);

onMounted(async () => {
  try {
    etablissement.value = await etablissementStore.etablissement;
    etablissement.value.adresse = ensureAdresseObject(etablissement.value?.adresse);
    etablissement.value.universite = ensureUniversite(etablissement.value?.universite);
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
    const universite = ensureUniversite(etablissement.value?.universite);

    await updateEtablissementService(etablissement.value.id, {
      libelle: etablissement.value.libelle,
      adresse: etablissement.value.adresse,
      site_web: etablissement.value.site_web,
      telephone: etablissement.value.telephone,
      directeurNom: nullIfEmpty(etablissement.value.directeurNom),
      signataireNom: nullIfEmpty(etablissement.value.signataireNom),
      signataireQualite: nullIfEmpty(etablissement.value.signataireQualite),
      universite: {
        libelle: nullIfEmpty(universite.libelle),
        telephone: nullIfEmpty(universite.telephone),
        president: nullIfEmpty(universite.president),
        adresse: universite.adresse
      }
    }, true);

    if (logoFile.value) {
      const formData = new FormData();
      formData.append('file', logoFile.value);
      await uploadEtablissementLogoService(etablissement.value.id, formData, false);
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
    <em>Gestion des données d'identification de l'établissement (la composante, ex. l'IUT) et de son université de rattachement</em>
    <Divider/>

    <ErrorState v-if="hasError"/>
    <template v-else>
      <ListSkeleton v-if="isLoadingEtablissement"/>
      <div v-else>
        <form @submit.prevent="updateEtablissement()" class="flex flex-col">
          <div class="flex flex-row gap-4 items-center w-full">
            <ValidatedInput
                v-model="etablissement.libelle"
                name="libelle"
                label="Libellé"
                type="text"
                :rules="[validationRules.required]"
                @validation="result => handleValidation('libelle', result)"
                help-text="Entrez le nom de l'établissement"
                class="w-1/2"
            />
            <div class="flex flex-col w-1/2">
              <ValidatedInput
                  v-model="logoFile"
                  name="logo"
                  mode="basic"
                  label="Logo"
                  type="file"
                  accept="image/*"
                  auto="auto"
                  :maxFileSize="2000000"
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
                placeholder="Entrez une adresse (minimum 3 caractères)"
                help-text="Recherchez et sélectionnez l'adresse de l'établissement"
                class="w-full"
                country="fr"
            />
          <ValidatedInput
            v-model="etablissement.telephone"
            name="telephone"
            label="Téléphone"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('telephone', result)"
            help-text="Entrez le numéro de téléphone de l'établissement"
            class="w-full"
            />
          <ValidatedInput
            v-model="etablissement.directeurNom"
            name="directeurNom"
            label="Directeur ou directrice"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('directeurNom', result)"
            help-text="Prénom et nom de la personne qui dirige l'établissement"
            class="w-full"
            />

          <h3 class="text-lg font-bold mt-4 mb-1">Université de rattachement</h3>
          <ValidatedInput
            v-model="etablissement.universite.libelle"
            name="universiteLibelle"
            label="Nom de l'université"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('universiteLibelle', result)"
            help-text="Ex. : Université de Reims Champagne-Ardenne"
            class="w-full"
            />
          <ValidatedInput
            v-model="etablissement.universite.adresse"
            name="universiteAdresse"
            label="Adresse de l'université"
            type="address"
            :rules="[]"
            @validation="result => handleValidation('universiteAdresse', result)"
            placeholder="Entrez une adresse (minimum 3 caractères)"
            help-text="Adresse du siège de l'université"
            class="w-full"
            country="fr"
            />
          <ValidatedInput
            v-model="etablissement.universite.telephone"
            name="universiteTelephone"
            label="Téléphone de l'université"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('universiteTelephone', result)"
            class="w-full"
            />
          <ValidatedInput
            v-model="etablissement.universite.president"
            name="universitePresident"
            label="Président ou présidente de l'université"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('universitePresident', result)"
            help-text="Prénom et nom"
            class="w-full"
            />

          <h3 class="text-lg font-bold mt-4 mb-1">Conventions de stage</h3>
          <ValidatedInput
            v-model="etablissement.signataireNom"
            name="signataireNom"
            label="Signataire des conventions"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('signataireNom', result)"
            help-text="Représentant de l'établissement qui signe les conventions de stage (prénom et nom)"
            class="w-full"
            />
          <ValidatedInput
            v-model="etablissement.signataireQualite"
            name="signataireQualite"
            label="Qualité du signataire"
            type="text"
            :rules="[]"
            @validation="result => handleValidation('signataireQualite', result)"
            help-text="Ex. : Directeur de l'IUT, par délégation du Président"
            class="w-full"
            />

          <Button label="Enregistrer" class="w-full" type="submit" :disabled="!formValid"/>
        </form>
      </div>
    </template>
  </div>
</template>
