import PrimeVue from 'primevue/config'
import { setup } from "@storybook/vue3"
import Message from 'primevue/message'
import Button from 'primevue/button'
import '@styles/main.scss';
import Aura from '@primevue/themes/aura';

import {definePreset} from "@primevue/themes";

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

setup((app) => {
    app.use(PrimeVue, {
        theme: {
            preset: MyPreset,
            options: {
                darkModeSelector: '.app-dark'
            }
        }
    });
    app.component('Message', Message)
    app.component('Button', Button)
})

/** @type { import('@storybook/vue3').Preview } */
const preview = {
    parameters: {
        controls: {
            matchers: {
                color: /(background|color)$/i,
                date: /Date$/i
            }
        }
    }
};

export default preview;
