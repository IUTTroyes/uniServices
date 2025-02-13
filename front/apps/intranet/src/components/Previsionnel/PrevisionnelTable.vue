<script setup>

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

// Fonction utilitaire pour obtenir la valeur d'un champ donné dans les données
const getFieldValue = (data, field) => {
  return field.split('.').reduce((acc, part) => acc && acc[part], data);
};
</script>

<template>
  <DataTable :value="props.data" :filters="props.filters" tableStyle="min-width: 50rem" striped-rows scrollable :size="props.size" show-gridlines>
    <!-- Groupe de colonnes pour l'en-tête -->
    <ColumnGroup type="header">
      <Row>
        <Column :header="props.headerTitle" :colspan="headerTitlecolspan" class="text-xl"/>
        <Column v-if="props.topHeaderCols.length > 0" v-for="(topHeaderCol, index) in props.topHeaderCols" :key="index" :header="topHeaderCol.header" :colspan="topHeaderCol.colspan" :class="topHeaderCol.class"/>
      </Row>
      <Row>
        <Column v-for="(col, index) in props.columns" :key="index" :header="col.header" :colspan="col.colspan" :sortable="col.sortable" :field="col.field" :class="col.class"/>
      </Row>
    </ColumnGroup>
    <!-- Colonnes dynamiques avec slots pour personnalisation -->
    <Column v-for="(col, index) in props.columns" :key="index" :field="col.field" :header="col.header" :sortable="col.sortable" :class="col.class">
      <template #body="slotProps">
        <slot :name="`body-${col.field}`" :data="slotProps.data" :value="getFieldValue(slotProps.data, col.field)">
          <InputText v-if="col.form" v-model="slotProps.data[col.field]" class="!w-fit"/>
          <Tag v-else-if="col.tag" class="w-max" :class="col.tagClass(getFieldValue(slotProps.data, col.field))" :severity="col.tagSeverity(getFieldValue(slotProps.data, col.field))" :icon="col.tagIcon(getFieldValue(slotProps.data, col.field))">
            {{ col.tagContent ? col.tagContent(getFieldValue(slotProps.data, col.field)) : getFieldValue(slotProps.data, col.field) }}<span v-if="col.unit && col.tagSeverity(getFieldValue(slotProps.data, col.field)) !== 'secondary'"> {{ col.unit }}</span>
          </Tag>
          <span v-else>{{ getFieldValue(slotProps.data, col.field) }}<span v-if="col.unit && !(typeof getFieldValue(slotProps.data, col.field) === 'string' && getFieldValue(slotProps.data, col.field).includes('autre département'))"> {{ col.unit }}</span></span>
        </slot>
      </template>
    </Column>

    <!-- Groupe de colonnes pour le pied de page -->
    <ColumnGroup v-if="footerCols.length > 0 || footerRows.length > 0 || additionalRows.length > 0" type="footer">
      <Row>
        <Column v-for="(footerRow, index) in props.footerRows" :key="index" :footer="footerRow.footer" :colspan="footerRow.colspan" :class="footerRow.class"/>
      </Row>

      <Row v-for="(data, index) in props.additionalRows" :key="index">
        <Column v-for="d in data" :colspan="d.colspan" :class="d.class">
          <template #footer="slotProps">
            <slot :name="`footer-${d.field}`" :value="d.footer">
              <Tag v-if="d.tag" class="w-max" :class="d.tagClass(d.footer)" :severity="d.tagSeverity(d.footer)" :icon="d.tagIcon(d.footer)">
                {{ d.footer }}<span v-if="d.unit && d.tagSeverity(d.footer) !== 'secondary'"> {{ d.unit }}</span>
              </Tag>
              <span class="w-fit" v-else>{{ d.footer }}<span v-if="d.unit"> {{ d.unit }}</span></span>
            </slot>
          </template>
        </Column>
      </Row>

      <Row>
        <Column v-for="(footerCol, index) in props.footerCols" :key="index"  :colspan="footerCol.colspan" :class="footerCol.class">
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

