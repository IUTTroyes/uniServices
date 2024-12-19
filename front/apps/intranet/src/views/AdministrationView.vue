<script setup>
import {onMounted, ref} from "vue";
import {useDiplomeStore} from "common-stores";
import {getServiceDepartementSemestresActifs} from "common-requests";

const diplomeStore = useDiplomeStore();

const date = ref('');
const absences = ref([]);
const semestresFc = ref([]);
const semestresFi = ref([]);

onMounted(async () => {
    const departementId = localStorage.getItem('departement');
    const semestres = await getServiceDepartementSemestresActifs(departementId);
    semestresFc.value = semestres.semestresFc;
    semestresFi.value = semestres.semestresFi;


    date.value = new Date().toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric'
    });

    absences.value = [
        {semestre: 'S1', etudiant: 'John Doe', heure: '08:00', prof: 'Jane Doe', matiere: 'Maths'},
        {semestre: 'S1', etudiant: 'John Doe', heure: '08:00', prof: 'Jane Doe', matiere: 'Maths'},
        {semestre: 'S1', etudiant: 'John Doe', heure: '08:00', prof: 'Jane Doe', matiere: 'Maths'},
        {semestre: 'S1', etudiant: 'John Doe', heure: '08:00', prof: 'Jane Doe', matiere: 'Maths'},
    ];
})
</script>

<template>
    <div class="page-title mb-6">
        <h1>Administration</h1>
    </div>

    <div class="card">
        <div class="card-header mb-6">
            <h2 class="text-xl">Etudiants</h2>
        </div>
        <div class="card-body flex flex-col gap-10">
            <div class="absences flex flex-col gap-4">
                <em>Suivi des absences pour le {{ date }} entre {{ new Date().getHours() < 13 ? '8h00 et 13h00' : '13h00 et 21h00' }}</em>
                <Message severity="info" icon="pi pi-info-circle">{{ absences.length }} absent.s sur la demie journée en cours.</Message>

                <DataTable :value="absences" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" stripedRows showGridlines tableStyle="min-width: 50rem"
                           paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                           currentPageReportTemplate="{first} to {last} of {totalRecords}">
                    <template #paginatorstart>
                        <Button type="button" icon="pi pi-refresh" text />
                    </template>
                    <template #paginatorend>
                        <Button type="button" icon="pi pi-download" text />
                    </template>
                    <Column field="semestre" header="Semestre" sortable style="width: 25%"></Column>
                    <Column field="etudiant" header="Etudiant" sortable style="width: 25%"></Column>
                    <Column field="heure" header="Heure" sortable style="width: 25%"></Column>
                    <Column field="prof" header="Prof." sortable style="width: 25%"></Column>
                    <Column field="matiere" header="Matière" sortable style="width: 25%"></Column>
                </DataTable>
            </div>

            <div class="flex justify-between gap-10">
                <div>
                    <h3 class="text-xl">Formation Continue</h3>
                    <div v-for="semestreFc in semestresFc">
                        <router-link to="/">{{semestreFc.libelle}}</router-link>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl">Formation Initiale</h3>
                    <div v-for="semestreFi in semestresFi">
                        <router-link to="/">{{semestreFi.libelle}}</router-link>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl">Général</h3>
                    <div></div>
                </div>
            </div>
        </div>
    </div>

    <router-link to="administration/pn">Pn</router-link>

    <RouterView />

</template>

<style scoped>

</style>
