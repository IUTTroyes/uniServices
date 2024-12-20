<script setup>
import {onMounted, ref} from "vue";
import {getServiceDepartementSemestresActifs} from "common-requests";

const semestresFc = ref([]);
const semestresFi = ref([]);
const itemsFc = ref([]);
const itemsFi = ref([]);

onMounted(async () => {
    const departementId = localStorage.getItem('departement');
    const semestres = await getServiceDepartementSemestresActifs(departementId);
    semestresFc.value = semestres.semestresFc;
    semestresFi.value = semestres.semestresFi;

    semestresFc.value.forEach(semestreFc => {
        itemsFc.value.push({
            label: semestreFc.libelle,
            items: [
                {label: 'Étudiants', icon: 'pi pi-user', command: () => {}},
                {label: 'Groupes', icon: 'pi pi-users', command: () => {}},
                {label: 'Absences', icon: 'pi pi-calendar', command: () => {}},
                {label: 'Notes', icon: 'pi pi-book', command: () => {}},
                {label: 'Fin de semestre', icon: 'pi pi-check', command: () => {}},
            ]
        });
    });

    semestresFi.value.forEach(semestreFi => {
        itemsFi.value.push({
            label: semestreFi.libelle,
            items: [
                {label: 'Étudiants', icon: 'pi pi-user', command: () => {}},
                {label: 'Groupes', icon: 'pi pi-users', command: () => {}},
                {label: 'Absences', icon: 'pi pi-calendar', command: () => {}},
                {label: 'Notes', icon: 'pi pi-book', command: () => {}},
                {label: 'Fin de semestre', icon: 'pi pi-check', command: () => {}},
            ]
        });
    });
});
</script>

<template>
    <div>
        <div class="text-lg opacity-80 font-semibold">Gestion des semestres</div>
        <em class="text-muted-color">etudiants, absences, notes, fin de semestre</em>
        <div class="flex justify-between gap-10">
            <Fieldset class="w-full">
                <template #legend>
                    <div class="flex items-center pl-2">
                        <i class="pi pi-briefcase bg-yellow-400 bg-opacity-20 rounded-full p-4 text-yellow-500"/>
                        <span class="font-bold p-2 capitalize">formation continue</span>
                    </div>
                </template>
                    <PanelMenu :model="itemsFc" multiple class="custom-panelmenu-header"/>

            </Fieldset>
            <Fieldset class="w-full">
                <template #legend>
                    <div class="flex items-center pl-2">
                        <i class="pi pi-book bg-yellow-400 bg-opacity-20 rounded-full p-4 text-yellow-500"/>
                        <span class="font-bold p-2 capitalize">formation initiale</span>
                    </div>
                </template>
                <PanelMenu :model="itemsFi" multiple class="custom-panelmenu-header"/>
            </Fieldset>
        </div>
    </div>
</template>

<style scoped>

</style>
