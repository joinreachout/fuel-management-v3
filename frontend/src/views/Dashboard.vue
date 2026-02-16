<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Fuel Management Dashboard</h1>
            <p class="text-sm text-gray-600 mt-1">REV 3.0 - Real-time monitoring and alerts</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Last updated</p>
            <p class="text-sm font-medium">{{ lastUpdated }}</p>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">⚠️ Error loading dashboard: {{ error }}</p>
      </div>

      <!-- Dashboard Content -->
      <div v-else>
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatCard
            title="Total Stations"
            :value="summary.inventory?.total_stations || 0"
            icon="🏢"
            iconBgColor="bg-blue-100"
          />
          <StatCard
            title="Total Depots"
            :value="summary.inventory?.total_depots || 0"
            icon="🏭"
            iconBgColor="bg-green-100"
          />
          <StatCard
            title="Total Tanks"
            :value="summary.inventory?.total_tanks || 0"
            icon="⛽"
            iconBgColor="bg-purple-100"
          />
          <StatCard
            title="Avg Fill Level"
            :value="summary.inventory?.avg_fill_percentage?.toFixed(1) + '%' || '0%'"
            :subtitle="`${formatLiters(summary.inventory?.total_stock_liters)} total`"
            icon="📊"
            iconBgColor="bg-yellow-100"
          />
        </div>

        <!-- Alerts Summary -->
        <div v-if="summary.alerts" class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-sm text-gray-600">Catastrophe</p>
            <p class="text-2xl font-bold text-red-600">{{ summary.alerts.CATASTROPHE || 0 }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-sm text-gray-600">Critical</p>
            <p class="text-2xl font-bold text-orange-600">{{ summary.alerts.CRITICAL || 0 }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-sm text-gray-600">Must Order</p>
            <p class="text-2xl font-bold text-yellow-600">{{ summary.alerts.MUST_ORDER || 0 }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-sm text-gray-600">Warning</p>
            <p class="text-2xl font-bold text-blue-600">{{ summary.alerts.WARNING || 0 }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-sm text-gray-600">Info</p>
            <p class="text-2xl font-bold text-gray-600">{{ summary.alerts.INFO || 0 }}</p>
          </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Active Alerts -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🚨 Active Alerts</h2>
            <div v-if="alerts.length === 0" class="text-center py-8 text-gray-500">
              ✅ No active alerts - all systems normal
            </div>
            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
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
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">⚠️ Critical Tanks</h2>
            <div v-if="criticalTanks.length === 0" class="text-center py-8 text-gray-500">
              ✅ No critical tanks - all stocks adequate
            </div>
            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
              <CriticalTankCard
                v-for="tank in criticalTanks"
                :key="tank.tank_id"
                :tank="tank"
              />
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { dashboardApi } from '../services/api';
import StatCard from '../components/StatCard.vue';
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

    // Load all dashboard data in parallel
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

  // Auto-refresh every 30 seconds
  const interval = setInterval(loadDashboard, 30000);

  // Cleanup on unmount
  return () => clearInterval(interval);
});
</script>
