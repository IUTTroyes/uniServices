<script setup>
import { ref, onMounted, watch, computed } from "vue";

const props = defineProps({
  // Accept either an array of EtudiantNote objects ({ note: number | null, ... }) or numbers
  notes: {
    type: Array,
    required: false,
    default: () => []
  }
});

const chartData = ref();
const chartOptions = ref();

const bins = [
  { label: '18–20', min: 18, max: 20, includeMax: true },
  { label: '16–18', min: 16, max: 18, includeMax: false },
  { label: '14–16', min: 14, max: 16, includeMax: false },
  { label: '12–14', min: 12, max: 14, includeMax: false },
  { label: '10–12', min: 10, max: 12, includeMax: false },
  { label: '5–10', min: 5, max: 10, includeMax: false },
  { label: '0–5', min: 0, max: 5, includeMax: false }
];

const numericNotes = computed(() => {
  if (!Array.isArray(props.notes)) return [];
  // Map to numeric values when objects are provided, ignore null/undefined and non-numeric
  return props.notes
    .map((n) => {
      if (n == null) return null;
      if (typeof n === 'number') return isFinite(n) ? n : null;
      if (typeof n === 'object' && n !== null) {
        const ps = n.presenceStatut ?? n.presence_status ?? null;
        if (ps === 'absent_justifie' || ps === 'dispense') return null;
        const v = n.note ?? n.Note ?? null;
        if (typeof v === 'number' && isFinite(v)) {
          // Exclure les notes techniques non comptabilisées
          if (v === -0.01) return null;
          return v;
        }
        return null;
      }
      return null;
    })
    .filter((v) => typeof v === 'number');
});

const setChartData = () => {
  const documentStyle = getComputedStyle(document.documentElement);

  const counts = bins.map(({ min, max, includeMax }) =>
    numericNotes.value.filter((v) => (v >= min && (includeMax ? v <= max : v < max))).length
  );

  return {
    labels: bins.map((b) => b.label),
    datasets: [
      {
        label: "Nombre d'étudiants",
        backgroundColor: documentStyle.getPropertyValue('--p-primary-500') || documentStyle.getPropertyValue('--p-cyan-500'),
        borderColor: documentStyle.getPropertyValue('--p-primary-500') || documentStyle.getPropertyValue('--p-cyan-500'),
        data: counts
      }
    ]
  };
};

const setChartOptions = () => {
  const documentStyle = getComputedStyle(document.documentElement);
  const textColor = documentStyle.getPropertyValue('--p-text-color');
  const textColorSecondary = documentStyle.getPropertyValue('--p-text-muted-color');
  const surfaceBorder = documentStyle.getPropertyValue('--p-content-border-color');

  return {
    indexAxis: 'y',
    maintainAspectRatio: false,
    aspectRatio: 0.8,
    plugins: {
      legend: {
        labels: {
          color: textColor
        }
      }
    },
    scales: {
      x: {
        ticks: {
          color: textColorSecondary,
          font: {
            weight: 500
          }
        },
        grid: {
          display: false,
          drawBorder: false
        }
      },
      y: {
        ticks: {
          color: textColorSecondary
        },
        grid: {
          color: surfaceBorder,
          drawBorder: false
        }
      }
    }
  };
};

onMounted(() => {
  chartData.value = setChartData();
  chartOptions.value = setChartOptions();
});

watch(numericNotes, () => {
  chartData.value = setChartData();
});
</script>

<template>
  <div class="card">
    <Chart type="bar" :data="chartData" :options="chartOptions" class="h-[20rem]" />
  </div>
</template>
