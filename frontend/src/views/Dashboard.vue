<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Beautiful Header with Gradient -->
    <header class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-2xl">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center">
          <h1 class="text-5xl font-extrabold text-white mb-3 tracking-tight">
            ⛽ Fuel Management System
          </h1>
          <p class="text-xl text-indigo-100 mb-8">REV 3.0 - Real-time Monitoring Dashboard</p>

          <!-- KPI Cards in Header -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <div class="bg-white/20 backdrop-blur-lg rounded-xl p-4 hover:bg-white/30 transition-all duration-300 hover:scale-105 cursor-pointer">
              <p class="text-indigo-100 text-sm font-medium">Stations</p>
              <p class="text-4xl font-bold text-white mt-1">{{ summary.inventory?.total_stations || 0 }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-lg rounded-xl p-4 hover:bg-white/30 transition-all duration-300 hover:scale-105 cursor-pointer">
              <p class="text-indigo-100 text-sm font-medium">Depots</p>
              <p class="text-4xl font-bold text-white mt-1">{{ summary.inventory?.total_depots || 0 }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-lg rounded-xl p-4 hover:bg-white/30 transition-all duration-300 hover:scale-105 cursor-pointer">
              <p class="text-indigo-100 text-sm font-medium">Tanks</p>
              <p class="text-4xl font-bold text-white mt-1">{{ summary.inventory?.total_tanks || 0 }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-lg rounded-xl p-4 hover:bg-white/30 transition-all duration-300 hover:scale-105 cursor-pointer">
              <p class="text-indigo-100 text-sm font-medium">Fill Level</p>
              <p class="text-4xl font-bold text-white mt-1">{{ summary.inventory?.avg_fill_percentage?.toFixed(1) || 0 }}%</p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 pb-12">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="relative">
          <div class="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-purple-600"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-3xl">⛽</span>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 shadow-lg">
        <div class="flex items-center">
          <span class="text-4xl mr-4">⚠️</span>
          <div>
            <h3 class="text-lg font-semibold text-red-800">Error Loading Dashboard</h3>
            <p class="text-red-600 mt-1">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Dashboard Content -->
      <div v-else class="space-y-6">
        <!-- Alert Summary Cards -->
        <div class="grid grid-cols-5 gap-4">
          <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-90">Catastrophe</p>
                <p class="text-4xl font-bold mt-2">{{ summary.alerts?.CATASTROPHE || 0 }}</p>
              </div>
              <span class="text-5xl opacity-75">🚨</span>
            </div>
          </div>

          <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-90">Critical</p>
                <p class="text-4xl font-bold mt-2">{{ summary.alerts?.CRITICAL || 0 }}</p>
              </div>
              <span class="text-5xl opacity-75">⚠️</span>
            </div>
          </div>

          <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-90">Must Order</p>
                <p class="text-4xl font-bold mt-2">{{ summary.alerts?.MUST_ORDER || 0 }}</p>
              </div>
              <span class="text-5xl opacity-75">📦</span>
            </div>
          </div>

          <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-90">Warning</p>
                <p class="text-4xl font-bold mt-2">{{ summary.alerts?.WARNING || 0 }}</p>
              </div>
              <span class="text-5xl opacity-75">ℹ️</span>
            </div>
          </div>

          <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-lg p-6 text-white hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm opacity-90">Info</p>
                <p class="text-4xl font-bold mt-2">{{ summary.alerts?.INFO || 0 }}</p>
              </div>
              <span class="text-5xl opacity-75">💡</span>
            </div>
          </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Active Alerts -->
          <div class="bg-white rounded-2xl shadow-xl p-8 hover:shadow-2xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <span class="text-3xl mr-3">🚨</span>
                Active Alerts
              </h2>
              <span class="px-4 py-2 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
                {{ alerts.length }} alerts
              </span>
            </div>

            <div v-if="alerts.length === 0" class="text-center py-12">
              <span class="text-6xl mb-4 block">✅</span>
              <p class="text-xl text-gray-500 font-medium">No active alerts</p>
              <p class="text-gray-400 mt-2">All systems normal</p>
            </div>

            <div v-else class="space-y-4 max-h-96 overflow-y-auto pr-2">
              <AlertCard
                v-for="(alert, index) in alerts"
                :key="index"
                :severity="alert.severity"
                :message="alert.message"
                :details="alert.details"
              />
            </div>
          </div>

          <!-- Critical Tanks -->
          <div class="bg-white rounded-2xl shadow-xl p-8 hover:shadow-2xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <span class="text-3xl mr-3">⚠️</span>
                Critical Tanks
              </h2>
              <span class="px-4 py-2 bg-orange-100 text-orange-700 rounded-full text-sm font-semibold">
                {{ criticalTanks.length }} tanks
              </span>
            </div>

            <div v-if="criticalTanks.length === 0" class="text-center py-12">
              <span class="text-6xl mb-4 block">✅</span>
              <p class="text-xl text-gray-500 font-medium">No critical tanks</p>
              <p class="text-gray-400 mt-2">All stocks adequate</p>
            </div>

            <div v-else class="space-y-4 max-h-96 overflow-y-auto pr-2">
              <CriticalTankCard
                v-for="tank in criticalTanks"
                :key="tank.tank_id"
                :tank="tank"
              />
            </div>
          </div>
        </div>

        <!-- Footer Info -->
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 text-white">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <span class="text-2xl">🔄</span>
              <div>
                <p class="font-semibold">Auto-refresh: Every 30 seconds</p>
                <p class="text-sm text-gray-300">Last updated: {{ lastUpdated }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-300">Total Stock</p>
              <p class="text-2xl font-bold">{{ formatLiters(summary.inventory?.total_stock_liters) }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { dashboardApi } from '../services/api';
import AlertCard from '../components/AlertCard.vue';
import CriticalTankCard from '../components/CriticalTankCard.vue';

const loading = ref(true);
const error = ref(null);
const summary = ref({});
const alerts = ref([]);
const criticalTanks = ref([]);
const lastUpdated = ref('');

const formatLiters = (liters) => {
  if (!liters) return '0 L';
  const num = parseFloat(liters);
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M L';
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};

const loadDashboard = async () => {
  try {
    loading.value = true;
    error.value = null;

    const [summaryRes, alertsRes, criticalRes] = await Promise.all([
      dashboardApi.getSummary(),
      dashboardApi.getAlerts(),
      dashboardApi.getCriticalTanks(),
    ]);

    if (summaryRes.data.success) {
      summary.value = summaryRes.data.data;
    }

    if (alertsRes.data.success) {
      alerts.value = alertsRes.data.data || [];
    }

    if (criticalRes.data.success) {
      criticalTanks.value = criticalRes.data.data || [];
    }

    lastUpdated.value = new Date().toLocaleString();
  } catch (err) {
    console.error('Dashboard load error:', err);
    error.value = err.message || 'Failed to load dashboard data';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadDashboard();
  const interval = setInterval(loadDashboard, 30000);
  return () => clearInterval(interval);
});
</script>
