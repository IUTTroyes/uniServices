<script setup>
import { onMounted, ref, computed } from 'vue'
import { getDepartementSemestresService } from '@requests'
import { ListSkeleton } from '@components'
import { useSemestreStore, useUsersStore } from '@stores'

const userStore = useUsersStore()
const semestreStore = useSemestreStore()
const semestresFc = ref([])
const semestresFi = ref([])
const selectedSemestre = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')

const panelMenuItems = computed(() => {
  if (!selectedSemestre.value) return []
  return [
    {
      label: 'Étudiants', icon: 'pi pi-user', command: () => {}, items: [
        { label: 'Liste des étudiants', icon: 'pi pi-list', command: () => {} },
        { label: 'Ajouter des étudiants', icon: 'pi pi-plus-circle', command: () => {} },
      ]
    },
    {
      label: 'Groupes', icon: 'pi pi-users', command: () => {}, items: [
        {
          label: 'Composition des groupes', icon: 'pi pi-list',
          command: () => {}
        },
        {
          label: 'Structure des groupes', icon: 'pi pi-cog', route: '/administration/semestre/' + selectedSemestre.value.id + '/groupes/structure',
        },
      ]
    },
    {
      label: 'Absences', icon: 'pi pi-calendar', command: () => {}, items: [
        {
          label: 'Liste des absences', icon: 'pi pi-list',
          command: () => {}
        },
        { label: 'Liste des justificatifs', icon: 'pi pi-folder-open', command: () => {} },
        { label: 'Suivi des pointages de présence', icon: 'pi pi-eye', command: () => {} },
      ]
    },
    {
      label: 'Notes et Évaluations', icon: 'pi pi-book', command: () => {}, items: [
        { label: 'Liste des notes', icon: 'pi pi-list', command: () => {} },
        { label: 'Gestion des évaluations', icon: 'pi pi-cog', command: () => {} },
        { label: 'Demandes de rattrapages', icon: 'pi pi-history', command: () => {} },
        { label: 'Modalités du contrôle continu', icon: 'pi pi-map', command: () => {} },
      ]
    },
    {
      label: 'Fin de semestre', icon: 'pi pi-check', command: () => {}, items: [
        { label: 'Préparation de la sous-commission', icon: 'pi pi-calculator', command: () => {} },
        { label: 'Changement de semestre des étudiants', icon: 'pi pi-forward', command: () => {} },
      ]
    },
  ]
})

const getSemestres = async () => {
  try {
    const departementId = userStore.departementDefaut.id
    const semestres = await getDepartementSemestresService(departementId, true)

    const groupByYear = (semestres) => {
      return semestres.reduce((acc, semestre) => {
        const year = semestre.annee.libelle
        if (!acc[year]) {
          acc[year] = []
        }
        acc[year].push(semestre)
        return acc
      }, {})
    }

    semestresFc.value = groupByYear(semestres.filter(semestre => semestre.annee.opt.alternance))
    semestresFi.value = groupByYear(semestres.filter(semestre => !semestre.annee.opt.alternance))

    // semestresFc.value = semestres.filter(semestre => semestre.annee.opt.alternance);
    // semestresFi.value = semestres.filter(semestre => !semestre.annee.opt.alternance);
    const firstYear = Object.keys(semestresFi.value)[0]
    if (firstYear && semestresFi.value[firstYear].length > 0) {
      selectedSemestre.value = semestresFi.value[firstYear][0]
    }
  } catch (error) {
    errorMessage.value = 'Erreur lors de la récupération des semestres.'
  } finally {
    isLoading.value = false
  }
}

onMounted(
    getSemestres
)

const selectSemestre = (semestre) => {
  selectedSemestre.value = semestre
}
</script>

<template>
  <div class="flex justify-between gap-10">
    <Fieldset class="w-full">
      <template #legend>
        <div class="flex items-center pl-2">
          <i class="pi pi-briefcase bg-yellow-400 bg-opacity-20 rounded-full p-4 text-yellow-500"/>
          <div class="flex flex-col">
            <span class="font-bold px-2 capitalize">Semestres</span>
            <em class="text-muted-color px-2">Étudiants, absences, notes, fin de semestre</em>
          </div>
        </div>
      </template>
      <ListSkeleton v-if="isLoading" class="mt-4"/>
      <div v-else-if="errorMessage" class="error-message">{{ errorMessage }}</div>
      <div v-else class="flex gap-10 mt-4">
        <div class="w-1/2 flex gap-4">
          <ul v-for="(semestres, type) in { 'Formation Initiale': semestresFi, 'Formation Continue': semestresFc }"
              :key="type" class="w-1/2">
            <Fieldset :legend="type" class="max-h-96 overflow-auto">
              <li v-for="(semestresByYear, year) in semestres"
                  :key="year" class="mb-2 text-sm">
                <div class="text-muted-color text-sm">{{ year }}</div>
                <ul>
                  <li v-for="semestre in semestresByYear"
                      :key="semestre.id"
                      @click="selectSemestre(semestre)"
                      class="cursor-pointer w-full border-b p-1">
                    <div class="hover:bg-primary-400 hover:bg-opacity-10 rounded-md w-full p-2"
                         :class="{'bg-primary-400 bg-opacity-10': selectedSemestre && selectedSemestre.id === semestre.id}">
                      {{ semestre.libelle }}
                    </div>
                  </li>
                </ul>
              </li>
            </Fieldset>
          </ul>
        </div>
        <div class="w-1/2 " v-if="selectedSemestre">
          <h3 class="font-bold text-xl mb-4">Actions pour {{ selectedSemestre.libelle }}</h3>
          <PanelMenu :model="panelMenuItems" multiple>
            <template #item="{ item }">
              <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                <a v-ripple class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2" :href="href" @click="navigate">
                  <span :class="item.icon" />
                  <span class="ml-2">{{ item.label }}</span>
                </a>
              </router-link>
              <a v-else v-ripple class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2" :href="item.url" :target="item.target">
                <span :class="item.icon" />
                <span class="ml-2">{{ item.label }}</span>
                <span v-if="item.items" class="pi pi-angle-down text-primary ml-auto" />
              </a>
            </template>
          </PanelMenu>
        </div>
      </div>
    </Fieldset>
  </div>
</template>

<style scoped>
.error-message {
  color: red;
  text-align: center;
  font-size: 1.2em;
}
</style>
