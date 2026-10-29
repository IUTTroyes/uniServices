<script setup>
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { HeaderComponent } from '@components';

const route = useRoute();
const router = useRouter();

const items = [
  { label: 'Par semestre', icon: 'pi pi-list', route: '/intranet/administration/previsionnel/semestre' },
  { label: 'Par enseignant', icon: 'pi pi-user', route: '/intranet/administration/previsionnel/personnel' },
  { label: 'Par matière', icon: 'pi pi-book', route: '/intranet/administration/previsionnel/matiere' },
  { label: 'Vue d\'ensemble', icon: 'pi pi-eye', route: '/intranet/administration/previsionnel/semestre_test' },
  { label: 'HRS/primes', icon: 'pi pi-money-bill', route: '/intranet/administration/previsionnel/primes' },
  { label: 'Actions', icon: 'pi pi-cog', route: '/intranet/administration/previsionnel/actions' },
];

const currentTab = computed(() => {
  const match = items.find(item => route.path.startsWith(item.route));
  return match ? match.route : items[0].route;
});

const navigateTo = (path) => {
  if (route.path !== path) {
    router.push(path);
  }
};
</script>

<template>
  <div>
    <HeaderComponent
      icon="pi pi-calendar"
      titre="Prévisionnels"
      description="Consultez et gérez les prévisionnels de votre département"
    />
    <div class="card">
      <Tabs :value="currentTab" scrollable>
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
      <router-view class="mt-6" />
    </div>
  </div>
</template>
