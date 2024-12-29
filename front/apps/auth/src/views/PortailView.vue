<script setup>
import { TopbarComponent } from '@components';

const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];
if (token) {
    localStorage.setItem
    ('token', token);
}
const tokenParts = token?.split('.');
const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
const username = payload.username;
const type = payload.type;
const logoUrl = new URL('@/assets/logo/logo_iut.png', import.meta.url).href;
</script>

<template>
    <main>
        <TopbarComponent app-name="Portail" :logo-url="logoUrl"/>

        <div id="features" class="py-6 px-6 lg:px-20 mt-8 mx-0 lg:mx-20">
            <div class="grid grid-cols-12 gap-4 justify-center">
                <div class="col-span-12 text-center mt-20 mb-6">
                    <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">UniServices</div>
                    <span class="text-muted-color text-2xl">À quelle plateforme souhaitez-vous accéder ?</span>
                </div>

                <a href="http://localhost:3001" class="app col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0">
                    <div
                        style="height: 100%; padding: 2px; border-radius: 10px; background: linear-gradient(90deg, rgba(253, 228, 165, 0.2), rgba(192,187,205,0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(192,187,205,0.2))"
                    >
                        <div class="p-4 bg-surface-0 dark:bg-surface-900 h-full" style="border-radius: 8px">
                            <div class="flex items-center justify-center mb-4 bg-surface-100" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                <img src="@/assets/logo/logo_intranet_iut_troyes.svg" alt="logo de l'intranet">
                            </div>
                            <h5 class="mb-2 text-surface-900 dark:text-surface-0">Intranet</h5>
                            <span class="text-surface-600 dark:text-surface-200">
                                Gestion centralisée des services universitaires
                            </span>
                        </div>
                    </div>
                </a>

                <a href="http://localhost:3002" class="app col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0">
                    <div
                        style="height: 100%; padding: 2px; border-radius: 10px; background: linear-gradient(90deg, rgba(145, 226, 237, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(172, 180, 223, 0.2))"
                    >
                        <div class="p-4 bg-surface-0 dark:bg-surface-900 h-full" style="border-radius: 8px">
                            <div class="flex items-center justify-center mb-4 bg-surface-100" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                <img src="@/assets/logo/logo_unifolio.png" alt="logo d'unifolio'">
                            </div>
                            <h5 class="mb-2 text-surface-900 dark:text-surface-0">UniFolio</h5>
                            <span class="text-surface-600 dark:text-surface-200">Création et gestion de portfolios universitaires</span>
                        </div>
                    </div>
                </a>

                <div class="app col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0 disabled">
                    <div
                        style="height: 100%; padding: 2px; border-radius: 10px; background: linear-gradient(90deg, rgba(145, 226, 237, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(172, 180, 223, 0.2))"
                    >
                        <div class="p-4 bg-surface-0 dark:bg-surface-900 h-full" style="border-radius: 8px">
                            <div class="flex items-center justify-center bg-surface-100 mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                <i class="pi pi-fw pi-palette !text-2xl text-cyan-700"></i>
                            </div>
                            <h5 class="mb-2 text-surface-900 dark:text-surface-0">Correcto</h5>
                            <span class="text-surface-600 dark:text-surface-200">Semper risus in hendrerit.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</template>

<style scoped>
.app {
    cursor: pointer;
    transition: transform 0.2s;

    &:hover {
        transform: scale(1.02);
    }

    &.disabled {
        cursor: not-allowed;
        opacity: 0.5;

        &:hover {
            transform: none;
        }
    }
}
</style>
