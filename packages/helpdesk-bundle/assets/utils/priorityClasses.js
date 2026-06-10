import {ref} from 'vue';
const getPriorityClasses = (priority) => {
    switch (priority) {
        case 'CRITIQUE': return 'pi pi-exclamation-triangle text-red-600';
        case 'HAUTE':    return 'pi pi-angle-double-up text-orange-500';
        case 'MOYENNE':  return 'pi pi-angle-up text-blue-500';
        case 'BASSE':    return 'pi pi-angle-down text-green-500';
        default:         return 'pi pi-minus text-gray-400';
    }
};

const priorities = ref([
    { label: 'Basse', value: 'BASSE' },
    { label: 'Moyenne', value: 'MOYENNE' },
    { label: 'Haute', value: 'HAUTE' },
    { label: 'Critique', value: 'CRITIQUE' }
]);
