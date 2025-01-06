<script setup>
import AdminBlocSemestre from "@/components/Administration/blocs_admin/AdminBlocSemestre.vue";
import AdminBlocEtudiant from "@/components/Administration/blocs_admin/AdminBlocEtudiant.vue";
import AdminBlocPersonnel from "@/components/Administration/blocs_admin/AdminBlocPersonnel.vue";
import AdminBlocPn from "@/components/Administration/blocs_admin/AdminBlocPn.vue";
import AdminBlocEdt from "@/components/Administration/blocs_admin/AdminBlocEdt.vue";
</script>

<template>
    <div class="card">
        <div class="card-body flex flex-col gap-10">
            <admin-bloc-semestre class="w-full" />
            <div class="flex flex-row justify-between gap-10">
                <admin-bloc-etudiant class="w-full" />
                <admin-bloc-personnel class="w-full" />
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body flex flex-col gap-10">
          <div class="flex flex-row justify-between gap-10">
            <admin-bloc-edt class="w-full" />
            <admin-bloc-pn class="w-full" />
          </div>
        </div>
    </div>

    <RouterView />

</template>

<style scoped>

</style>
