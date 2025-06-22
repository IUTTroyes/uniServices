<script setup>
// récupère l'id du semestre sélectionné dans l'URL semestreId

import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'

import { getGroupesSemestreService } from '@requests'

import { useSemestreStore } from '@stores'
import { ListSkeleton } from '@components'
import SelectSemestre from '../../components/Navigation/SelectSemestre.vue'

const route = useRoute()
const semestreStore = useSemestreStore()
const semestreId = ref(route.params.semestreId)

// surveiller les changements de semestreId
watch(semestreId, async (newSemestre, oldSemestre) => {
  console.log('semestreId changed:', newSemestre)
  await _getSemestres()
})

watch(
     () => route.params.semestreId, // remplace par le nom de ton paramètre
    async (newVal, oldVal) => {
      // Code à exécuter quand le paramètre change
      semestreId.value = newVal
      await _getSemestres()
    }
)

onMounted(async () => {
  _getSemestres()
})

async function _getSemestres() {
  try {
    const groupes = await getGroupesSemestreService(semestreId.value, false)
    console.log(groupes)
    semestre.value = semestreStore.getSemestre(semestreId.value)
    groupesSemestre.value = buildTreeNodes(groupes)

    console.log(groupesSemestre.value)
    isLoading.value = false
  } catch (error) {
    console.error('Error fetching groupes for semestre:', error)
  }
}

function toTreeNode(obj) {
  return {
    key: String(obj.id),
    data: obj,
    label: obj.libelle,
    children: obj.enfants && obj.enfants.length
        ? obj.enfants.map(toTreeNode)
        : []
  }
}

// Pour un tableau de racines :
function buildTreeNodes(groupes) {
  return groupes.map(toTreeNode)
}

const groupesSemestre = ref({})
const semestre = ref(null)
const isLoading = ref(true)
const expandedKeys = ref({});

const expandAll = () => {
  for (let node of groupesSemestre.value) {
    expandNode(node);
  }

  expandedKeys.value = { ...expandedKeys.value };
};

const collapseAll = () => {
  expandedKeys.value = {};
};

const expandNode = (node) => {
  if (node.children && node.children.length) {
    expandedKeys.value[node.key] = true;

    for (let child of node.children) {
      expandNode(child);
    }
  }
};
</script>

<template>
  <ListSkeleton v-if="isLoading" class="mt-4"/>
  <div v-else>
  <p v-if="groupesSemestre.length === 0">Aucun groupe trouvé pour ce semestre.</p>
  <div v-else>
    <SelectSemestre></SelectSemestre>


    <div class="flex flex-wrap gap-2 mb-6">
      <Button type="button" icon="pi pi-plus" label="Tout déplier" @click="expandAll" />
      <Button type="button" icon="pi pi-minus" label="Tout refermer" @click="collapseAll" />
    </div>

    <TreeTable :value="groupesSemestre" tableStyle="min-width: 50rem" v-model:expandedKeys="expandedKeys">
      <Column field="libelle" header="Libellé" expander style="width: 34%"></Column>
      <Column field="type" header="Type" style="width: 33%"></Column>
      <Column field="codeApogee" header="Code Apogée" style="width: 33%"></Column>
      <Column style="width: 100rem">
        <template #body>
          <div class="flex flex-wrap gap-2">
            <Button type="button" icon="pi pi-search" rounded />
            <Button type="button" icon="pi pi-pencil" rounded severity="success" />
          </div>
        </template>
      </Column>
    </TreeTable>
  </div>
  </div>
</template>

<style scoped>

</style>
