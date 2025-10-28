<script setup>
// récupère l'id du semestre sélectionné dans l'URL semestreId

import { ref, onMounted, watch, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

import { getGroupesSemestreService } from '@requests'
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import DynamicForm from '@components/components/Forms/DynamicForm.vue'
import BadgeTypeCours from '@components/components/BadgeTypeCours.vue'

import { useSemestreStore } from '@stores'
import { ListSkeleton } from '@components'
import SelectSemestre from '@/components/Navigation/SelectSemestre.vue'
import { useLayoutStore } from '@stores/layoutStore'
const layoutStore = useLayoutStore()
import { typesGroupes } from '@config/uniServices.js'



const route = useRoute()
const semestreStore = useSemestreStore()
const semestreId = ref(route.params.semestreId)
const groupesPlats = ref([])

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
  layoutStore.rightOfBreadcrumb = SelectSemestre
  _getSemestres()
})


async function _getSemestres() {
  try {
    const groupes = await getGroupesSemestreService(semestreId.value, false)
    semestre.value = semestreStore.getSemestre(semestreId.value)
    groupesSemestre.value = buildTreeNodes(groupes)
    groupesPlats.value = flattenGroupes(groupesSemestre.value);
    console.log('Groupes for semestre:', groupesPlats.value)
    isLoading.value = false
  } catch (error) {
    console.error('Error fetching groupes for semestre:', error)
  }
}

function flattenGroupes(treeNodes) {
  const result = [];
  function traverse(nodes) {
    for (const node of nodes) {
      if (node.data.type !== 'TP') {
        result.push({ value: node.data.id, label: node.data.libelle });
      }
      if (node.children && node.children.length) {
        traverse(node.children);
      }
    }
  }
  traverse(treeNodes);
  // Tri par label
  return result.sort((a, b) => a.label.localeCompare(b.label, 'fr', { sensitivity: 'base' }));
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

function buildTreeNodes(groupes) {
  return groupes.map(toTreeNode)
}

const groupesSemestre = ref([])
const semestre = ref(null)
const isLoading = ref(true)
const expandedKeys = ref({});
const updateDialog = ref(false)
const selectedGroupe = ref(null)

const expandAll = () => {
  for (let node of groupesSemestre.value) {
    expandNode(node);
  }

  expandedKeys.value = { ...expandedKeys.value };
};

const collapseAll = () => {
  expandedKeys.value = {};
};

const openModalUpdate = (groupe) => {
  console.log(groupe)
  selectedGroupe.value = groupe
  updateDialog.value = true
}

const expandNode = (node) => {
  if (node.children && node.children.length) {
    expandedKeys.value[node.key] = true;

    for (let child of node.children) {
      expandNode(child);
    }
  }
};

const addGroupe = () => {
  updateDialog.value = true
  selectedGroupe.value = {
    data: {
      libelle: '',
      type: 'CM',
      codeApogee: '',
      parent: ''
    }
  }
}
</script>

<template>
  <ListSkeleton v-if="isLoading" class="mt-4"/>
  <div v-else>
  <p v-if="groupesSemestre.length === 0">Aucun groupe trouvé pour ce semestre.</p>
  <div v-else>

    <div class="flex flex-wrap gap-2 mb-6">
      <Button type="button" icon="pi pi-plus" label="Tout déplier" @click="expandAll" />
      <Button type="button" icon="pi pi-minus" label="Tout refermer" @click="collapseAll" />
      <Button type="button" icon="pi pi-plus" label="Ajouter un groupe" @click="addGroupe" severity="success" />
    </div>

    <TreeTable :value="groupesSemestre" tableStyle="min-width: 50rem"
               editMode="row"
               v-model:expandedKeys="expandedKeys">
      <Column field="libelle" header="Libellé" expander style="width: 34%"></Column>
      <Column field="type" header="Type" style="width: 33%">
        <template #body="{ node }">
          <BadgeTypeCours :type="node.data.type" />
        </template>
      </Column>
      <Column field="codeApogee" header="Code Apogée" style="width: 33%"></Column>
      <Column style="width: 200rem">
        <template #body="{ node }">
          <div class="flex gap-2">
            <ButtonEdit
                tooltip="Modifier le groupe"
                @click="openModalUpdate(node)"
                />
            <ButtonDelete
                tooltip="Supprimer le groupe"
                @click="deleteGroupe(node)"
            />
          </div>
        </template>
      </Column>

    </TreeTable>
  </div>
  </div>


<Dialog header="Modifier le groupe" :style="{ width: '50vw' }"
        @update:visible="updateDialog = $event"
        :visible="updateDialog" :modal="true" :closable="true">
  <DynamicForm
    v-if="selectedGroupe"
    :form-config="{
      title: '',
      fields: [
        { name: 'parent', label: 'Groupe Parent (si existant)', type: 'select', required: false, options: groupesPlats },
        { name: 'libelle', label: 'Libellé', type: 'text', required: true },
        { name: 'type', label: 'Type', type: 'select', options: typesGroupes, required: true },
        { name: 'codeApogee', label: 'Code Apogée', type: 'text' }
      ]
    }"
    :form-options="{ submitLabel: 'Enregistrer',  }"
    :initial-values="selectedGroupe.data"
    @submit="updateDialog = false"
  ></DynamicForm>
</Dialog>
</template>

<style scoped>

</style>
