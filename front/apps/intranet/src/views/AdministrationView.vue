<script setup>
import { onMounted, ref } from 'vue'
import { useSemestreStore } from 'common-stores'
import api from '@/axios';

const semestreStore = useSemestreStore()

const diplomes = ref([])
const selectedDiplome = ref(null)
const selectedPn = ref(null)
const activePanel = ref([])
const panels = ref([])

onMounted(async () => {
    const departementId = localStorage.getItem('departement')
    diplomes.value = await getDiplomes(departementId)
})

const onPanelUpdate = async (newValue) => {
    //parcourir les valeurs de newValue, regarder si c'est une clé présente dans panels, si non, get
    newValue.forEach(async (value) => {
        if (!panels.value[value]) {
            panels.value[value] = await semestreStore.getSemestre(value)
        }
    })
}

async function getDiplomes (departementId) {
    const response = await api.get(`/api/diplomes-par-departement/${departementId}`)
    return await response.data
}

async function changeDiplome (diplome) {
    selectedDiplome.value = diplome
    const response = await api.get(`/api/structure_diplomes/${diplome.id}`)
    selectedDiplome.value = await response.data
    console.log(selectedDiplome.value)
    selectedPn.value = null
    // parcours les structurePns dans selectedDiplome et prendre le plus récent par rapport à l'année de publication par défaut
    selectedPn.value = selectedDiplome.value.structurePns.reduce((prev, current) => {
        return (prev.anneePublication > current.anneePublication) ? prev : current
    })

    console.log(selectedDiplome.value)
}
</script>

<template>
    <div>
        <h1>Administration</h1>
    </div>
    test maquette ...

    <p>
        <Button v-for="diplome in diplomes['member']" :key="diplome.id"
                class="m-1"
                @click="changeDiplome(diplome)" :aria-label="diplome.libelle"
                :title="diplome.libelle">{{ diplome.typeDiplome.sigle }}<br>{{ diplome.sigle }}
        </Button>
        |
    </p>

    <Select v-if="selectedDiplome" v-model="selectedPn"
            :options="selectedDiplome.structurePns"
            optionLabel="libelle"
            placeholder="Selectionner un PN"
            class="w-full md:w-56"/>


    <Accordion multiple v-if="selectedDiplome && selectedPn"
               :value="activePanel"
               @update:value="onPanelUpdate"
    >
        <template v-for="annee in selectedPn.structureAnnees">
            <AccordionPanel :value="semestre.id"
                            v-for="semestre in annee.structureSemestres" :key="semestre.id"

            >
                <AccordionHeader>{{ semestre.libelle }} | {{ annee.libelle }}</AccordionHeader>
                <AccordionContent>
                    <div v-if="panels[semestre.id]">
                        <div v-for="ue in panels[semestre.id].structureUes" :key="ue.id">
                            <h3>{{ ue.libelle }}</h3>
                            <ul role="list" class="divide-y divide-gray-100">
                                <li class="flex justify-between gap-x-6 py-5" v-for="ueEc in ue.scolEnseignementUes"
                                    :key="ue.id">
                                    <div class="flex min-w-0 gap-x-4">
                                        <div class="min-w-0 flex-auto">
                                            <p class="text-sm/6 font-semibold text-gray-900">
                                                {{ ueEc.enseignement.codeMatiere }}</p>
                                            <p class="mt-1 truncate text-xs/5 text-gray-500">
                                                {{ ueEc.enseignement.libelle }}</p>
                                        </div>
                                    </div>
                                    <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                                        <p class="text-sm/6 text-gray-900">Co-Founder / CEO</p>
                                        <p class="mt-1 text-xs/5 text-gray-500">Last seen
                                            <time datetime="2023-01-23T13:23Z">3h ago</time>
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div v-else>
                        <p>Chargement...</p>
                    </div>
                </AccordionContent>
            </AccordionPanel>
        </template>
    </Accordion>
</template>

<style scoped>

</style>
