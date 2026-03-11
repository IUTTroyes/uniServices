<script setup>
import {ref, onMounted} from "vue";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton, Access } from "@components";
import {useAnneeUnivStore, useDiplomeStore} from "@stores";
import {createAnneeUniversitaireService} from "@requests";

const hasError = ref(false);

const anneeUnivStore = useAnneeUnivStore();
const anneeUniv = ref({
  libelle: "",
  actif: false,
});
const currentAnneeUniv = ref(null);
const formValid = ref(true);
const formErrors = ref(null);
const activeChoice = ref([
  {
    label: "Non",
    value: false
  },
  {
    label: "Oui",
    value: true
  }
]);

const diplomeStore = useDiplomeStore();
const diplomes = ref([]);
const selectedDiplomes = ref([]);
const isLoadingDiplomes = ref(false);


onMounted(async () => {
  await getCurrentAnneeUniv();
  await getDiplomes();
});

const getCurrentAnneeUniv = async () => {
  try {
    await anneeUnivStore.getCurrentAnneeUniv();
    currentAnneeUniv.value = await anneeUnivStore.anneeUniv;
    if (currentAnneeUniv.value) {
      anneeUniv.value.annee = currentAnneeUniv.value.annee + 1; // Proposer l'année suivante

      // Calcul du libellé de l'année suivante (ex: "2025-2026" -> "2026-2027")
      const [anneeDebut, anneeFin] = currentAnneeUniv.value.libelle.split('-').map(Number);
      anneeUniv.value.libelle = `${anneeDebut + 1}-${anneeFin + 1}`;
    }
  } catch (error) {
    console.error("Erreur lors de la récupération de l'année universitaire actuelle:", error);
    hasError.value = true;
  } finally {
    handleValidation('libelle', { isValid: !!anneeUniv.value.libelle, errorMessage: "Le libellé est requis." });
    handleValidation('annee', { isValid: !!anneeUniv.value.annee, errorMessage: "L'année est requise." });
  }
};

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const toggleDiplome = (diplomeId) => {
  const index = selectedDiplomes.value.indexOf(diplomeId);
  if (index > -1) {
    selectedDiplomes.value.splice(index, 1);
  } else {
    selectedDiplomes.value.push(diplomeId);
  }
};

const isDiplomeSelected = (diplomeId) => {
  return selectedDiplomes.value.includes(diplomeId);
};

const createAnneeUniv = async () => {
  try {
    const data = {
      libelle: anneeUniv.value.libelle,
      annee: anneeUniv.value.annee,
      commentaire: anneeUniv.value.commentaire,
      actif: anneeUniv.value.actif,
      diplomes: selectedDiplomes.value.map(id => `/api/structure_diplomes/${id}`)
    };
    // Appeler le service pour créer l'année universitaire
    await createAnneeUniversitaireService(data, true)
    console.log("Année universitaire créée:", anneeUniv.value);
  } catch (error) {
    console.error("Erreur lors de la création de l'année universitaire:", error);
  } finally {

  }
};

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    diplomes.value = diplomeStore.diplomes;
    // Par défaut, sélectionner tous les diplômes
    selectedDiplomes.value = (diplomes.value || []).map(d => d.id);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des diplômes:", error);
  } finally {
    isLoadingDiplomes.value = false;
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl font-bold">Nouvelle Année Universitaire</h1>
      <p class="text-muted-color">Créez une nouvelle année universitaire en remplissant le formulaire ci-dessous.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>
      <PermissionGuard permission="isSuperAdmin" :showFallback="true">
        <Message v-if="currentAnneeUniv" severity="info" class="mb-4 flex items-center flex-col justify-center text-center">
          L'année universitaire actuelle est : <strong>{{ currentAnneeUniv.libelle }}</strong>.
          <br>
          Créer une année universitaire va lancer la synchronisation des données pour la nouvelle année, depuis ORéOF. Vous recevrez une notification lorsque la synchronisation sera terminée.
        </Message>

        <form @submit.prevent="createAnneeUniv()" class="flex flex-col">
          <div>
            <div class="flex flex-row gap-4 items-center">
              <ValidatedInput
                  v-model="anneeUniv.libelle"
                  name="libelle"
                  label="Libellé"
                  type="text"
                  :rules="[validationRules.required]"
                  @validation="result => handleValidation('libelle', result)"
                  help-text="Entrez le libellé de l'évaluation (ex: 2024-2025)"
                  class="w-full"
              />

              <ValidatedInput
                  v-model="anneeUniv.annee"
                  name="annee"
                  label="Année"
                  type="number"
                  :rules="[validationRules.required]"
                  @validation="result => handleValidation('annee', result)"
                  help-text="Sélectionnez la date de début de l'année universitaire"
                  class="w-full"
              />
            </div>

            <ValidatedInput
                v-model="anneeUniv.commentaire"
                name="commentaire"
                label="Commentaire"
                type="textarea"
                :rules="[]"
                @validation="result => handleValidation('commentaire', result)"
                help-text="Entrez un commentaire optionnel pour cette année universitaire"
            />

            <div class="mb-4">
              <label class="block mb-2 font-medium">Définir l'année universitaire comme active après sa création ?</label>
              <div class="flex gap-4">
                <div v-for="choice in activeChoice" :key="choice.value" class="flex items-center gap-2">
                  <RadioButton
                      v-model="anneeUniv.actif"
                      :inputId="`actif-${choice.value}`"
                      name="actif"
                      :value="choice.value"
                  />
                  <label :for="`actif-${choice.value}`">{{ choice.label }}</label>
                </div>
              </div>
            </div>
          </div>

          <Divider></Divider>

          <div>
            <div class="card-title mb-4">
              <h1 class="text-xl font-bold">Gestion des diplômes</h1>
              <p class="text-muted-color">Définissez quels diplômes seront associés à la nouvelle année universitaire.</p>
            </div>
            <ListSkeleton v-if="isLoadingDiplomes" :count="3" class="mb-4" />
            <template v-else>
              <div v-for="diplome in diplomes" :key="diplome.id">
                <div class="p-2 border rounded mb-2 flex justify-between items-center">
                  <h3 class="font-semibold">{{ diplome.libelle }}</h3>
                  <ToggleButton
                      :modelValue="isDiplomeSelected(diplome.id)"
                      @update:modelValue="toggleDiplome(diplome.id)"
                      onLabel="Associé"
                      offLabel="Non associé"
                      onIcon="pi pi-check"
                      offIcon="pi pi-times"
                  />
                </div>
              </div>
            </template>
          </div>

          <div class="flex justify-center items-center gap-4">
            <Button label="Créer l'année universitaire" @click="createAnneeUniv" :disabled="!formValid" />
            <Button label="Annuler" severity="secondary"/>
          </div>
        </form>

        <template #fallback>
          <Access></Access>
        </template>
      </PermissionGuard>
    </template>
  </div>
</template>

<style scoped>

</style>
