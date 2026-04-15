import { createApp } from 'vue';
import { createPinia } from "pinia";
import App from './App.vue';
import '@styles/main.scss';
import { initializeAppData } from '@requests/initializeData';
import router from './router';
import { registerPermissionDirective } from '@utils';
import { setupInactivityTimer } from '@helpers/authService';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Ripple from 'primevue/ripple';
import StyleClass from 'primevue/styleclass';
import Tooltip from 'primevue/tooltip';

import '@/assets/styles.scss';
import '@/assets/tailwind.css';
import {definePreset} from "@primeuix/themes";

const MyPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{yellow.50}',
            100: '{yellow.100}',
            200: '{yellow.200}',
            300: '{yellow.300}',
            400: '{yellow.400}',
            500: '{yellow.500}',
            600: '{yellow.600}',
            700: '{yellow.700}',
            800: '{yellow.800}',
            900: '{yellow.900}',
            950: '{yellow.950}'
        }
    }
});

const pinia = createPinia();
const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: MyPreset,
        options: {
            darkModeSelector: '.app-dark'
        }
    },
    ripple: true
});
app.directive('ripple', Ripple);
app.directive('styleclass', StyleClass);
app.directive('tooltip', Tooltip);
app.use(ToastService);
app.use(ConfirmationService);

app.use(pinia);

// Register the permission directive
registerPermissionDirective(app);

// Ajouter un contenu temporaire avant le montage
import GlobalLoader from '@components/loader/GlobalLoader.vue';
const loadingElement = document.createElement('div');
loadingElement.id = 'loading';
document.body.appendChild(loadingElement);

const loaderApp = createApp(GlobalLoader);
loaderApp.mount(loadingElement);

// Initialiser les données et monter l'application
(async () => {
    try {
        await initializeAppData();
        // Initialiser le minuteur d'inactivité (45 min)
        setupInactivityTimer();
    } catch (error) {
        console.error('Erreur lors de l\'initialisation de l\'application:', error);
    } finally {
        // Supprimer le contenu temporaire après l'initialisation
        document.body.removeChild(loadingElement);
        // Monter l'application
        app.mount('#app');
    }
})();
