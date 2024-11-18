<script setup>
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';

import { ref } from 'vue';

const checked1 = ref(true);

const props = defineProps({
    logo: {
        type: String,
        required: true
    },
});
</script>
<template>
    <div class="bg-surface-50 dark:bg-surface-950 px-6 py-20 md:px-12 lg:px-20">
        <div class="bg-surface-0 dark:bg-surface-900 p-6 shadow rounded-border w-full lg:w-6/12 mx-auto">
            <div class="text-center mb-8">
                <img src="{{logo}}" class="mb-4" alt="">
                <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Welcome Back</div>
                <span class="text-surface-600 dark:text-surface-200 font-medium leading-normal">Don't have an account?</span>
                <a class="font-medium no-underline ml-2 text-primary cursor-pointer">Create today!</a>
            </div>

            <div>
                <label for="email1" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Email</label>
                <InputText id="email1" type="text" placeholder="Email address" class="w-full mb-4" />

                <label for="password1" class="text-surface-900 dark:text-surface-0 font-medium mb-2 block">Password</label>
                <InputText id="password1" type="password" placehoder="Password" class="w-full mb-4" />

                <div class="flex items-center justify-between mb-12">
                    <div class="flex items-center">
                        <Checkbox id="rememberme1" v-model="checked1" :binary="true" class="mr-2" />
                        <label for="rememberme1">Remember me</label>
                    </div>
                    <a class="font-medium no-underline ml-2 text-primary text-right cursor-pointer">Forgot password?</a>
                </div>

                <Button label="Sign In" icon="pi pi-user" class="w-full" />
            </div>
        </div>
    </div>
</template>
<style scoped>
img {
    width: 100px;
    margin: 0 auto;
}
</style>
