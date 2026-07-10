<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent } from '@components';

const router = useRouter();

const items = [
  { label: 'Import Apogée', icon: 'pi pi-upload', route: '/intranet/administration/etudiant/ajout/apogee' },
  { label: 'Import manuel', icon: 'pi pi-pencil', route: '/intranet/administration/etudiant/ajout/manuel' },
];

const activeTab = ref(localStorage.getItem('activeTab') || items[0].route);

// If the stored tab is from a previous session and lacks /intranet/, patch it
if (activeTab.value && !activeTab.value.startsWith('/intranet/')) {
  activeTab.value = items[0].route;
}

const navigateTo = (route) => {
  activeTab.value = route;
  router.push(route);
};

watch(activeTab, (newTab) => {
  localStorage.setItem('activeTab', newTab);
});

onMounted(async () => {
  router.push(activeTab.value);
});
</script>

<template>
  <HeaderComponent
      icon="pi pi-user-plus"
      titre="Ajout d'étudiants"
      description="Ajoutez de nouveaux étudiants à votre département"
  />
  <div class="card">
    <Tabs :value="activeTab" scrollable>
      <TabList>
        <router-link
            v-for="tab in items"
            :key="tab.label"
            :to="tab.route"
            custom
        >
          <Tab :value="tab.route" @click="navigateTo(tab.route)">
            <div class="flex items-center gap-2 text-inherit uppercase">
              <i :class="tab.icon" />
              <span>{{ tab.label }}</span>
            </div>
          </Tab>
        </router-link>
      </TabList>
    </Tabs>
    <router-view class="mt-6"></router-view>
  </div>
</template>

<style scoped></style>
