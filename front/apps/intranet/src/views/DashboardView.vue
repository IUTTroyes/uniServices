<script setup>
import { useUsersStore } from "common-stores";
import { onMounted, computed, ref } from "vue";
import { formatDateLong } from "common-helpers";
import api from '@/axios';

import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";
import DashboardEtudiant from "@/components/Etudiant/Dashboard.vue";

const store = useUsersStore();
const date = '';
const initiales = '';

const actuEvents = ref([]);

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

    // requete axios pour recuperer les actualites
    try {
        const { data } = await api.get(`/api/actualites`);
        actuEvents.value = data.map(actu => ({
            title: actu.title,
            date: new Date(actu.pubDate).toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }),
            content: actu.description,
            image: actu.image,
            link: actu.link
        }));
    } catch (error) {
        console.error('Error fetching actualites:', error);
    }
});

const isPersonnel = computed(() => store.userType === 'personnels');
const isEtudiant = computed(() => store.userType === 'etudiants');

const agendaEvents = ref([
    { title: 'Conférence intéractive sur les émotions', date: '15/10/2020 | 10:30 - 14:00', icon: 'pi pi-shopping-cart' },
    { title: 'Webinaire “Ma première rentrée à l’URCA”', date: '15/10/2020 | 14:00 - 16:00', icon: 'pi pi-cog' },
    { title: 'Webinaire “Transition lycée-université”', date: '15/10/2020 | 16:15 - 18:00', icon: 'pi pi-shopping-cart' },
]);

const redirectTo = (link) => {
    window.open(link, '_blank')
}
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
            <div class="absolute top-0 right-0 rounded-full bg-primary w-10 h-10"></div>
            <div class="card-title mb-4">
                <div class="font-semibold text-xl">Agenda de l'IUT</div>
                <em>Les évènements à venir</em>
            </div>
            <div class="card-content flex justify-between gap-10 mb-8">
                <div v-for="(event, index) in agendaEvents" class="p-4 rounded-md flex-1">
                    <div class="flex flex-col gap-2 items-start">
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
                <Card v-for="(event, index) in actuEvents" :key="index" style="width: 25rem" class="card-actus flex flex-col justify-between overflow-hidden w-25">
                    <template #header>
                        <div class="h-32 w-full overflow-hidden flex items-center">
                            <img :alt="event.title" :src="event.image" />
                        </div>
                        <div class="text-lg font-bold px-5 pt-3">{{event.title}}</div>
                        <div class="text-sm px-5 text-muted-color">{{event.date}}</div>
                    </template>
                    <template #content>
                        <div class="flex flex-col justify-between gap-2">
                            <div>{{event.content}}</div>
                            <Button label="En savoir plus" icon="pi pi-external-link" iconPos="right" class="bg-primary-light" @click="redirectTo(event.link)"/>
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

.card-actus {
    border: solid 1px var(--p-tag-primary-background);
    box-shadow: none;
}
</style>
