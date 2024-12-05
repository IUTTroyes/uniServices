<script setup>
import { useUsersStore } from "@/stores/users.js";
import { onMounted, computed, ref } from "vue";
import { formatDateLong } from "common-helpers";

import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";

const store = useUsersStore();
const date = '';
const initiales = '';

onMounted(async() => {
    await store.fetchUser();

    console.log(store.user);

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

        <DashboardPersonnel v-if="hasRolePermanent" />
    </div>
</template>
