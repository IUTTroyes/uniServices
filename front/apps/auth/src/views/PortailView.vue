<script setup>
import { ref } from 'vue';
import axios from 'axios';

const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];
if (token) {
  localStorage.setItem
('token', token);
}
const tokenParts = token?.split('.');

const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
const username = payload.username;

const redirectToUnifolio = async () => {
  window.location.replace('http://localhost:5174');
};

const redirectToIntranet = async () => {
  window.location.replace('http://localhost:5175');
}
</script>

<template>
        <main>
            <p>Hello {{ username }}</p>
            <Fieldset legend="Portail" class="register">
                <Card>
                    <template #title>Tools</template>
                    <template #content>
                        <Button label="Intranet" outlined @click="redirectToIntranet" />
                        <a href="http://localhost:5175   0 "></a>
                        <Button label="Unifolio" outlined @click="redirectToUnifolio" />
                    </template>
                </Card>
            </Fieldset>
        </main>
</template>

<style scoped>
.logo {
  width: 150px;
  margin: 0 auto 2rem auto;
}

.form {
  width: 100%;
  max-width: 40rem;
}

hr {
  border: 0;
  border-top: 1px solid lightgray;
  margin: 2rem 0;
}

.pi-eye {
  transform: scale(1.6);
  margin-right: 1rem;
}

.pi-eye-slash {
  transform: scale(1.6);
  margin-right: 1rem;
}
</style>
