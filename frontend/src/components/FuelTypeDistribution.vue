<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-chart-pie text-indigo-500 mr-2"></i>
        Fuel Type Distribution
      </h3>
      <p class="text-xs text-gray-500 mt-1">Distribution across fuel types</p>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
      </div>

      <div v-else class="space-y-4">
        <!-- Pie Chart -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="h-64 relative flex items-center justify-center">
            <canvas id="fuelDistributionChart"></canvas>
          </div>
        </div>

        <!-- Distribution Stats -->
        <div class="space-y-3">
          <div
            v-for="fuel in fuelDistribution"
            :key="fuel.name"
            class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: fuel.color }"></div>
                <span class="text-sm font-semibold text-gray-900">{{ fuel.name }}</span>
              </div>
              <div class="text-right">
                <div class="text-lg font-bold text-gray-900">{{ fuel.volume }}</div>
                <div class="text-xs text-gray-500">{{ fuel.percentage }}%</div>
              </div>
            </div>

            <!-- Progress bar -->
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :style="{ width: fuel.percentage + '%', backgroundColor: fuel.color }"></div>
            </div>

            <!-- Additional stats -->
            <div class="mt-3 grid grid-cols-3 gap-3 text-xs">
              <div>
                <div class="text-gray-500">Stations</div>
                <div class="font-semibold text-gray-900">{{ fuel.stations }}</div>
              </div>
              <div>
                <div class="text-gray-500">Avg Stock</div>
                <div class="font-semibold text-gray-900">{{ fuel.avgStock }}</div>
              </div>
              <div>
                <div class="text-gray-500">Daily Usage</div>
                <div class="font-semibold text-gray-900">{{ fuel.dailyUsage }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Grid -->
        <div class="grid grid-cols-3 gap-4 mt-6 pt-4 border-t border-gray-200">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ summary.totalVolume }}</div>
            <div class="text-xs text-gray-500">Total Stock</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-indigo-600">{{ summary.fuelTypes }}</div>
            <div class="text-xs text-gray-500">Fuel Types</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ summary.avgFill }}</div>
            <div class="text-xs text-gray-500">Avg Fill Level</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';
import { fuelTypesApi } from '../services/api.js';

const loading = ref(true);
const fuelDistribution = ref([]);
const summary = ref({ totalVolume: '—', fuelTypes: 0, avgFill: '—' });

let distributionChartInstance = null;

const createDistributionChart = () => {
  const ctx = document.getElementById('fuelDistributionChart');
  if (!ctx) return;

  if (distributionChartInstance) {
    distributionChartInstance.destroy();
  }

  distributionChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: fuelDistribution.value.map(f => f.name),
      datasets: [{
        data: fuelDistribution.value.map(f => f.percentage),
        backgroundColor: fuelDistribution.value.map(f => f.color),
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverOffset: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            boxWidth: 12,
            font: { size: 11 },
            padding: 15,
            generateLabels: (chart) => {
              const data = chart.data;
              return data.labels.map((label, i) => ({
                text: `${label} (${data.datasets[0].data[i]}%)`,
                fillStyle: data.datasets[0].backgroundColor[i],
                hidden: false,
                index: i
              }));
            }
          }
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const fuel = fuelDistribution.value[context.dataIndex];
              return `${fuel.name}: ${fuel.volume} (${fuel.percentage}%)`;
            }
          }
        }
      }
    }
  });
};

const loadData = async () => {
  loading.value = true;
  try {
    const res = await fuelTypesApi.getDistribution();
    const raw = res.data?.data || [];
    const apiSummary = res.data?.summary || {};

    fuelDistribution.value = raw.map(f => ({
      name: f.name,
      volume: f.volume,
      percentage: f.percentage,
      color: f.color || '#94a3b8',
      stations: f.stations,
      avgStock: f.avg_stock,
      dailyUsage: f.daily_usage,
    }));

    summary.value = {
      totalVolume: apiSummary.total_volume || '—',
      fuelTypes: apiSummary.fuel_types || raw.length,
      avgFill: apiSummary.avg_fill || '—',
    };
  } catch (e) {
    console.error('FuelTypeDistribution: failed to load', e);
  } finally {
    loading.value = false;
    await nextTick();
    createDistributionChart();
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Component specific styles */
</style>
