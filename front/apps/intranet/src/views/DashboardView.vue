<script setup>
import { useUsersStore } from "common-stores";
import { onMounted, computed, ref } from "vue";
import { formatDateLong } from "common-helpers";

import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";

const store = useUsersStore();
const date = '';
const initiales = '';

onMounted(async() => {
    await store.fetchUser();

    const date = new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    const initiales = computed(() =>
        store.user?.prenom?.charAt(0) + store.user?.nom?.charAt(0) || ''
    );
});

const hasRolePermanent = computed(() => store.user.roles?.includes('ROLE_PERMANENT'));
</script>

<template>
    <div>
        <div class="m-5 mb-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-20 bg-violet-400 rounded-full flex items-center justify-center">
                        <template v-if="store.user.photoName">
                            <img :src="store.user.photoName" alt="photo de profil" class="rounded-full">
                        </template>
                        <template v-else>
                            <span class="text-gray-700 text-xl">{{ initiales }}</span>
                        </template>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-semibold"><span class="font-light">Bonjour,</span> {{ store.user.prenom }}</h1>
                        <small>{{ formatDateLong(date) }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card h-full">
            <div class="font-semibold text-xl mb-4">Dernières actualités du département</div>
            <Stepper value="1">
                <StepItem value="1">
                    <Step>Header I</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col h-48">
                            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content I</div>
                        </div>
                        <div class="py-6">
                            <Button label="Next" @click="activateCallback('2')" />
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="2">
                    <Step>Header II</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col h-48">
                            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content II</div>
                        </div>
                        <div class="flex py-6 gap-2">
                            <Button label="Back" severity="secondary" @click="activateCallback('1')" />
                            <Button label="Next" @click="activateCallback('3')" />
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="3">
                    <Step>Header III</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col h-48">
                            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content III</div>
                        </div>
                        <div class="py-6">
                            <Button label="Back" severity="secondary" @click="activateCallback('2')" />
                        </div>
                    </StepPanel>
                </StepItem>
            </Stepper>
        </div>

        <DashboardPersonnel v-if="hasRolePermanent" />
    </div>
</template>
