<script setup>
import { useUsersStore } from "common-stores";
import { onMounted, computed, ref } from "vue";
import { formatDateLong } from "common-helpers";

import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";
import DashboardEtudiant from "@/components/Etudiant/Dashboard.vue";

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

const isPersonnel = computed(() => store.userType === 'personnels');
const isEtudiant = computed(() => store.userType === 'etudiants');

const agendaEvents = ref([
    { title: 'Conférence intéractive sur les émotions', date: '15/10/2020 | 10:30 - 14:00', icon: 'pi pi-shopping-cart' },
    { title: 'Webinaire “Ma première rentrée à l’URCA”', date: '15/10/2020 | 14:00 - 16:00', icon: 'pi pi-cog' },
    { title: 'Webinaire “Transition lycée-université”', date: '15/10/2020 | 16:15 - 18:00', icon: 'pi pi-shopping-cart' },
]);
const actuEvents = ref([
    { title: 'Conférence intéractive sur les émotions', date: '15/10/2020 | 10:30 - 14:00', icon: 'pi pi-shopping-cart' },
    { title: 'Webinaire “Ma première rentrée à l’URCA”', date: '15/10/2020 | 14:00 - 16:00', icon: 'pi pi-cog' },
    { title: 'Webinaire “Transition lycée-université”', date: '15/10/2020 | 16:15 - 18:00', icon: 'pi pi-shopping-cart' },
    { title: 'Conférence intéractive sur les émotions', date: '15/10/2020 | 10:30 - 14:00', icon: 'pi pi-shopping-cart' },
]);
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
            <div class="card-title mb-4">
                <div class="font-semibold text-xl">Agenda de l'IUT</div>
                <em>Les évènements à venir</em>
            </div>
            <div class="card-content flex justify-between gap-10 mb-8">
                <div v-for="(event, index) in agendaEvents" class="p-4 rounded-md flex-1">
                    <div>
                        <div class="font-bold">
                            {{ event.title }}
                        </div>
                        <div class="text-sm">
                            {{ event.date }}
                        </div>
                        <div>
                            <p>
                                {{ event.content }}
                            </p>
                        </div>
                        <Button class="bg-primary-light" label="En savoir plus" />
                    </div>
                </div>
            </div>

            <div class="card-title mb-4">
                <div class="font-semibold text-xl">Actualités</div>
                <em>Les évènements passés</em>
            </div>
            <div class="flex justify-between gap-10">
                <Card v-for="(event, index) in actuEvents" style="width: 25rem; overflow: hidden">
                    <template #header class="p-card-header">
                        <div class="h-32 w-full overflow-hidden flex items-center">
                            <img alt="user header" src="@/assets/logo/logo_intranet_iut_troyes.svg" />
                        </div>
                    </template>
                    <template #title>{{event.title}}</template>
                    <template #subtitle>{{event.date}}</template>
                    <template #footer>
                        <div class="flex gap-4 mt-1">
                            <Button label="En savoir plus" class="bg-primary-light w-full" />
                        </div>
                    </template>
                </Card>
            </div>


        </div>

        <DashboardPersonnel v-if="isPersonnel" />
        <DashboardEtudiant v-else-if="isEtudiant" />
    </div>
</template>

<style scoped>
.bg-primary-light {
    background-color: var(--p-tag-primary-background);
    border: 1px solid var(--p-tag-primary-background);
    color: var(--primary-color);
}
</style>
