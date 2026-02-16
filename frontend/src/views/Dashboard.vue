<template>
  <div class="min-h-screen">
    <!-- Fixed Black Top Bar with Logo + Menu -->
    <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
      <div class="flex items-center gap-8">
        <!-- Logo WHITE -->
        <img
          src="/kkt_logo.png"
          alt="Kitty Kat Technologies"
          class="h-12 w-auto"
          style="filter: brightness(0) invert(1);">

        <!-- Menu -->
        <nav class="flex items-center gap-6">
          <a href="#" class="text-white font-medium border-b-2 border-white pb-1 text-sm">Dashboard</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">Orders</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">Transfers</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">Parameters</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">Import</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</a>
        </nav>
      </div>
    </div>

    <!-- Spacer for fixed navbar -->
    <div class="h-20 bg-black"></div>

    <!-- SCROLLABLE: Header with KPI and Background -->
    <header class="bg-black relative" style="min-height: 560px;">

      <!-- Truck Background Image - Right Side with Gradient Fade -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute right-0 top-0 bottom-0 w-2/3" style="
          background-image: linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,0.7) 15%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0) 60%), url('/truck_header.jpg');
          background-size: auto 100%;
          background-position: center right;
          background-repeat: no-repeat;
          opacity: 0.85;
        "></div>
      </div>

      <!-- Background Decoration SVG -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-10">
        <svg class="absolute right-0 top-0 h-full w-full opacity-10" viewBox="0 0 1200 300" preserveAspectRatio="xMaxYMid slice">
          <defs>
            <linearGradient id="fuelGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#0ea5e9;stop-opacity:0.8" />
              <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.8" />
            </linearGradient>
          </defs>
          <g fill="url(#fuelGradient)">
            <ellipse cx="950" cy="150" rx="80" ry="20" opacity="0.5"/>
            <rect x="870" y="150" width="160" height="120" opacity="0.5"/>
            <ellipse cx="950" cy="270" rx="80" ry="20" opacity="0.5"/>
            <ellipse cx="1100" cy="170" rx="60" ry="15" opacity="0.4"/>
            <rect x="1040" y="170" width="120" height="100" opacity="0.4"/>
            <ellipse cx="1100" cy="270" rx="60" ry="15" opacity="0.4"/>
            <rect x="900" y="220" width="250" height="8" rx="4" opacity="0.3"/>
            <rect x="920" y="240" width="200" height="6" rx="3" opacity="0.3"/>
            <ellipse cx="820" cy="200" rx="40" ry="10" opacity="0.3"/>
            <rect x="780" y="200" width="80" height="70" opacity="0.3"/>
            <ellipse cx="820" cy="270" rx="40" ry="10" opacity="0.3"/>
          </g>
        </svg>
      </div>

      <!-- Main Header Content -->
      <div class="relative px-8 py-3 pb-96">

        <!-- ROW 1: Title + Subtitle + KPI Row 1 -->
        <div class="flex items-start justify-between mb-2 mt-6">
          <div>
            <h1 class="text-2xl font-bold text-white mb-1">Dashboard</h1>
            <p class="text-sm text-gray-400">Fuel supply analytics and monitoring system</p>
          </div>

          <!-- KPI Row 1 (4 metrics) -->
          <div class="flex items-center gap-10 pt-1">
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" id="kpi-total-stations">{{ summary.inventory?.total_stations || 0 }}</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Total</div>
                <div class="text-white text-xs font-semibold">Stations</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" :class="{ 'critical': criticalCount > 0 }" id="kpi-shortages">{{ criticalCount }}</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Shortages</div>
                <div class="text-white text-xs font-semibold">Predicted</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" :class="{ 'critical': criticalCount > 0 }" id="kpi-critical-stations">{{ criticalCount }}</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Below</div>
                <div class="text-white text-xs font-semibold">Threshold</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" id="kpi-low-stations">0</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Low Stock</div>
                <div class="text-white text-xs font-semibold">Stations</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ROW 3: KPI Row 2 (aligned right) -->
        <div class="flex justify-end mb-4">
          <div class="flex items-center gap-10">
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" id="kpi-mandatory-orders">2</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Mandatory</div>
                <div class="text-white text-xs font-semibold">Orders</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" id="kpi-recommended-orders">0</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Recommended</div>
                <div class="text-white text-xs font-semibold">Orders</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-2xl font-bold text-white" id="kpi-active-transfers">0</div>
              <div class="h-8 w-0.5 bg-white/40"></div>
              <div class="flex flex-col leading-tight">
                <div class="text-white text-xs font-semibold">Active</div>
                <div class="text-white text-xs font-semibold">Transfers</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ROW 4: Three Separate Boxes - All Left Aligned -->
        <div class="flex items-center gap-4 pb-3">

          <!-- Box 1: Current Date/Time -->
          <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
            <i class="far fa-clock text-gray-400"></i>
            <span id="current-datetime" class="font-medium text-gray-300">{{ currentDateTime }}</span>
          </div>

          <!-- Box 2: Last Update -->
          <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
            <i class="far fa-calendar-alt text-gray-400"></i>
            <span class="text-gray-400">Last Update:</span>
            <span id="last-update-date" class="font-medium text-gray-300">{{ lastUpdated }}</span>
          </div>

          <!-- Box 3: Risk exposure note -->
          <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-lg border border-white/10 text-xs">
            <i class="fas fa-exclamation-circle text-amber-500" id="header-icon"></i>
            <span id="header-note" class="text-amber-500 font-medium">{{ criticalCount }} stations below threshold — decision required</span>
          </div>

        </div>

      </div>
    </header>

    <!-- Main Content - OVERLAPPING HEADER -->
    <div class="min-h-screen bg-gray-50 relative -mt-56">

      <div class="max-w-[1920px] mx-auto px-6 space-y-6 relative z-10 pt-56">

        <!-- WIDGET 1: Stock Forecast Chart (Full Width) - OVERLAPS HEADER -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden -mt-96 relative z-20">

          <!-- Chart Header -->
          <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-bold text-gray-800">
              <i class="fas fa-chart-line text-blue-500 mr-2"></i>
              Fuel Level Forecast - Station Level (Tons)
            </h3>
            <p class="text-xs text-gray-500 mt-1">Predictive analysis of fuel levels across stations</p>
          </div>

          <!-- Chart Content -->
          <div class="p-6">
            <canvas id="forecastChart" height="100"></canvas>
          </div>
        </div>

        <!-- Grid: Station Fill Levels + Other Widgets -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
          <!-- WIDGET 2: Station Fill Levels with TABS + VERTICAL BARS -->
          <div class="lg:col-span-2">
            <StationFillLevels />
          </div>

          <!-- WIDGET 3: Procurement Advisor (placeholder for now) -->
          <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-4">
              <h3 class="text-lg font-bold text-gray-800">
                <i class="fas fa-lightbulb text-blue-500 mr-2"></i>
                Procurement Advisor
              </h3>
              <p class="text-xs text-gray-500 mt-1">AI-powered ordering recommendations</p>
            </div>
            <div class="p-6">
              <p class="text-gray-500 text-center py-8">Coming soon...</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Optimus AI Assistant (Floating) -->
    <OptimusAI />

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { dashboardApi } from '../services/api';
import Chart from 'chart.js/auto';
import StationFillLevels from '../components/StationFillLevels.vue';
import OptimusAI from '../components/OptimusAI.vue';

