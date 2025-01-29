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
  topHeaderCols: {
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
  }
});

const getFieldValue = (data, field) => {
  return field.split('.').reduce((acc, part) => acc && acc[part], data);
};
</script>

<template>
  <DataTable :value="props.data" :filters="props.filters" tableStyle="min-width: 50rem" striped-rows scrollable :size="props.size" show-gridlines>
    <ColumnGroup type="header">
      <Row>
        <Column :header="props.headerTitle" :colspan="4" class="text-xl"/>
        <Column v-for="(topHeaderCol, index) in props.topHeaderCols" :key="index" :header="topHeaderCol.header" :colspan="topHeaderCol.colspan" :class="topHeaderCol.class"/>
      </Row>
      <Row>
        <Column v-for="(col, index) in props.columns" :key="index" :header="col.header" :colspan="col.colspan" :sortable="col.sortable" :field="col.field" :class="col.class"/>
      </Row>
    </ColumnGroup>
    <Column v-for="(col, index) in props.columns" :key="index" :field="col.field" :header="col.header" :sortable="col.sortable" :class="col.class">
      <template #body="slotProps">
        <Tag v-if="col.tag" class="w-max" :class="col.tagClass(getFieldValue(slotProps.data, col.field))" :severity="col.tagSeverity(getFieldValue(slotProps.data, col.field))" :icon="col.tagIcon(getFieldValue(slotProps.data, col.field))">
          {{ getFieldValue(slotProps.data, col.field) }} h
        </Tag>
        <span v-else>{{ getFieldValue(slotProps.data, col.field) }}</span>
      </template>
    </Column>
    <ColumnGroup type="footer">
      <Row>
        <Column v-for="(footerRow, index) in props.footerRows" :key="index" :footer="footerRow.footer" :colspan="footerRow.colspan" :class="footerRow.class"/>
      </Row>
      <Row>
        <Column v-for="(footerCol, index) in props.footerCols" :key="index" :footer="footerCol.footer" :colspan="footerCol.colspan"/>
      </Row>
    </ColumnGroup>
  </DataTable>
</template>

<style scoped>
/* Ajoutez ici vos styles personnalisés */
</style>
