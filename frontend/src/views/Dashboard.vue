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
          background-image: linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,0.7) 15%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0) 60%), url('/rev3/frontend/dist/truck_header.jpg');
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

          <!-- Chart Content: Filters (Left) + Canvas (Right) -->
          <div class="p-6 flex gap-6">

            <!-- LEFT: Filters Sidebar -->
            <div class="w-48 flex-shrink-0 space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">

              <!-- Level Filter -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">
                  <i class="fas fa-layer-group text-gray-400 mr-1"></i>
                  Level
                </label>
                <select
                  v-model="chartFilters.level"
                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="station">Station</option>
                  <option value="region">Region</option>
                </select>
              </div>

              <!-- Region Filter -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">
                  <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                  Region
                </label>
                <select
                  v-model="chartFilters.region"
                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Regions</option>
                  <option v-for="region in regions" :key="region.name" :value="region.name">
                    {{ region.name }}
                  </option>
                </select>
              </div>

              <!-- Station Filter -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">
                  <i class="fas fa-gas-pump text-gray-400 mr-1"></i>
                  Station
                </label>
                <select
                  v-model="chartFilters.station"
                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Stations</option>
                  <option v-for="station in stations" :key="station.id" :value="station.id">
                    {{ station.name || station.code }}
                  </option>
                </select>
              </div>

              <!-- Fuel Type Filter -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">
                  <i class="fas fa-oil-can text-gray-400 mr-1"></i>
                  Fuel Type
                </label>
                <select
                  v-model="chartFilters.fuelType"
                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Fuel Types</option>
                  <option v-for="fuel in fuelTypes" :key="fuel.id" :value="fuel.id">
                    {{ fuel.name }}
                  </option>
                </select>
              </div>

              <!-- Forecast Days Filter -->
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">
                  <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                  Forecast Days
                </label>
                <select
                  v-model="chartFilters.days"
                  class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="30">30 Days</option>
                  <option value="60">60 Days</option>
                  <option value="90">90 Days</option>
                </select>
              </div>

            </div>

            <!-- RIGHT: Chart Canvas -->
            <div class="flex-1 min-w-0">
              <div class="min-h-[400px] relative">
                <!-- No Data Message -->
                <div v-if="!forecastHasData" class="absolute inset-0 flex items-center justify-center">
                  <div class="text-center p-8 max-w-md">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
                      <i class="fas fa-chart-line text-4xl text-amber-600"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 mb-2">No Forecast Data Available</h4>
                    <p class="text-sm text-gray-600 mb-4">
                      {{ forecastMessage || 'No sales data (liters_per_day) found for selected filters.' }}
                    </p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                      <p class="text-xs font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Required Data:
                      </p>
                      <ul class="text-xs text-blue-700 space-y-1">
                        <li>• Daily sales data in <code class="bg-blue-100 px-1 rounded">sales_params</code> table</li>
                        <li>• Active depot tanks with stock levels</li>
                        <li>• Current stock in <code class="bg-blue-100 px-1 rounded">depot_tanks</code></li>
                      </ul>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">
                      Try selecting "All Stations" or "All Fuel Types" to see available data
                    </p>
                  </div>
                </div>
                <canvas id="forecastChart"></canvas>
              </div>
            </div>

          </div>
        </div>

        <!-- Grid: Station Fill Levels + Stock by Fuel Type -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
          <!-- WIDGET 2: Station Fill Levels -->
          <div class="lg:col-span-2">
            <StationFillLevels />
          </div>

          <!-- WIDGET 3: Stock by Fuel Type -->
          <div class="lg:col-span-2">
            <StockByFuelType />
          </div>

          <!-- WIDGET 4: Procurement Advisor -->
          <div class="xl:col-span-3 lg:col-span-2">
            <ProcurementAdvisor />
          </div>
        </div>

        <!-- Working Capital Optimization (Full Width) -->
        <div class="mt-6">
          <WorkingCapital />
        </div>

        <!-- Risk Exposure + Inventory Turnover Grid -->
        <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
          <!-- WIDGET 5: Risk Exposure Overview -->
          <RiskExposure />

          <!-- WIDGET 6: Inventory Turnover Rate -->
          <InventoryTurnover />
        </div>

        <!-- Top 3 Suppliers -->
        <div class="mt-6">
          <TopSuppliers />
        </div>

      </div>
    </div>

    <!-- Optimus AI Assistant (Floating) -->
    <OptimusAI />

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { dashboardApi, stationsApi, fuelTypesApi } from '../services/api';
import Chart from 'chart.js/auto';
import StationFillLevels from '../components/StationFillLevels.vue';
import StockByFuelType from '../components/StockByFuelType.vue';
import ProcurementAdvisor from '../components/ProcurementAdvisor.vue';
import WorkingCapital from '../components/WorkingCapital.vue';
import RiskExposure from '../components/RiskExposure.vue';
import InventoryTurnover from '../components/InventoryTurnover.vue';
import TopSuppliers from '../components/TopSuppliers.vue';
import OptimusAI from '../components/OptimusAI.vue';

