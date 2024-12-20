<script setup>
import AdminBlocSemestre from "@/components/Administration/blocs_admin/AdminBlocSemestre.vue";
import AdminBlocEtudiant from "@/components/Administration/blocs_admin/AdminBlocEtudiant.vue";
import AdminBlocPersonnel from "@/components/Administration/blocs_admin/AdminBlocPersonnel.vue";
</script>

<template>
    <div class="card">
<!--        <div class="card-header mb-6 text-xl font-bold">-->
<!--            Administration-->
<!--        </div>-->
        <div class="card-body flex flex-col gap-10">
            <admin-bloc-semestre class="w-full" />
            <div class="flex flex-row justify-between gap-10">
                <admin-bloc-etudiant class="w-full" />
                <admin-bloc-personnel class="w-full" />
            </div>
        </div>
    </div>

    <router-link to="administration/pn">Pn</router-link>

    <RouterView />

</template>

<style scoped>

</style>
