import { createApp } from 'vue';
import { createPinia } from "pinia";
import App from './App.vue';
import '@styles/main.scss';
import router from './router';
import { initializeAppData } from '@requests/initializeData';

import Aura from '@primevue/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import { definePreset } from '@primevue/themes';
import '@/assets/styles.scss';
import '@/assets/tailwind.css';
import fr from '@config/fr.json'


const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];
if (token) localStorage.setItem('token', token);

const MyPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{violet.50}',
            100: '{violet.100}',
            200: '{violet.200}',
            300: '{violet.300}',
            400: '{violet.400}',
            500: '{violet.500}',
            600: '{violet.600}',
            700: '{violet.700}',
            800: '{violet.800}',
            900: '{violet.900}',
            950: '{violet.950}'
        },
    }
});

const pinia = createPinia();
const app = createApp(App);

app.use(router);

app.use(PrimeVue, {
    locale : fr.fr,
    theme: {
        preset: MyPreset,
        options: {
            darkModeSelector: '.app-dark'
        }
    }
});
app.use(ToastService);
app.use(ConfirmationService);

app.use(pinia);

// Ajouter un contenu temporaire avant le montage
import GlobalLoader from '@components/loader/GlobalLoader.vue';
const loadingElement = document.createElement('div');
loadingElement.id = 'loading';
document.body.appendChild(loadingElement);

const loaderApp = createApp(GlobalLoader);
loaderApp.mount(loadingElement);

// Initialiser les données
await initializeAppData();

// Supprimer le contenu temporaire après l'initialisation
document.body.removeChild(loadingElement);

// Monter l'application
app.mount('#app');
