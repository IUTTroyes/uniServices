<script setup>
import {ref, onMounted, computed, watch} from 'vue';
import {PermissionGuard, ValidatedInput, validationRules} from '@components';
import {createActuService} from '@requests';
import {formatDateCourt} from '@helpers/date.js'
import {useUsersStore} from '@stores'

const showNewActuForm = ref(false);
const showActuDialog = ref(false);
const hasError = ref(false);
const formValid = ref(true);
const formErrors = ref({});
const userStore = useUsersStore();
const departement = userStore.departementDefaut;

// Formulaire de création
const newActuForm = ref({
  libelle: '',
  description: '',
  public: [],
  departement: departement ? `/api/structure_departements/${departement.id}` : null,
  actif: true,
  dateDebut: null,
  dateFin: null,
  link: '',
  created: null,
});

// Options possibles pour le champ "public"
const publicOptions = ref([
  {label: 'Étudiant', value: 'etudiant'},
  {label: 'Personnel', value: 'personnel'},
]);
// Options possibles pour le champ "actif"
const actifOptions = ref([
  {label: 'Oui', value: true},
  {label: 'Non', value: false},
]);

const props = defineProps({
  data: {
    type: Object,
    default: () => ({items: []}),
  },
});
const emit = defineEmits(['actu-created']);

// Local reactive copy of items (do not mutate props directly)
const items = ref(props.data.items ? [...props.data.items] : []);
watch(() => props.data.items, (v) => { items.value = v ? [...v] : []; });

onMounted(() => {
  console.log(props.data);
});

// Pagination for Timeline (client-side)
const rowsPerPage = 4;
const first = ref(0); // index of first item on current page
const totalItems = computed(() => items.value?.length || 0);
const pagedItems = computed(() => {
  return items.value.slice(first.value, first.value + rowsPerPage);
});

const onPage = (event) => {
  // PrimeVue Paginator returns an event with `first` index
  first.value = event.first ?? 0;
};

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const formatDateForApi = (value) => {
  if (!value) {
    return null;
  }

  const date = value instanceof Date ? value : new Date(value);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toISOString(); // toujours format complet, ex: 2026-09-02T00:00:00.000Z
};

const resetForm = () => {
  newActuForm.value = {
    libelle: '',
    description: '',
    public: [],
    departement: departement ? `/api/structure_departements/${departement.id}` : null,
    actif: true,
    dateDebut: null,
    dateFin: null,
    link: '',
  };
  formErrors.value = {};
  showNewActuForm.value = false;
};

const createActu = async () => {
  if (!formValid.value) {
    hasError.value = true;
    return;
  }

  const payload = {
    libelle: newActuForm.value.libelle,
    description: newActuForm.value.description,
    public: newActuForm.value.public,
    departement: newActuForm.value.departement,
    actif: newActuForm.value.actif,
    dateDebut: formatDateForApi(newActuForm.value.dateDebut),
    dateFin: formatDateForApi(newActuForm.value.dateFin),
    link: newActuForm.value.link,
  };

  try {
    console.log('Payload for API:', payload);
    const response = await createActuService(payload, '', true);

    // Mettre à jour la liste des Actus
    if (response) {
      // update local list for immediate UI feedback
      items.value.unshift(response);
      // inform parent so it can update its data source if needed
      emit('actu-created', response);
      console.log('Actu created successfully:', response);
    } else {
      console.error('Unexpected response format:', response);
    }
  } catch (error) {
    console.error('Error creating actu:', error);
  }

  resetForm();
};
</script>

