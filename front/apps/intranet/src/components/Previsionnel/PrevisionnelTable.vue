<script setup>
import { ref, reactive, toRefs } from 'vue';
import ButtonDelete from "@components/components/ButtonDelete.vue";
import ButtonDuplicate from "@components/components/ButtonDuplicate.vue";
import apiCall from '@helpers/apiCall.js'
import createApiService from '@requests/apiService'

const previsionnelService = createApiService('/api/previsionnels')

// Définition des propriétés du composant
const props = defineProps({
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

// Utilisation de reactive pour rendre les données réactives
const state = reactive({
  data: props.data
});

const { data } = toRefs(state);

// Fonction utilitaire pour obtenir la valeur d'un champ donné dans les données
const getFieldValue = (data, field) => {
  return field.split('.').reduce((acc, part) => acc && acc[part], data);
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

// Fonction pour dupliquer un prévisionnel
const duplicatePrevi = async (data) => {

  const iriPersonnel = 'api/personnels/' + data.idPersonnel;
  const iriEnseignement = 'api/scol_enseignements/' + data.idEnseignement;
  const iriAnneeUniversitaire = 'api/structure_annee_universitaires/' + data.structureAnneeUniversitaire;

  const dataToDuplicate = {
    personnel: iriPersonnel,
    anneeUniversitaire: data.structureAnneeUniversitaire,
    enseignement: iriEnseignement,
    heures: data.heures,
    groupes: data.groupes,
    referent: false
  };

  const response = await apiCall(previsionnelService.create, [dataToDuplicate], 'L\'élément a bien été dupliqué', 'Une erreur est survenue lors de la duplication du prévisionnel');

  // Reconstruire l'élément dupliqué
  const duplicatedPrevi = {
    id: response.id,
    codeEnseignement: data.codeEnseignement,
    groupes: response.groupes,
    heures: response.heures,
    idEnseignement: data.idEnseignement,
    idPersonnel: data.idPersonnel,
    libelle: data.libelle,
    libelleEnseignement: data.libelleEnseignement,
    personnel: data.personnel,
    structureAnneeUniversitaire: data.structureAnneeUniversitaire,
  }
  // Ajouter le prévisionnel dupliqué à la liste des données
  state.data.push(duplicatedPrevi);
  // Mettre à jour le tableau pour refléter les changements
  data.value = [...state.data];
  console.log(data.value)
};
</script>

<template>
  <DataTable :value="data" :filters="props.filters" tableStyle="min-width: 50rem" striped-rows scrollable :size="props.size" show-gridlines>
    <!-- Groupe de colonnes pour l'en-tête -->
    <ColumnGroup type="header">
      <Row>
        <Column :header="props.headerTitle" :colspan="headerTitlecolspan" class="!bg-gray-300 !bg-opacity-20"/>
        <Column v-if="props.topHeaderCols.length > 0" v-for="(topHeaderCol, index) in props.topHeaderCols" :key="index" :header="topHeaderCol.header" :colspan="topHeaderCol.colspan" :class="topHeaderCol.class"/>
      </Row>
      <Row>
        <Column v-for="(col, index) in props.columns" :key="index" :header="col.header" :colspan="col.colspan" :rowspan="col.rowspan" :sortable="col.sortable" :field="col.field" :class="col.class"/>
      </Row>
    </ColumnGroup>
    <!-- Colonnes dynamiques avec slots pour personnalisation -->
    <Column v-for="(col, index) in props.columns" :key="index" :field="col.field" :header="col.header" :sortable="col.sortable" :class="col.class">
      <template #body="slotProps">
        <slot :name="`body-${col.field}`" :data="slotProps.data" :value="getFieldValue(slotProps.data, col.field)">

          <InputText v-if="col.form && col.formType === 'text'" v-model="slotProps.data[col.field]" :placeholder="getFieldValue(slotProps.data, col.field)" @blur="col.formAction(getFieldValue(slotProps.data, col.id), col.type, $event.target.value)" class="max-w-20"/>

          <Select v-else-if="col.form && col.formType === 'select'"
                  v-model="slotProps.data[col.field]"
                  :options="col.formOptions"
                  optionLabel="label"
                  :placeholder="getFieldValue(slotProps.data, col.field)"
                  class="max-w-52"
                  @update:modelValue="col.formAction(getFieldValue(slotProps.data, col.id), $event)"
                  v-tooltip.top="col.tooltip ? col.tooltip : slotProps.data[col.field]"
          >
          </Select>

          <ButtonDelete v-else-if="col.button & col.delete" tooltip="Supprimer l'élément du prévi" @confirm-delete="deletePrevi(slotProps.data)" :class="col.class"/>
          <ButtonDuplicate v-else-if="col.button & col.duplicate" tooltip="Dupliquer l'élément dans le prévi" @confirm-duplicate="duplicatePrevi(slotProps.data)" :class="col.class"/>
          <Button v-else-if="col.button" :icon="col.buttonIcon" @click="col.buttonAction(getFieldValue(slotProps.data, col.id))" :class="col.buttonClass(col.field)" :label="col.field" :severity="col.buttonSeverity(col.field)"/>


          <Tag v-else-if="col.tag" class="w-max" :class="col.tagClass(getFieldValue(slotProps.data, col.field))" :severity="col.tagSeverity(getFieldValue(slotProps.data, col.field))" :icon="col.tagIcon(getFieldValue(slotProps.data, col.field))">
            {{ col.tagContent ? col.tagContent(getFieldValue(slotProps.data, col.field)) : getFieldValue(slotProps.data, col.field) }}<span v-if="col.unit && col.tagSeverity(getFieldValue(slotProps.data, col.field)) !== 'secondary'"> {{ col.unit }}</span>
          </Tag>
          <span v-else>{{ getFieldValue(slotProps.data, col.field) }}<span v-if="col.unit && !(typeof getFieldValue(slotProps.data, col.field) === 'string' && getFieldValue(slotProps.data, col.field).includes('autre département'))"> {{ col.unit }}</span></span>
        </slot>
      </template>
    </Column>

    <!-- Groupe de colonnes pour le pied de page -->
    <ColumnGroup v-if="footerCols.length > 0 || additionalRows.length > 0" type="footer">
      <Row v-for="(data, index) in props.additionalRows" :key="index">
        <Column v-for="d in data" :colspan="d.colspan" :rowspan="d.rowspan" :class="d.class">
          <template #footer="slotProps">
            <slot :name="`footer-${d.field}`" :value="d.footer">
              <InputText v-if="d.form && d.formType === 'text'" :value="d.footer" @input="d.footer = $event.target.value"/>
              <Select v-else-if="d.form && d.formType === 'select'"
                      :options="d.footer"
                      optionLabel="label"
                      :placeholder="d.placeholder"
                      class="!w-full"
                      @update:modelValue="(newValue) => d.formAction(newValue)"
              />


              <Button v-else-if="d.button" :icon="d.buttonIcon" @click="d.buttonAction(d.footer)" :class="d.buttonClass(d.footer)" :label="d.footer" :severity="d.buttonSeverity(d.footer)"/>
              <Tag v-else-if="d.tag" class="w-max" :class="d.tagClass(d.footer)" :severity="d.tagSeverity(d.footer)" :icon="d.tagIcon(d.footer)">
                {{ d.tagContent ? d.tagContent(d.footer) : d.footer }}<span v-if="d.unit && d.tagSeverity(d.footer) !== 'secondary'"> {{ d.unit }}</span>
              </Tag>
              <span class="w-fit" v-else>{{ d.footer }}<span v-if="d.unit"> {{ d.unit }}</span></span>
            </slot>
          </template>
        </Column>
      </Row>
      <Row>
        <Column v-for="(footerCol, index) in props.footerCols" :key="index" :colspan="footerCol.colspan" :rowspan="footerCol.rowspan" :class="footerCol.class">
          <template #footer="slotProps">
            <slot :name="`footer-${footerCol.field}`" :value="footerCol.footer">
              <Tag v-if="footerCol.tag" class="w-max" :class="footerCol.tagClass(footerCol.footer)" :severity="footerCol.tagSeverity(footerCol.footer)" :icon="footerCol.tagIcon(footerCol.footer)">
                {{ footerCol.footer }}<span v-if="footerCol.unit"> {{ footerCol.unit }}</span>
              </Tag>
              <span class="w-fit" v-else>{{ footerCol.footer }}<span v-if="footerCol.unit"> {{ footerCol.unit }}</span></span>
            </slot>
          </template>
        </Column>
      </Row>
    </ColumnGroup>
  </DataTable>
</template>

<style scoped>
</style>
