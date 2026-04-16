<script setup>
import { ref, reactive, toRefs, watch } from 'vue';
import ButtonDelete from "@components/components/Buttons/ButtonDelete.vue";
import ButtonDuplicate from "@components/components/Buttons/ButtonDuplicate.vue";
import ButtonSave from "@components/components/Buttons/ButtonSave.vue";
import apiCall from '@helpers/apiCall.js'
import debounce from '@helpers/debounce.js'
import createApiService from '@requests/apiService'
const previsionnelService = createApiService('/api/previsionnels');
const hrsService = createApiService('/api/personnel_enseignant_hrs');

const dataToDuplicatePrevi = ref({});
const dataToDuplicateHrs = ref({});
const duplicatedPrevi = ref({});
const duplicatedHrs = ref({});


const debouncedActions = new Map();

// Function to get or create a debounced version of a form action
const getDebouncedActionPrevi = (formAction, id, type, test) => {
  console.log('test', formAction, id, type);
  const key = `${id}-${type}-${test}`;
  if (!debouncedActions.has(key)) {
    debouncedActions.set(key, debounce((value) => {
      formAction(id, type, value);
    }, 500));
  }
  return debouncedActions.get(key);
};

// Définition des propriétés du composant
const props = defineProps({
  editingRowId: {
    type: [Number, String],
    default: null
  },
  origin: {
    type: String,
    required: true
  },
  columns: {
    type: Array,
    required: true
  },
  topHeaderCols: {
    type: Array,
    required: false
  },
  additionalRows: {
    type: Array,
    required: false
  },
  footerCols: {
    type: Array,
    required: false
  },
  footerRows: {
    type: Array,
    required: false
  },
  data: {
    type: Array,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  size: {
    type: String,
    default: 'null'
  },
  headerTitle: {
    type: String,
    required: true
  },
  headerTitlecolspan: {
    type: Number,
    required: true
  }
});

const emit = defineEmits(['update:editingRowId', 'save-row', 'cancel-row']);

// Utilisation de reactive pour rendre les données réactives
const state = reactive({
  data: props.data
});

const { data } = toRefs(state);


const onRowClick = (rowData) => {
  if (props.origin === 'previSemestreForm' && props.editingRowId !== rowData.id) {
    emit('update:editingRowId', rowData.id);
  }
};

const cancelRow = (id) => {
  emit('cancel-row', id);
};

const handleKeyDown = (event, rowData) => {
  if (event.key === 'Enter') {
    emit('save-row', rowData.id);
  } else if (event.key === 'Escape') {
    emit('cancel-row', rowData.id);
  }
};

// Fonction utilitaire pour obtenir la valeur d'un champ donné dans les données
const getFieldValue = (data, field) => {
  if (!field) return '';
  return field.split('.').reduce((acc, part) => acc && acc[part], data);
};

// Fonction pour mettre à jour une valeur imbriquée dans un objet
const setFieldValue = (data, field, value) => {
  const parts = field.split('.');
  const last = parts.pop();
  const obj = parts.reduce((acc, part) => acc && acc[part], data);
  if (obj && last) {
    obj[last] = value;
  }
};

// Fonction pour supprimer un prévisionnel
const deletePrevi = async (data) => {
  await apiCall(previsionnelService.delete, [data.id], 'L\'élément a bien été supprimé', 'Une erreur est survenue lors de la suppression du prévisionnel');
  // Mettre à jour les données après la suppression
  const index = state.data.findIndex(item => item.id === data.id);
  if (index !== -1) {
    state.data.splice(index, 1);
  }
  // Mettre à jour le tableau pour refléter les changements
  data.value = [...state.data];
};

// Fonction pour supprimer des heures ou primes
const deleteHrs = async (id) => {
  await apiCall(hrsService.delete, [id], 'L\'élément a bien été supprimé', 'Une erreur est survenue lors de la suppression des heures ou primes');
};
</script>

<template>
  <DataTable scrollHeight="800px"
             scrollable :value="data" :filters="props.filters" tableStyle="min-width: 50rem" striped-rows :size="props.size" show-gridlines
             @row-click="(event) => onRowClick(event.data)">
    <!-- Groupe de colonnes pour l'en-tête -->
    <ColumnGroup type="header">
      <Row>
        <Column :header="props.headerTitle" :colspan="headerTitlecolspan" class="bg-gray-300/20!"/>
        <template v-if="props.topHeaderCols.length > 0">
          <Column v-for="(topHeaderCol, index) in props.topHeaderCols" :key="index" :header="topHeaderCol.header" :colspan="topHeaderCol.colspan" :class="topHeaderCol.class"/>
        </template>
      </Row>
      <Row>
        <Column v-for="(col, index) in props.columns" :key="index" :header="col.header" :colspan="col.colspan" :rowspan="col.rowspan" :sortable="col.sortable" :field="col.field" :class="col.class"/>
      </Row>
    </ColumnGroup>
    <!-- Colonnes dynamiques avec slots pour personnalisation -->
    <Column v-for="(col, index) in props.columns" :key="index" :field="col.field" :header="col.header" :sortable="col.sortable" :class="col.class" :style="col.style">
      <template #body="slotProps">
        <slot :name="`body-${col.field}`" :data="slotProps.data" :value="getFieldValue(slotProps.data, col.field)">
          <div v-if="props.editingRowId === slotProps.data.id && col.form" class="relative group">
            <InputText
                v-if="col.formType === 'text'"
                :modelValue="getFieldValue(slotProps.data, col.field)"
                @update:modelValue="(val) => setFieldValue(slotProps.data, col.field, val)"
                :placeholder="getFieldValue(slotProps.data, col.field)"
                @keydown="(event) => handleKeyDown(event, slotProps.data)"
                class="max-w-20"
                v-tooltip.top="col.tooltip"
                :disabled="col.disabled ? col.disabled(slotProps.data) : false"
                autofocus
            />

            <Select v-else-if="col.formType === 'select'"
                    :modelValue="getFieldValue(slotProps.data, col.field)"
                    :options="col.formOptions"
                    optionLabel="label"
                    :placeholder="typeof getFieldValue(slotProps.data, col.field) === 'object' ? (getFieldValue(slotProps.data, col.field)?.label || 'Sélectionner un intervenant') : getFieldValue(slotProps.data, col.field)"
                    class="max-w-52"
                    @update:modelValue="(event) => { setFieldValue(slotProps.data, col.field, event); col.formAction(getFieldValue(slotProps.data, col.id), event); }"
                    @keydown="(event) => handleKeyDown(event, slotProps.data)"
                    v-tooltip.top="col.tooltip ? col.tooltip : slotProps.data[col.field]"
                    :disabled="col.disabled ? col.disabled(slotProps.data) : false"
            >
            </Select>
          </div>
          <template v-else>
            <div v-if="col.actions" class="flex gap-2 justify-center">
              <template v-if="props.editingRowId === slotProps.data.id">
                <Button icon="pi pi-check" severity="success" size="small" rounded @click.stop="emit('save-row', slotProps.data.id)" v-tooltip.top="'Enregistrer'" />
                <Button icon="pi pi-times" severity="secondary" size="small" rounded @click.stop="cancelRow(slotProps.data.id)" v-tooltip.top="'Annuler'" />
              </template>
              <template v-else>
                <ButtonDuplicate v-if="col.duplicate" tooltip="Dupliquer l'élément dans le prévi" @confirm-duplicate="(event) => { col.duplicateAction(getFieldValue(slotProps.data, col.id), event); }" :class="col.class"/>
                <ButtonDelete v-if="col.delete" tooltip="Supprimer l'élément du prévi" @confirm-delete="deletePrevi(slotProps.data)" :class="col.class"/>
              </template>
            </div>

            <Button v-else-if="col.button" :icon="col.buttonIcon" @click="col.buttonAction(getFieldValue(slotProps.data, col.id))" :class="col.buttonClass(col.field)" :label="col.field" :severity="col.buttonSeverity(col.field)"/>


            <Tag v-else-if="col.tag" class="w-max" :class="col.tagClass(getFieldValue(slotProps.data, col.field))" :severity="col.tagSeverity(getFieldValue(slotProps.data, col.field))" :icon="col.tagIcon(getFieldValue(slotProps.data, col.field))">
              {{ col.tagContent ? col.tagContent(getFieldValue(slotProps.data, col.field)) : getFieldValue(slotProps.data, col.field) }}<span v-if="col.unit && col.tagSeverity(getFieldValue(slotProps.data, col.field)) !== 'secondary'" v-tooltip.top="col.tooltip"> {{ col.unit }}</span>
            </Tag>
            <span v-else>{{ getFieldValue(slotProps.data, col.field) }}<span v-if="col.unit && !(typeof getFieldValue(slotProps.data, col.field) === 'string' && getFieldValue(slotProps.data, col.field).includes('autre département'))" v-tooltip.top="col.tooltip"> {{ col.unit }}</span></span>
          </template>
        </slot>
      </template>
    </Column>


    <!-- Groupe de colonnes pour le pied de page -->
    <ColumnGroup v-if="footerCols.length > 0 || additionalRows.length > 0" type="footer">
      <Row v-for="(data, index) in props.additionalRows" :key="index">
        <Column v-for="d in data" :colspan="d.colspan" :rowspan="d.rowspan" :class="d.class">
          <template #footer="slotProps">
            <slot :name="`footer-${d.field}`" :value="d.footer">
              <InputText
                  v-if="d.form && d.formType === 'text'"
                  v-model="d.footer"
                  :placeholder="d.placeholder || 'Saisir une valeur'"
                  @blur="d.formAction(d.footer)"
                  v-tooltip.top="d.tooltip"
              />

              <Select v-else-if="d.form && d.formType === 'select'"
                      :options="d.footer"
                      optionLabel="label"
                      :placeholder="typeof d.placeholder === 'object' ? (d.placeholder?.label || 'Sélectionner') : d.placeholder"
                      class="!w-full"
                      @update:modelValue="(newValue) => d.formAction(newValue)"
                      v-tooltip.top="d.tooltip ? d.tooltip : d.footer"
              />

              <ButtonSave v-else-if="d.button & d.save" tooltip="Enregistrer l'élément HRS/prime"  :class="d.class"/>
              <ButtonDelete v-else-if="d.button & d.delete" tooltip="Supprimer l'élément HRS/prime" @confirm-delete="deleteHrs(d.id)" :class="d.class"/>
              <ButtonDuplicate v-else-if="d.button & d.duplicate" tooltip="Dupliquer l'élément HRS/prime" @confirm-duplicate="duplicateHrs(d.id)" :class="d.class"/>
              <Button v-else-if="d.button" :icon="d.buttonIcon" @click="d.buttonAction(d.buttonParam !== undefined ? d.buttonParam : d.footer)" :class="d.buttonClass(d.footer)" :label="d.footer" :severity="d.buttonSeverity(d.footer)" :tooltip="d.tooltip"/>
              <Tag v-else-if="d.tag" class="w-max" :class="d.tagClass(d.footer)" :severity="d.tagSeverity(d.footer)" :icon="d.tagIcon(d.footer)" v-tooltip.top="d.tooltip">
                {{ d.tagContent ? d.tagContent(d.footer) : d.footer }}<span v-if="d.unit && d.tagSeverity(d.footer) !== 'secondary'"> {{ d.unit }}</span>
              </Tag>
              <span class="w-fit" v-else v-tooltip.top="d.tooltip">{{ d.footer }}<span v-if="d.unit"> {{ d.unit }}</span></span>
            </slot>
          </template>
        </Column>
      </Row>
      <Row>
        <Column v-for="(footerCol, index) in props.footerCols" :key="index" :colspan="footerCol.colspan" :rowspan="footerCol.rowspan" :class="footerCol.class">
          <template #footer="slotProps">
            <slot :name="`footer-${footerCol.field}`" :value="footerCol.footer">
              <Tag v-if="footerCol.tag" class="w-max" :class="footerCol.tagClass(footerCol.footer)" :severity="footerCol.tagSeverity(footerCol.footer)" :icon="footerCol.tagIcon(footerCol.footer)" v-tooltip.top="footerCol.tooltip">
                {{ footerCol.footer }}<span v-if="footerCol.unit"> {{ footerCol.unit }}</span>
              </Tag>
              <span class="w-fit" v-else>{{ footerCol.footer }}<span v-if="footerCol.unit" v-tooltip.top="footerCol.tooltip"> {{ footerCol.unit }}</span></span>
            </slot>
          </template>
        </Column>
      </Row>
    </ColumnGroup>
  </DataTable>
</template>

<style scoped>
</style>
