<script setup>
import {SimpleSkeleton} from "@components";
import {ref, onMounted} from "vue";

const isLoadingAnnees = ref(true);
const selectedAnnee = ref(null);
const anneesList = ref([]);

const persistPrevi = ref(false);
</script>

<template>
  <div class="card my-6 mx-4 flex flex-col gap-6">
    <div>
      <h3 class="text-lg font-bold">Importer un nouveau prévisionnel</h3>
      <Divider/>
    </div>
    <div>
      <h3 class="text-lg font-bold">Dupliquer un prévisionnel</h3>
      <Divider/>
      <div class="w-full flex gap-4">
        <div class="flex flex-col gap-1 w-2/5">
          <SimpleSkeleton v-if="!isLoadingAnnees" />
          <IftaLabel v-else class="w-full">
            <Select
                v-model="selectedAnnee"
                :options="anneesList"
                optionLabel="libelle"
                placeholder="Sélectionner une année"
                class="w-full"
            />
            <label for="annee">Année à copier</label>
          </IftaLabel>
          <div class="flex items-center gap-2 text-muted-color">
            <Checkbox v-model="persistPrevi" input-id="persistPrevi" binary />
            <label for="persistPrevi">Conserver les données dans l'année de destination ? (par défaut la copie remplace l'ensemble des données)</label>
          </div>
        </div>
        <div class="flex gap-4 w-3/5 h-fit">
            <div class="w-2/3">
              <SimpleSkeleton v-if="!isLoadingAnnees" />
              <IftaLabel v-else class="w-full">
                <Select
                    v-model="selectedAnnee"
                    :options="anneesList"
                    optionLabel="libelle"
                    placeholder="Sélectionner une année"
                    class="w-full"
                />
                <label for="annee">Année de destination</label>
              </IftaLabel>
            </div>
        <Button label="Dupliquer un prévisionnel" icon="pi pi-copy" class="w-1/3" />
        </div>
      </div>
    </div>
    <div>
      <h3 class="text-lg font-bold">Supprimer un prévisionnel</h3>
      <Divider/>
    </div>
  </div>
</template>

