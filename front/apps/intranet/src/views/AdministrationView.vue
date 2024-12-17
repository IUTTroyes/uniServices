<script setup>
import {onMounted, ref} from "vue";

const date = ref('');
const absences = ref([]);

onMounted(() => {
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
        <div class="card-body">
            <div class="absences">
                <em>Suivi des absences en "temps réel" pour le {{ date }}</em>

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
        </div>
    </div>

    <router-link to="administration/pn">Pn</router-link>

    <RouterView />

</template>

<style scoped>

</style>