const loading = ref(true);
const error = ref(null);
const summary = ref({});
const alerts = ref([]);
const criticalTanks = ref([]);
const lastUpdated = ref('');
const currentDateTime = ref('');

// Filter data
const regions = ref([]);
const stations = ref([]);
const fuelTypes = ref([]);

const chartFilters = ref({
  level: 'station',
  region: '',
  station: '',
  fuelType: '',
  days: '30'
});

const forecastHasData = ref(true);
const forecastMessage = ref('');

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

const loadFilterData = async () => {
  try {
    const [stationsRes, fuelTypesRes] = await Promise.all([
      stationsApi.getAll(),
      fuelTypesApi.getAll(),
    ]);

    if (stationsRes.data.success) {
      stations.value = stationsRes.data.data || [];

      // Extract unique regions from stations
      const uniqueRegions = [...new Set(stations.value.map(s => s.region_name).filter(Boolean))];
      regions.value = uniqueRegions.map(name => ({ name }));
    }

    if (fuelTypesRes.data.success) {
      fuelTypes.value = fuelTypesRes.data.data || [];
      console.log('Loaded fuel types:', fuelTypes.value); // Debug
    }
  } catch (err) {
    console.error('Filter data load error:', err);
  }
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

let forecastChartInstance = null;

const loadForecastData = async () => {
  try {
    const params = {
      level: chartFilters.value.level,
      days: parseInt(chartFilters.value.days)
    };

    if (chartFilters.value.region && chartFilters.value.region !== '') {
      params.region = chartFilters.value.region;
    }

    if (chartFilters.value.station && chartFilters.value.station !== '') {
      params.station_id = parseInt(chartFilters.value.station);
    }

    if (chartFilters.value.fuelType && chartFilters.value.fuelType !== '') {
      params.fuel_type_id = parseInt(chartFilters.value.fuelType);
    }

    console.log('Forecast params:', params); // Debug log

    const response = await dashboardApi.getForecast(params);

    if (response.data.success && response.data.data) {
      updateForecastChart(response.data.data);
    }
  } catch (err) {
    console.error('Forecast load error:', err);
  }
};

const updateForecastChart = (forecastData) => {
  const ctx = document.getElementById('forecastChart');
  if (!ctx) return;

  // Check if we have data
  const hasData = forecastData.datasets && forecastData.datasets.length > 0;
  forecastHasData.value = hasData;
  forecastMessage.value = forecastData.message || '';

  // Destroy existing chart
  if (forecastChartInstance) {
    forecastChartInstance.destroy();
    forecastChartInstance = null;
  }

  // Only create chart if we have data
  if (hasData) {
    forecastChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: forecastData.labels || [],
        datasets: forecastData.datasets || []
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
  }
};

// Watch for filter changes and reload forecast
watch(chartFilters, () => {
  loadForecastData();
}, { deep: true });

onMounted(() => {
  loadDashboard();
  loadFilterData();
  loadForecastData();

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
