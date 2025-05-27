<template>
  <div class="p-fluid grid formgrid fiche-heure-form-biats">
    <div class="field col-12 md:col-6">
      <label for="semaineAnnee">Semaine/Année (ex: S23-2024)</label>
      <InputText id="semaineAnnee" v-model.trim="formData.semaineAnnee" placeholder="YYYY-WNN ou SNN-YYYY" />
      <!-- Basic validation example (can be enhanced with Vuelidate) -->
      <small v-if="formErrors.semaineAnnee" class="p-error">{{ formErrors.semaineAnnee }}</small>
    </div>

    <div class="col-12">
      <h3 class="text-lg font-semibold mb-2 mt-3">Entrées d'heures</h3>
      <DataTable :value="formData.heures" editMode="row" dataKey="id_temp" @row-edit-save="onRowEditSave" responsiveLayout="scroll">
           <Column field="date" header="Date">
               <template #editor="{ data, field }">
                   <Calendar v-model="data[field]" dateFormat="dd/mm/yy" showIcon />
               </template>
               <template #body="{ data, field }">
                   {{ formatDateForDisplay(data[field]) }}
               </template>
           </Column>
           <Column field="startTime" header="Début (HH:MM)">
               <template #editor="{ data, field }">
                   <InputText v-model="data[field]" placeholder="HH:MM" />
               </template>
           </Column>
           <Column field="endTime" header="Fin (HH:MM)">
               <template #editor="{ data, field }">
                   <InputText v-model="data[field]" placeholder="HH:MM" />
               </template>
           </Column>
           <Column field="task" header="Tâche/Description" style="width: 40%">
               <template #editor="{ data, field }">
                   <InputTextarea v-model="data[field]" autoResize rows="1" />
               </template>
           </Column>
           <Column :rowEditor="true" style="width: 10%; min-width:8rem" bodyStyle="text-align:center"></Column>
           <Column bodyStyle="text-align:center; width: 8rem">
               <template #body="slotProps">
                   <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="removeHeureEntry(slotProps.index)" />
               </template>
           </Column>
           <template #empty>
             Aucune entrée d'heure pour le moment.
           </template>
      </DataTable>
      <Button label="Ajouter une ligne" icon="pi pi-plus" class="p-button-sm mt-2" @click="addHeureEntry" />
    </div>
    <small v-if="formErrors.heures" class="p-error col-12">{{ formErrors.heures }}</small>


    <div class="col-12 mt-5 flex justify-content-end">
      <Button label="Annuler" icon="pi pi-times" class="p-button-text mr-2" @click="handleCancel" />
      <Button :label="isEditMode ? 'Mettre à jour Brouillon' : 'Enregistrer Brouillon'" icon="pi pi-save" @click="handleSaveDraft" :loading="isSaving" />
      <!-- Optional: Consider a separate submit button if workflow demands it -->
      <!-- <Button label="Enregistrer et Soumettre" icon="pi pi-send" class="ml-2" @click="handleSaveAndSubmit" :loading="isSubmitting" /> -->
    </div>
  </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import InputTextarea from 'primevue/inputtextarea';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
// No useRouter import needed if only emitting events

const props = defineProps({
  isEditMode: { type: Boolean, default: false },
  initialData: { type: Object, default: null },
  isSaving: { type: Boolean, default: false }, // Optional: for loading state on save button
  // isSubmitting: { type: Boolean, default: false } // Optional: for a save & submit button
});
const emit = defineEmits(['save', 'cancel']);

const defaultEntry = () => ({ id_temp: Date.now() + Math.random().toString(36).substr(2, 9), date: null, startTime: '', endTime: '', task: '' });

const initialFormData = () => {
  if (props.isEditMode && props.initialData) {
    // Deep clone and ensure heures array has unique keys for DataTable editing
    const clonedData = JSON.parse(JSON.stringify(props.initialData));
    const heures = (clonedData.heures && clonedData.heures.length > 0)
      ? clonedData.heures.map(h => ({ ...h, id_temp: h.id_temp || Date.now() + Math.random().toString(36).substr(2, 9) }))
      : [defaultEntry()];
    return { ...clonedData, heures };
  }
  return { semaineAnnee: '', heures: [defaultEntry()], statut: 'BROUILLON' }; // Default statut for new
};

const formData = ref(initialFormData());
const formErrors = ref({});


watch(() => props.initialData, () => {
  formData.value = initialFormData();
}, { deep: true });


const addHeureEntry = () => {
  if (!formData.value.heures) {
    formData.value.heures = [];
  }
  formData.value.heures.push(defaultEntry());
};

const removeHeureEntry = (index) => {
  formData.value.heures.splice(index, 1);
};

const onRowEditSave = (event) => {
  // PrimeVue DataTable v-model editing handles this automatically for the row data
  // However, if `event.data` is different from `event.newData` (it usually is),
  // you might need to update the specific item in your array if not using direct v-model on rows.
  // For this setup, direct modification of `data[field]` in templates + `event.newData` in `onRowEditSave` works.
  let { newData, index } = event;
  formData.value.heures[index] = newData;
};

const formatDateForDisplay = (dateValue) => {
  if (!dateValue) return '';
  const date = (typeof dateValue === 'string') ? new Date(dateValue) : dateValue;
  //Check if date is valid
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const validateForm = () => {
  formErrors.value = {};
  let isValid = true;
  if (!formData.value.semaineAnnee || !/^(S\d{1,2}-\d{4}|\d{4}-W\d{1,2})$/i.test(formData.value.semaineAnnee)) {
    formErrors.value.semaineAnnee = 'Format Semaine/Année invalide (ex: S23-2024 ou 2024-W23).';
    isValid = false;
  }
  if (!formData.value.heures || formData.value.heures.length === 0) {
    formErrors.value.heures = 'Au moins une entrée d\'heure est requise.';
    isValid = false;
  } else {
    for (const entry of formData.value.heures) {
      if (!entry.date || !entry.startTime || !entry.endTime || !entry.task) {
        formErrors.value.heures = 'Toutes les entrées d\'heures doivent être complètes (Date, Début, Fin, Tâche).';
        isValid = false;
        break;
      }
      if (entry.startTime && !/^\d{2}:\d{2}$/.test(entry.startTime)) {
        formErrors.value.heures = `Format HH:MM invalide pour l'heure de début dans une des lignes.`;
        isValid = false;
        break;
      }
      if (entry.endTime && !/^\d{2}:\d{2}$/.test(entry.endTime)) {
        formErrors.value.heures = `Format HH:MM invalide pour l'heure de fin dans une des lignes.`;
        isValid = false;
        break;
      }
    }
  }
  return isValid;
};

const handleSaveDraft = () => {
  if (validateForm()) {
    // Remove temporary IDs before emitting
    const payload = JSON.parse(JSON.stringify(formData.value));
    payload.heures.forEach(h => delete h.id_temp);
    emit('save', payload);
  }
};

const handleCancel = () => {
  emit('cancel');
};

// Optional: if you have a separate "Save and Submit" button
// const handleSaveAndSubmit = () => {
//   if (validateForm()) {
//     const payload = JSON.parse(JSON.stringify(formData.value));
//     payload.heures.forEach(h => delete h.id_temp);
//     emit('save', { ...payload, submit: true }); // Or a different event like 'save-and-submit'
//   }
// };

</script>

<style scoped>
.fiche-heure-form-biats {
  /* Add specific styles if needed */
}
.p-error {
  display: block; /* Ensure error messages take full width under inputs */
  margin-top: 0.25rem;
}
</style>
