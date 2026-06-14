import { createApp } from 'vue';
import { createPinia } from "pinia";
import App from './App.vue';
import '@styles/main.scss';
import router from './router';
import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Ripple from 'primevue/ripple';
import StyleClass from 'primevue/styleclass';
import Tooltip from 'primevue/tooltip';
import { definePreset } from '@primeuix/themes';
import fr from '@config/fr.json'

const app = createApp(App);
const pinia = createPinia();
import '@/assets/tailwind.css';

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
app.use(pinia);

app.mount('#app');
