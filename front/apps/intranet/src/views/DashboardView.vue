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
const agendaEvents = ref([]);
const isLoadingActu = ref(true);
const isLoadingAgenda = ref(true);

onMounted(async() => {
    await store.getUser();

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
    } finally {
        isLoadingActu.value = false;
    }

    // requete axios pour recuperer l'agenda
    try {
        const { data } = await api.get(`/api/agenda`);
        agendaEvents.value = data.map(agenda => ({
            title: agenda.title,
            date: new Date(agenda.pubDate).toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }),
            content: agenda.description,
            image: agenda.image,
            link: agenda.link
        }));
    } catch (error) {
        console.error('Error fetching agenda:', error);
    } finally {
        isLoadingAgenda.value = false;
    }
});

const isPersonnel = computed(() => store.userType === 'personnels');
const isEtudiant = computed(() => store.userType === 'etudiants');

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
            <div class="card-title mb-4">
                <div class="font-semibold text-xl">Agenda de l'IUT</div>
                <em>Les évènements à venir</em>
            </div>
            <div class="card-content flex justify-between gap-10 mb-8">
                <div v-if="isLoadingAgenda" class="flex justify-between w-full gap-10">
                    <div class="w-full">
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton width="50%" class="mb-2"></Skeleton>
                        <Skeleton height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                    <div class="w-full">
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton width="50%" class="mb-2"></Skeleton>
                        <Skeleton height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                    <div class="w-full">
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton width="50%" class="mb-2"></Skeleton>
                        <Skeleton height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                    <div class="w-full">
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton width="50%" class="mb-2"></Skeleton>
                        <Skeleton height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                </div>
                <div v-else v-for="(event, index) in agendaEvents" class="p-4 rounded-md flex-1 flex flex-col justify-between gap-2">
                    <div class="flex flex-col gap-2 items-start">
                        <div>
                            <div class="font-bold text-lg">
                                {{ event.title }}
                            </div>
                            <div class="text-sm text-muted-color">
                                {{ event.date }}
                            </div>
                        </div>
                        <div>
                            <p>
                                {{ event.content }}
                            </p>
                        </div>
                    </div>
                    <Button class="bg-primary-light" label="En savoir plus" icon="pi pi-external-link" iconPos="right" @click="redirectTo(event.link)"/>
                </div>
            </div>

            <div class="card-title mb-4">
                <div class="font-semibold text-xl">Actualités</div>
                <em>Les évènements passés</em>
            </div>
            <div class="flex justify-between gap-10">
                <div v-if="isLoadingActu" class="flex justify-between w-full gap-10">
                    <div class="w-full">
                        <Skeleton width="100%" height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="50%" class="mb-2"></Skeleton>
                        <Skeleton height="3rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                    <div class="w-full">
                        <Skeleton width="100%" height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton height="3rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                    <div class="w-full">
                        <Skeleton width="100%" height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton height="3rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                    <div class="w-full">
                        <Skeleton width="100%" height="5rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" class="mb-2"></Skeleton>
                        <Skeleton height="3rem" class="mb-2"></Skeleton>
                        <Skeleton width="100%" height="2rem"></Skeleton>
                    </div>
                </div>
                <Card v-else v-for="(event, index) in actuEvents" :key="index" style="width: 25rem" class="card-actus flex flex-col justify-between overflow-hidden w-25">
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
