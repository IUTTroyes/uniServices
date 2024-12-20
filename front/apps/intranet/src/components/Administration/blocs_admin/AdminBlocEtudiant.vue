<script setup>

</script>

<template>
    <div>
        <div class="text-lg opacity-80 font-semibold">Gestion de l'ensemble des étudiants</div>
        <em class="text-muted-color">etudiants, absences, notes, fin de semestre</em>
        <div class="flex justify-between gap-10">
            <Fieldset class="w-full">
                <template #legend>
                    <div class="flex items-center pl-2">
                        <i class="pi pi-briefcase bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary-500"/>
                        <span class="font-bold p-2 capitalize">etudiants</span>
                    </div>
                </template>


            </Fieldset>
        </div>
    </div>
</template>

<style scoped>

</style>