const loading = ref(true);
const error = ref(null);
const summary = ref({});
const alerts = ref([]);
const criticalTanks = ref([]);
const lastUpdated = ref('');
const currentDateTime = ref('');

const criticalCount = computed(() => {
  return criticalTanks.value.length;
});

const formatDateTime = () => {
  const now = new Date();
  const day = String(now.getDate()).padStart(2, '0');
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const month = monthNames[now.getMonth()];
  const year = now.getFullYear();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');

  return `${day} ${month} ${year}, ${hours}:${minutes}`;
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

    lastUpdated.value = formatDateTime();
    currentDateTime.value = formatDateTime();
  } catch (err) {
    console.error('Dashboard load error:', err);
    error.value = err.message || 'Failed to load dashboard data';
  } finally {
    loading.value = false;
  }
};

const createForecastChart = () => {
  const ctx = document.getElementById('forecastChart');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
      datasets: [{
        label: 'Diesel',
        data: [120, 115, 110, 105, 100, 95, 90],
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
      }, {
        label: 'Petrol 95',
        data: [100, 95, 90, 85, 80, 75, 70],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
      }, {
        label: 'Petrol 98',
        data: [80, 77, 74, 71, 68, 65, 62],
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        tension: 0.4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'top',
        }
      },
      scales: {
        y: {
          beginAtZero: false,
          title: {
            display: true,
            text: 'Stock (Tons)'
          }
        }
      }
    }
  });
};

onMounted(() => {
  loadDashboard();
  createForecastChart();

  // Update time every minute
  setInterval(() => {
    currentDateTime.value = formatDateTime();
  }, 60000);

  // Refresh data every 30 seconds
  const interval = setInterval(loadDashboard, 30000);
  return () => clearInterval(interval);
});
</script>

<style scoped>
/* Critical class for red numbers */
.critical {
  color: #ef4444 !important;
}

.critical:hover {
  text-shadow: 0 0 12px rgba(239, 68, 68, 0.6);
}

/* KPI Numbers - White by default with hover effect */
#kpi-total-stations:hover,
#kpi-shortages:hover,
#kpi-critical-stations:hover,
#kpi-low-stations:hover,
#kpi-mandatory-orders:hover,
#kpi-recommended-orders:hover,
#kpi-active-transfers:hover {
  transform: scale(1.1);
  transition: all 0.2s ease;
}
</style>
