<script setup>
import { useUsersStore } from "@stores";
import { onMounted, computed, ref } from "vue";
import { formatDateLong } from "@helpers/date.js";

const store = useUsersStore();

onMounted(async() => {
    // si étudiant on redirige vers la page d'accueil
    if (store.userType === 'etudiants') {
        redirectTo('https://www.google.com');
    }

    const initiales = computed(() =>
        store.user?.prenom?.charAt(0) + store.user?.nom?.charAt(0) || ''
    );
});

const redirectTo = (link) => {
    window.open(link, '_blank')
}
</script>

<template>
    <div v-if="store.user">
        <div class="m-5 mb-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-20 bg-violet-400 rounded-full flex items-center justify-center">
                        <template v-if="store.userPhoto">
                            <img :src="store.userPhoto" alt="photo de profil" class="rounded-full">
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
