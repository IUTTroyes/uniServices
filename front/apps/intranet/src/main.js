import 'common-styles/main.scss';
import PrimeVue from "primevue/config";
import Aura from '@primevue/themes/aura';

import router from './router';

import { createApp } from 'vue'
import App from './App.vue'

const app = createApp(App);
app.use(PrimeVue, {
    theme: {
        preset: Aura
    }
});

app.use(router);

app.mount('#app')
