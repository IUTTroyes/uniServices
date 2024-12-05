<script setup>
import { ref, computed } from 'vue';
import { startOfWeek, addDays, format } from 'date-fns';
import { fr } from 'date-fns/locale';
import Edt from '@/components/Edt.vue';

const menu = ref();
const items = ref([
    {
        label: 'Options',
        items: [
            {
                label: 'Lien synchronisation ICAL',
                icon: 'pi pi-calendar'
            },
            {
                label: 'Chronologique',
                icon: 'pi pi-clock'
            }
        ]
    }
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const currentWeek = ref(new Date());

const getWeekDays = (date) => {
    const startDate = startOfWeek(date, { weekStartsOn: 1 });
    return Array.from({ length: 5 }, (_, i) => {
        const day = addDays(startDate, i);
        return {
            dayName: format(day, 'EEEE', { locale: fr }),
            dayNumber: format(day, 'dd/MM', { locale: fr })
        };
    });
};

const weekNumber = computed(() => format(currentWeek.value, 'w', { locale: fr }));
const days = computed(() => getWeekDays(currentWeek.value));

const previousWeek = () => {
    currentWeek.value = addDays(currentWeek.value, -7);
};

const nextWeek = () => {
    currentWeek.value = addDays(currentWeek.value, 7);
};

const tabs = computed(() => [
    { title: 'Personnel', content: Edt, value: '0', data: { type: 'personnel', info: 'Données pour le personnel', days: days.value } },
    { title: 'Département', content: Edt, value: '1', data: { type: 'departement', info: 'Données pour le département', days: days.value } },
]);
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <div class="card h-full">
            <div class="card-header flex justify-between items-start">
                <div class="font-semibold text-xl mb-4">Emploi du temps</div>
                <div class="flex gap-2">
                    <Button label="Vue Liste" />
                    <Button type="button" severity="secondary" icon="pi pi-ellipsis-v" @click="toggle" aria-haspopup="true" aria-controls="overlay_menu" />
                    <Menu ref="menu" id="overlay_menu" :model="items" :popup="true" />
                </div>
            </div>
            <div class="card-body">
                <div class="w-full flex justify-center mt-5 gap-5">
                    <Button icon="pi pi-arrow-left" severity="secondary" aria-label="previousWeek" @click="previousWeek" />
                    <div class="flex flex-col">
                        <Tag :value="`Semaine ${weekNumber}`"></Tag>
                        <small>Semaine de formation *à calculer*</small>
                    </div>
                    <Button icon="pi pi-arrow-right" severity="secondary" aria-label="nextWeek" @click="nextWeek" />
                </div>

                <Tabs value="0">
                    <TabList>
                        <Tab v-for="tab in tabs" :key="tab.title" :value="tab.value">{{ tab.title }}</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel v-for="tab in tabs" :key="tab.content" :value="tab.value">
                            <component :is="tab.content" :data="tab.data" />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
</style>
