import { createApp } from 'vue';
import { createPinia } from "pinia";
import App from './App.vue';
import '@styles/main.scss';
import router from './router';
import { registerPermissionDirective } from '@utils';
import { setupInactivityTimer } from '@helpers/authService';
import { registerAllBundleWidgets } from './widgets/registerBundleWidgets';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Ripple from 'primevue/ripple';
import StyleClass from 'primevue/styleclass';
import Tooltip from 'primevue/tooltip';
import { definePreset } from '@primeuix/themes';
import fr from '@config/fr.json';

import './assets/styles.scss';
import './assets/tailwind.css';
import '@styles/forms.css';

const MyPreset = definePreset(Aura, {
    semantic: {
        formField: { borderRadius: 'var(--ui-radius-control)' },
        primary: {
            50: 'var(--color-uni-service-50)',
            100: 'var(--color-uni-service-100)',
            200: 'var(--color-uni-service-200)',
            300: 'var(--color-uni-service-300)',
            400: 'var(--color-uni-service-400)',
            500: 'var(--color-uni-service-500)',
            600: 'var(--color-uni-service-600)',
            700: 'var(--color-uni-service-700)',
            800: 'var(--color-uni-service-800)',
            900: 'var(--color-uni-service-900)',
            950: 'var(--color-uni-service-950)'
        },
    }
});

const pinia = createPinia();
const app = createApp(App);

registerAllBundleWidgets();

app.use(pinia);
app.use(router);

app.use(PrimeVue, {
    locale : fr.fr,
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
        await router.isReady();
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
