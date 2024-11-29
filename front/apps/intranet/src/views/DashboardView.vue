<script setup>
import { useUsersStore } from "@/stores/users.js";
import { onMounted, computed } from "vue";
import { formatDateLong } from "common-helpers";

const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];

const store = useUsersStore();

onMounted(async() => {
    await store.fetchUser();
});

const date = new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
});

const initiales = computed(() =>
    store.user?.prenom?.charAt(0) + store.user?.nom?.charAt(0) || ''
);
</script>

<template>
    <div>
        <div class="m-5 mb-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full flex items-center justify-center">
                        <template v-if="store.user?.photo_name">
                            <img :src="store.user.photo_name" alt="photo de profil" class="rounded-full">
                        </template>
                        <template v-else>
                            <span class="text-gray-700 text-xl">{{ initiales }}</span>
                        </template>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-semibold"><span class="font-light">Bonjour,</span> {{ store.user?.prenom }}</h1>
                        <small>{{ formatDateLong(date) }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="card h-full">
                <div class="font-semibold text-xl mb-4">Card 1</div>
                <p>Use this page to start from scratch and place your custom content.</p>
            </div>
            <div class="card">
                <div class="font-semibold text-xl mb-4">Card 2</div>
                <p>Use this page to start from scratch and place your custom content.</p>
            </div>
        </div>
    </div>
</template>