<template>
  <div class="flex flex-col justify-between gap-4">
    <div v-if="items.length > 0" class="w-full">
      <Timeline :value="pagedItems" align="left" class="w-full">
      <template #content="slotProps">
        <div class="text-sm leading-4 flex flex-col">
          <div v-if="slotProps.item.dateDebut && slotProps.item.dateFin" class="text-muted-color">
            du {{ formatDateCourt(slotProps.item.dateDebut) }} au {{formatDateCourt(slotProps.item.dateFin)}}
          </div>
          <div v-else-if="slotProps.item.dateDebut" class="text-muted-color">
            {{ formatDateCourt(slotProps.item.dateDebut) }}
          </div>
          <div class="font-semibold">
            {{ slotProps.item.libelle }}
          </div>
        </div>
      </template>
      </Timeline>

      <!-- Paginator affiché seulement s'il y a plus de rowsPerPage éléments -->
      <div v-if="totalItems > rowsPerPage" class="flex justify-center mt-2">
        <Paginator :first="first" :rows="rowsPerPage" :totalRecords="totalItems" @page="onPage" :rows-per-page-options="[4]" />
      </div>
    </div>

    <Message v-else severity="info" icon="pi pi-info-circle">
      Aucune actualité disponible.
    </Message>

    <PermissionGuard :permissions="['ROLE_ADMIN']">
      <Button size="small" severity="primary" label="Gérer les actus" icon="pi pi-pencil" @click="showActuDialog = true"/>
    </PermissionGuard>
  </div>

  <Dialog
      header="Actualités du département"
      :visible="showActuDialog"
      modal
      dismissable-mask
      :style="{ width: '90vw' }"
      :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
      @update:visible="showActuDialog = $event"
  >
    <DataTable
    :value="items"
        :paginator="true"
        :rows="5"
        striped-rows
        removableSort
        sortMode="multiple"
        :rows-per-page-options="[5, 10, 20]"
        responsive-layout="scroll"
        class="w-full mb-6"
    >
      <Column field="created" header="Date de publication" sortable>
        <template #body="slotProps">
          {{ slotProps.data.created ? formatDateCourt(slotProps.data.created) : '' }}
        </template>
      </Column>
      <Column field="libelle" header="Titre" sortable></Column>
      <Column field="dateDebut" header="Date de début" sortable>
      <template #body="slotProps">
        {{ slotProps.data.dateDebut ? formatDateCourt(slotProps.data.dateDebut) : '' }}
      </template>
      </Column>
      <Column field="dateFin" header="Date de fin" sortable>
        <template #body="slotProps">
          {{ slotProps.data.dateFin ? formatDateCourt(slotProps.data.dateFin) : '' }}
        </template>
      </Column>
      <Column field="link" header="Lien" sortable></Column>
      <Column field="description" header="Description" sortable></Column>
      <Column field="public" header="Public" sortable>
        <template #body="slotProps">
          {{ slotProps.data.public?.join(', ') }}
        </template>
      </Column>
      <Column field="actif" header="Actif" sortable></Column>

      <template #empty>
        <Message severity="info" icon="pi pi-info-circle">
          Aucune actualité disponible.
        </Message>
      </template>
    </DataTable>

    <Divider></Divider>

    <Button size="small" severity="primary" label="Créer une actus" icon="pi pi-plus" @click="showNewActuForm = true"/>

    <div v-if="showNewActuForm">
      <form @submit.prevent="createActu()" class="m-12 w-full flex justify-center">
        <div class="p-12 bg-surface-300/20 rounded-lg flex flex-col gap-4 w-1/2">
          <div>Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</div>
          <ValidatedInput
              v-model="newActuForm.libelle"
              name="libelle"
              label="Titre"
              type="text"
              :rules="[validationRules.required]"
              @validation="result => handleValidation('libelle', result)"
              help-text="Entrez le titre de l'actu."
          />

          <div class="flex items-center gap-6">
            <ValidatedInput
                v-model="newActuForm.dateDebut"
                name="dateDebut"
                label="Date de début"
                type="date"
                @validation="result => handleValidation('dateDebut', result)"
                help-text="Entrez la date de début de l'actu."
                class="w-1/2"
            />
            <ValidatedInput
                v-model="newActuForm.dateFin"
                name="dateFin"
                label="Date de fin"
                type="date"
                @validation="result => handleValidation('dateFin', result)"
                help-text="Entrez la date de fin de l'actu."
                class="w-1/2"
            />
          </div>

          <ValidatedInput
              v-model="newActuForm.link"
              name="link"
              label="Lien"
              type="text"
              @validation="result => handleValidation('link', result)"
              help-text="Entrez le lien de l'actu."
          />

          <ValidatedInput
              v-model="newActuForm.description"
              name="description"
              label="Description"
              type="textarea"
              @validation="result => handleValidation('description', result)"
              help-text="Entrez la description de l'actu."
          />

          <ValidatedInput
              type="multiselect"
              v-model="newActuForm.public"
              :options="publicOptions"
              label="Public"
              :rules="[validationRules.required]"
              placeholder="Sélectionnez le type de public"
              class="w-full"
              help-text="Sélectionner à qui s'adresse cette actualité (étudiant, personnel ou les deux)."
          />

          <ValidatedInput
              type="radio"
              v-model="newActuForm.actif"
              :options="actifOptions"
              label="Visibilité"
              :rules="[validationRules.required]"
              help-text="Sélectionner si cette actualité est affichée ou non."
          />

          <div class="flex gap-2">
            <Button size="small" severity="primary" label="Enregistrer" type="submit"/>
            <Button size="small" severity="secondary" label="Annuler" @click="resetForm"/>
          </div>
        </div>
      </form>
    </div>
  </Dialog>
</template>

<style scoped>
</style>
