<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-dollar-sign text-green-500 mr-2"></i>
        Cost Analysis Dashboard
      </h3>
      <p class="text-xs text-gray-500 mt-1">Price trends and cost statistics</p>
    </div>

    <!-- Content -->
    <div class="p-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
      </div>

      <div v-else class="space-y-4">
        <!-- Price Trend Chart -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="text-sm font-semibold text-gray-700 mb-3">
            <i class="fas fa-chart-line text-green-600 mr-1"></i>
            Fuel Price Trends (Last 30 Days)
          </div>
          <div class="h-48 relative">
            <canvas id="costTrendChart"></canvas>
          </div>
        </div>

        <!-- Cost Statistics Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gradient-to-br from-blue-50 to-white p-4 rounded-lg border border-blue-100">
            <div class="text-xs text-blue-600 font-semibold mb-1">Average Cost per Liter</div>
            <div class="text-2xl font-bold text-blue-900">{{ avgCostPerLiter }}</div>
            <div class="text-xs text-blue-500 mt-1">
              <i class="fas fa-arrow-down mr-1"></i>
              -2.3% vs last month
            </div>
          </div>

          <div class="bg-gradient-to-br from-emerald-50 to-white p-4 rounded-lg border border-emerald-100">
            <div class="text-xs text-emerald-600 font-semibold mb-1">Total Monthly Spend</div>
            <div class="text-2xl font-bold text-emerald-900">{{ totalMonthlySpend }}</div>
            <div class="text-xs text-emerald-500 mt-1">
              <i class="fas fa-arrow-up mr-1"></i>
              +5.1% vs last month
            </div>
          </div>

          <div class="bg-gradient-to-br from-amber-50 to-white p-4 rounded-lg border border-amber-100">
            <div class="text-xs text-amber-600 font-semibold mb-1">Cost per Ton</div>
            <div class="text-2xl font-bold text-amber-900">{{ avgCostPerTon }}</div>
            <div class="text-xs text-amber-500 mt-1">Diesel average</div>
          </div>

          <div class="bg-gradient-to-br from-purple-50 to-white p-4 rounded-lg border border-purple-100">
            <div class="text-xs text-purple-600 font-semibold mb-1">Savings Opportunity</div>
            <div class="text-2xl font-bold text-purple-900">€45K</div>
            <div class="text-xs text-purple-500 mt-1">Via bulk ordering</div>
          </div>
        </div>

        <!-- Fuel Type Breakdown -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="text-sm font-semibold text-gray-700 mb-3">Cost Breakdown by Fuel Type</div>
          <div class="space-y-2">
            <div v-for="fuel in fuelCostBreakdown" :key="fuel.name" class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: fuel.color }"></div>
                <span class="text-sm text-gray-700">{{ fuel.name }}</span>
              </div>
              <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-900">{{ fuel.cost }}</span>
                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full rounded-full" :style="{ width: fuel.percentage + '%', backgroundColor: fuel.color }"></div>
                </div>
                <span class="text-xs text-gray-500 w-12 text-right">{{ fuel.percentage }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';
import { costAnalysisApi } from '../services/api';

const loading = ref(true);
const avgCostPerLiter = ref('€0.00');
const totalMonthlySpend = ref('€0');
const avgCostPerTon = ref('€0');
const fuelCostBreakdown = ref([]);
const priceData = ref([]);

let costChartInstance = null;

const loadData = async () => {
  try {
    loading.value = true;

    const response = await costAnalysisApi.get();

    if (response.data.success) {
      const data = response.data.data;

      // Set summary metrics
      avgCostPerLiter.value = data.avg_cost_per_liter ? `€${data.avg_cost_per_liter}` : '€0.00';
      totalMonthlySpend.value = data.total_monthly_spend ? `€${formatCurrency(data.total_monthly_spend)}` : '€0';
      avgCostPerTon.value = data.avg_cost_per_ton ? `€${Math.round(data.avg_cost_per_ton)}` : '€0';

      // Set fuel breakdown
      if (data.fuel_breakdown && data.fuel_breakdown.length > 0) {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
        fuelCostBreakdown.value = data.fuel_breakdown.map((fuel, idx) => ({
          name: fuel.fuel_name,
          cost: `€${formatCurrency(fuel.total_cost)}`,
          percentage: parseFloat(fuel.percentage_of_total) || 0,
          color: colors[idx % colors.length]
        }));
      }

      // Set price trends
      priceData.value = data.price_trends || [];

      createCostChart();
    }
  } catch (error) {
    console.error('Error loading cost analysis:', error);
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => {
  const num = parseFloat(value);
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  } else if (num >= 1000) {
    return (num / 1000).toFixed(0) + 'K';
  }
  return num.toFixed(0);
};

const createCostChart = () => {
  const ctx = document.getElementById('costTrendChart');
  if (!ctx) return;

  if (costChartInstance) {
    costChartInstance.destroy();
  }

  if (priceData.value.length === 0) {
    return;
  }

  // Group by fuel type
  const fuelGroups = {};
  priceData.value.forEach(item => {
    if (!fuelGroups[item.fuel_name]) {
      fuelGroups[item.fuel_name] = [];
    }
    fuelGroups[item.fuel_name].push(item);
  });

  // Get labels (dates)
  const labels = priceData.value
    .map(item => new Date(item.price_date))
    .filter((date, index, self) => self.findIndex(d => d.getTime() === date.getTime()) === index)
    .sort((a, b) => a - b)
    .map(date => date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }));

  // Create datasets
  const colors = [
    { border: '#3b82f6', bg: 'rgba(59, 130, 246, 0.1)' },
    { border: '#10b981', bg: 'rgba(16, 185, 129, 0.1)' },
    { border: '#f59e0b', bg: 'rgba(245, 158, 11, 0.1)' },
    { border: '#ef4444', bg: 'rgba(239, 68, 68, 0.1)' }
  ];

  const datasets = Object.keys(fuelGroups).map((fuelName, idx) => {
    const color = colors[idx % colors.length];
    const data = fuelGroups[fuelName]
      .sort((a, b) => new Date(a.price_date) - new Date(b.price_date))
      .map(item => parseFloat(item.price_per_liter));

    return {
      label: fuelName,
      data,
      borderColor: color.border,
      backgroundColor: color.bg,
      tension: 0.4,
      fill: true,
      borderWidth: 2
    };
  });

  costChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets
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
          beginAtZero: false,
          title: {
            display: true,
            text: 'Price (€/L)',
            font: { size: 11 }
          },
          ticks: {
            font: { size: 10 }
          }
        },
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 45,
            font: { size: 9 }
          }
        }
      }
    }
  });
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Component specific styles */
</style>
