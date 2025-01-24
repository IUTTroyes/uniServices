<script setup>
import { DataTable, Column, ColumnGroup, Row, Tag } from 'primevue';

const props = defineProps({
  origin : {
    type: String,
    required: true
  },
  columns: {
    type: Array,
    required: true
  },
  subColumns: {
    type: Array,
    required: true
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
  }
});

const getFieldValue = (data, field) => {
  return field.split('.').reduce((acc, part) => acc && acc[part], data);
};
</script>

<template>
  <DataTable :value="props.data" :filters="props.filters" tableStyle="min-width: 50rem" striped-rows scrollable :size="props.size">
    <ColumnGroup type="header">
      <Row>
        <Column :header="props.headerTitle" :colspan="4" class="text-xl"/>
        <Column v-for="(subCol, index) in props.subColumns" :key="index" :header="subCol.header" :colspan="subCol.colspan" :class="subCol.class"/>
      </Row>
      <Row>
        <Column v-for="(col, index) in props.columns" :key="index" :header="col.header" :colspan="col.colspan" :sortable="col.sortable" :field="col.field" :class="col.class"/>
      </Row>
    </ColumnGroup>
    <Column v-for="(col, index) in props.columns" :key="index" :field="col.field" :header="col.header" :sortable="col.sortable">
      <template #body="slotProps">
        <Tag v-if="col.tag" class="w-max" :class="col.tagClass(getFieldValue(slotProps.data, col.field))" :severity="col.tagSeverity(getFieldValue(slotProps.data, col.field))" :icon="col.tagIcon(getFieldValue(slotProps.data, col.field))">
          {{ getFieldValue(slotProps.data, col.field) }} h
        </Tag>
        <span v-else>{{ getFieldValue(slotProps.data, col.field) }}</span>
      </template>
    </Column>
  </DataTable>
</template>

<style scoped>
/* Ajoutez ici vos styles personnalisés */
</style>
