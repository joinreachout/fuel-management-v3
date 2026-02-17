<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-map text-purple-500 mr-2"></i>
        Regional Comparison
      </h3>
      <p class="text-xs text-gray-500 mt-1">Regional statistics and performance</p>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
      </div>

      <div v-else class="space-y-4">
        <!-- Regional Performance Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Region</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600">Stations</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Total Stock</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Capacity</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Fill %</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr
                v-for="region in regions"
                :key="region.name"
                class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                  <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                  {{ region.name }}
                </td>
                <td class="px-4 py-3 text-center text-sm text-gray-600">{{ region.stations }}</td>
                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ region.stock }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ region.capacity }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full"
                        :class="getFillColor(region.fillPercent)"
                        :style="{ width: region.fillPercent + '%' }"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 w-10 text-right">{{ region.fillPercent }}%</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getStatusClass(region.status)">
                    {{ region.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Regional Chart -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 mt-6">
          <div class="text-sm font-semibold text-gray-700 mb-3">
            <i class="fas fa-chart-bar text-purple-600 mr-1"></i>
            Stock Distribution by Region
          </div>
          <div class="h-64 relative">
            <canvas id="regionalChart"></canvas>
          </div>
        </div>

        <!-- Key Insights -->
        <div class="grid grid-cols-2 gap-4 mt-6">
          <div class="bg-gradient-to-br from-blue-50 to-white p-4 rounded-lg border border-blue-100">
            <div class="text-xs text-blue-600 font-semibold mb-1">Highest Capacity</div>
            <div class="text-xl font-bold text-blue-900">{{ insights.highestCapacity }}</div>
            <div class="text-xs text-blue-500 mt-1">{{ insights.highestCapacityValue }} total capacity</div>
          </div>

          <div class="bg-gradient-to-br from-emerald-50 to-white p-4 rounded-lg border border-emerald-100">
            <div class="text-xs text-emerald-600 font-semibold mb-1">Best Performance</div>
            <div class="text-xl font-bold text-emerald-900">{{ insights.bestPerformance }}</div>
            <div class="text-xs text-emerald-500 mt-1">{{ insights.bestPerformanceValue }}% average fill</div>
          </div>

          <div class="bg-gradient-to-br from-amber-50 to-white p-4 rounded-lg border border-amber-100">
            <div class="text-xs text-amber-600 font-semibold mb-1">Needs Attention</div>
            <div class="text-xl font-bold text-amber-900">{{ insights.needsAttention }}</div>
            <div class="text-xs text-amber-500 mt-1">{{ insights.needsAttentionValue }}% fill level</div>
          </div>

          <div class="bg-gradient-to-br from-purple-50 to-white p-4 rounded-lg border border-purple-100">
            <div class="text-xs text-purple-600 font-semibold mb-1">Total Coverage</div>
            <div class="text-xl font-bold text-purple-900">{{ insights.totalStations }}</div>
            <div class="text-xs text-purple-500 mt-1">Stations across all regions</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';
import { regionalComparisonApi } from '../services/api';

const loading = ref(true);
const regions = ref([]);
const insights = ref({
  highestCapacity: 'N/A',
  highestCapacityValue: '0',
  bestPerformance: 'N/A',
  bestPerformanceValue: 0,
  needsAttention: 'N/A',
  needsAttentionValue: 0,
  totalStations: 0
});

let regionalChartInstance = null;

const getFillColor = (percent) => {
  if (percent >= 70) return 'bg-green-500';
  if (percent >= 50) return 'bg-blue-500';
  if (percent >= 30) return 'bg-amber-500';
  return 'bg-red-500';
};

const getStatusClass = (status) => {
  const classes = {
    'Good': 'bg-green-100 text-green-800',
    'Normal': 'bg-blue-100 text-blue-800',
    'Low': 'bg-amber-100 text-amber-800',
    'Critical': 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatTons = (tons) => {
  const num = parseFloat(tons);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K T';
  }
  return num.toFixed(0) + ' T';
};

const createRegionalChart = () => {
  const ctx = document.getElementById('regionalChart');
  if (!ctx) return;

  if (regionalChartInstance) {
    regionalChartInstance.destroy();
  }

  if (regions.value.length === 0) {
    return;
  }

  regionalChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: regions.value.map(r => r.name),
      datasets: [
        {
          label: 'Current Stock (Tons)',
          data: regions.value.map(r => r.stockTons),
          backgroundColor: 'rgba(99, 102, 241, 0.7)',
          borderColor: '#6366f1',
          borderWidth: 1
        },
        {
          label: 'Daily Consumption (Tons)',
          data: regions.value.map(r => r.consumptionTons),
          backgroundColor: 'rgba(245, 158, 11, 0.5)',
          borderColor: '#f59e0b',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: {
            boxWidth: 12,
            font: { size: 11 }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Stock/Consumption (Tons)',
            font: { size: 11 }
          },
          ticks: {
            font: { size: 10 }
          }
        },
        x: {
          ticks: {
            font: { size: 10 }
          }
        }
      }
    }
  });
};

const loadData = async () => {
  try {
    loading.value = true;

    const response = await regionalComparisonApi.get();

    if (response.data.success) {
      const data = response.data.data || [];

      // Transform data for table
      regions.value = data.map(r => ({
        name: r.region_name,
        stations: r.stations_count,
        stock: formatTons(r.total_stock_tons),
        stockTons: parseFloat(r.total_stock_tons),
        capacity: r.stations_count + ' stations',
        fillPercent: Math.round(r.avg_fill_percentage),
        consumptionTons: parseFloat(r.total_consumption_tons_per_day),
        status: r.status
      }));

      // Calculate insights
      if (data.length > 0) {
        // Highest capacity (most stock)
        const highestStock = data.reduce((prev, current) =>
          parseFloat(current.total_stock_tons) > parseFloat(prev.total_stock_tons) ? current : prev
        );

        // Best performance (highest fill %)
        const bestPerf = data.reduce((prev, current) =>
          parseFloat(current.avg_fill_percentage) > parseFloat(prev.avg_fill_percentage) ? current : prev
        );

        // Needs attention (lowest fill %)
        const needsAtt = data.reduce((prev, current) =>
          parseFloat(current.avg_fill_percentage) < parseFloat(prev.avg_fill_percentage) ? current : prev
        );

        // Total stations
        const totalStations = data.reduce((sum, r) => sum + parseInt(r.stations_count), 0);

        insights.value = {
          highestCapacity: highestStock.region_name,
          highestCapacityValue: formatTons(highestStock.total_stock_tons),
          bestPerformance: bestPerf.region_name,
          bestPerformanceValue: Math.round(bestPerf.avg_fill_percentage),
          needsAttention: needsAtt.region_name,
          needsAttentionValue: Math.round(needsAtt.avg_fill_percentage),
          totalStations: totalStations
        };
      }

      createRegionalChart();
    }
  } catch (error) {
    console.error('Error loading regional comparison:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Component specific styles */
</style>
