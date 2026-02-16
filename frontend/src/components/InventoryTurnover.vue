<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-sync-alt text-indigo-500 mr-2"></i>
        Inventory Turnover Rate
      </h3>
      <p class="text-xs text-gray-500 mt-1">Stock rotation metrics by fuel type</p>
    </div>

    <!-- Content -->
    <div class="px-6 py-6">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
      </div>

      <div v-else class="space-y-4">
        <!-- Fuel Type Turnover Cards -->
        <div
          v-for="fuel in fuelData"
          :key="fuel.id"
          class="p-5 rounded-xl border-2 transition-all hover:shadow-md"
          :style="{ borderColor: fuel.color, background: `linear-gradient(135deg, ${fuel.color}10, ${fuel.color}05)` }">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                   :style="{ background: fuel.color + '20' }">
                <i class="fas fa-oil-can text-lg" :style="{ color: fuel.color }"></i>
              </div>
              <div>
                <div class="text-sm font-bold text-gray-800">{{ fuel.name }}</div>
                <div class="text-xs text-gray-500">{{ fuel.category }}</div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-2xl font-bold" :style="{ color: fuel.color }">{{ fuel.turnoverRate }}x</div>
              <div class="text-xs text-gray-500">per month</div>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="relative h-3 bg-gray-100 rounded-full overflow-hidden mb-3">
            <div
              class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000"
              :style="{
                width: `${Math.min(fuel.efficiency, 100)}%`,
                background: `linear-gradient(to right, ${fuel.color}, ${fuel.color}DD)`
              }">
            </div>
          </div>

          <!-- Metrics Grid -->
          <div class="grid grid-cols-3 gap-3">
            <div class="text-center p-2 rounded bg-white border border-gray-200">
              <div class="text-xs text-gray-500">Avg Stock</div>
              <div class="text-sm font-bold text-gray-800">{{ formatNumber(fuel.avgStock) }}L</div>
            </div>
            <div class="text-center p-2 rounded bg-white border border-gray-200">
              <div class="text-xs text-gray-500">Monthly Usage</div>
              <div class="text-sm font-bold text-gray-800">{{ formatNumber(fuel.monthlyUsage) }}L</div>
            </div>
            <div class="text-center p-2 rounded bg-white border border-gray-200">
              <div class="text-xs text-gray-500">Days on Hand</div>
              <div class="text-sm font-bold text-gray-800">{{ fuel.daysOnHand }}</div>
            </div>
          </div>

          <!-- Efficiency Indicator -->
          <div class="mt-3 flex items-center justify-between">
            <div class="text-xs text-gray-600">
              <i :class="fuel.efficiency >= 75 ? 'fas fa-arrow-up text-green-500' : 'fas fa-arrow-down text-amber-500'"></i>
              <span class="ml-1">{{ fuel.efficiency >= 75 ? 'Efficient' : 'Below Target' }} rotation</span>
            </div>
            <div class="text-xs font-semibold" :style="{ color: fuel.color }">
              {{ fuel.efficiency }}% efficiency
            </div>
          </div>
        </div>

        <!-- Overall Summary -->
        <div class="mt-6 p-5 rounded-xl bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200">
          <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-bold text-gray-800">Overall Turnover Performance</div>
            <div class="text-xs px-3 py-1 rounded-full font-medium"
                 :class="overallScore >= 75 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">
              {{ overallScore }}% Target
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
              <div class="text-xs text-gray-500 mb-1">Avg Turnover</div>
              <div class="text-xl font-bold text-indigo-600">{{ avgTurnover.toFixed(1) }}x</div>
            </div>
            <div class="text-center">
              <div class="text-xs text-gray-500 mb-1">Total Stock Value</div>
              <div class="text-xl font-bold text-indigo-600">${{ formatNumber(totalStockValue) }}</div>
            </div>
            <div class="text-center">
              <div class="text-xs text-gray-500 mb-1">Avg Days on Hand</div>
              <div class="text-xl font-bold text-indigo-600">{{ avgDaysOnHand }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const loading = ref(false);

// Mock fuel turnover data
const fuelData = ref([
  {
    id: 1,
    name: 'Diesel',
    category: 'Commercial Fuel',
    color: '#3b82f6',
    turnoverRate: 3.2,
    efficiency: 85,
    avgStock: 450000,
    monthlyUsage: 1440000,
    daysOnHand: 9
  },
  {
    id: 2,
    name: 'Petrol 95',
    category: 'Premium Fuel',
    color: '#10b981',
    turnoverRate: 2.8,
    efficiency: 78,
    avgStock: 380000,
    monthlyUsage: 1064000,
    daysOnHand: 11
  },
  {
    id: 3,
    name: 'Petrol 98',
    category: 'Ultra Premium',
    color: '#f59e0b',
    turnoverRate: 2.1,
    efficiency: 65,
    avgStock: 220000,
    monthlyUsage: 462000,
    daysOnHand: 14
  },
  {
    id: 4,
    name: 'Kerosene',
    category: 'Industrial Fuel',
    color: '#8b5cf6',
    turnoverRate: 1.5,
    efficiency: 55,
    avgStock: 150000,
    monthlyUsage: 225000,
    daysOnHand: 20
  }
]);

const avgTurnover = computed(() => {
  const sum = fuelData.value.reduce((acc, fuel) => acc + fuel.turnoverRate, 0);
  return sum / fuelData.value.length;
});

const overallScore = computed(() => {
  const sum = fuelData.value.reduce((acc, fuel) => acc + fuel.efficiency, 0);
  return Math.round(sum / fuelData.value.length);
});

const totalStockValue = computed(() => {
  // Mock calculation: assume average price per liter
  const sum = fuelData.value.reduce((acc, fuel) => acc + fuel.avgStock, 0);
  return Math.round(sum * 1.2); // $1.2 per liter average
});

const avgDaysOnHand = computed(() => {
  const sum = fuelData.value.reduce((acc, fuel) => acc + fuel.daysOnHand, 0);
  return Math.round(sum / fuelData.value.length);
});

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(0) + 'K';
  }
  return num.toFixed(0);
};
</script>
