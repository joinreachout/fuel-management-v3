<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Fixed Black Top Bar -->
    <div class="fixed top-0 left-0 right-0 bg-black z-50 px-8 py-3">
      <div class="flex items-center gap-8">
        <img src="/kkt_logo.png" alt="Kitty Kat Technologies" class="h-12 w-auto" style="filter: brightness(0) invert(1);">
        <nav class="flex items-center gap-6">
          <a href="#/dashboard" class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</a>
          <a href="#/orders" class="text-gray-400 hover:text-white transition-colors text-sm">Orders</a>
          <a href="#/transfers" class="text-gray-400 hover:text-white transition-colors text-sm">Transfers</a>
          <a href="#/parameters" class="text-white font-medium border-b-2 border-white pb-1 text-sm">Parameters</a>
          <a href="#/import" class="text-gray-400 hover:text-white transition-colors text-sm">Import</a>
          <a href="#/how-it-works" class="text-gray-400 hover:text-white transition-colors text-sm">How It Works</a>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <div class="pt-20 px-8 py-6">
      <div class="max-w-[1920px] mx-auto">

        <!-- Page Header -->
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-gray-900">System Parameters</h1>
          <p class="text-gray-600 mt-2">View and manage system configuration data</p>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-t-xl border-b border-gray-200">
          <div class="flex gap-1 px-6">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="px-6 py-4 text-sm font-medium transition-all border-b-2"
              :class="activeTab === tab.id
                ? 'text-blue-600 border-blue-600'
                : 'text-gray-600 border-transparent hover:text-gray-900'">
              <i :class="tab.icon" class="mr-2"></i>
              {{ tab.name }}
              <span v-if="tab.count !== null" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100">
                {{ tab.count }}
              </span>
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="bg-white rounded-b-xl shadow-lg p-6">

          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>

          <!-- Stations Tab -->
          <div v-else-if="activeTab === 'stations'">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Region</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="item in stations" :key="item.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.id }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.code }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.region_name }}</td>
                    <td class="px-4 py-3 text-sm">
                      <span :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            class="px-2 py-1 rounded-full text-xs font-medium">
                        {{ item.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Fuel Types Tab -->
          <div v-else-if="activeTab === 'fuel-types'">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Density (kg/L)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="item in fuelTypes" :key="item.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.id }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.density }}</td>
                    <td class="px-4 py-3 text-sm">
                      <span :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            class="px-2 py-1 rounded-full text-xs font-medium">
                        {{ item.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Sales Params Tab -->
          <div v-else-if="activeTab === 'sales-params'">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Sales Parameters</strong> define daily consumption rates (liters_per_day) used for forecast calculations.
                Missing sales params will prevent forecast display for that depot/fuel combination.
              </p>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Depot ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Liters/Day</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Effective From</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Effective To</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="(item, idx) in salesParams" :key="idx" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.depot_id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.fuel_type_id }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ item.liters_per_day }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.effective_from }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.effective_to || 'Ongoing' }}</td>
                  </tr>
                  <tr v-if="salesParams.length === 0">
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                      No sales parameters configured
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Depot Tanks Tab -->
          <div v-else-if="activeTab === 'depot-tanks'">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Tank ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Depot ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fuel Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Current Stock (L)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Capacity (L)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Fill %</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="item in depotTanks" :key="item.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ item.depot_id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.fuel_type_id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ formatNumber(item.current_stock_liters) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ formatNumber(item.capacity_liters) }}</td>
                    <td class="px-4 py-3 text-sm">
                      <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden max-w-[100px]">
                          <div class="h-full bg-blue-600 rounded-full"
                               :style="{ width: item.fill_percentage + '%' }"></div>
                        </div>
                        <span class="text-xs font-medium">{{ item.fill_percentage }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { stationsApi, fuelTypesApi, depotsApi } from '../services/api';

const loading = ref(false);
const activeTab = ref('stations');

const stations = ref([]);
const fuelTypes = ref([]);
const salesParams = ref([]);
const depotTanks = ref([]);

const tabs = computed(() => [
  { id: 'stations', name: 'Stations', icon: 'fas fa-map-marker-alt', count: stations.value.length },
  { id: 'fuel-types', name: 'Fuel Types', icon: 'fas fa-oil-can', count: fuelTypes.value.length },
  { id: 'sales-params', name: 'Sales Parameters', icon: 'fas fa-chart-line', count: salesParams.value.length },
  { id: 'depot-tanks', name: 'Depot Tanks', icon: 'fas fa-gas-pump', count: depotTanks.value.length },
]);

const formatNumber = (num) => {
  if (!num) return '0';
  return parseFloat(num).toLocaleString();
};

const loadData = async () => {
  try {
    loading.value = true;

    // Load stations
    const stationsRes = await stationsApi.getAll();
    if (stationsRes.data.success) {
      stations.value = stationsRes.data.data || [];
    }

    // Load fuel types
    const fuelTypesRes = await fuelTypesApi.getAll();
    if (fuelTypesRes.data.success) {
      fuelTypes.value = fuelTypesRes.data.data || [];
    }

    // Load depot tanks (from first depot as example)
    const depotsRes = await depotsApi.getAll();
    if (depotsRes.data.success) {
      const depots = depotsRes.data.data || [];

      // Load tanks from all depots
      for (const depot of depots.slice(0, 10)) {
        try {
          const tanksRes = await depotsApi.getTanks(depot.id);
          if (tanksRes.data.success) {
            const tanks = tanksRes.data.data || [];
            tanks.forEach(tank => {
              const fillPercentage = tank.capacity_liters > 0
                ? Math.round((tank.current_stock_liters / tank.capacity_liters) * 100)
                : 0;

              depotTanks.value.push({
                ...tank,
                fill_percentage: fillPercentage
              });
            });
          }
        } catch (err) {
          console.error(`Error loading tanks for depot ${depot.id}:`, err);
        }
      }
    }

    // Mock sales params (since we don't have API endpoint for this yet)
    salesParams.value = [
      { depot_id: 165, fuel_type_id: 25, liters_per_day: 85000, effective_from: '2026-01-01', effective_to: null },
      { depot_id: 165, fuel_type_id: 26, liters_per_day: 4469, effective_from: '2026-01-01', effective_to: null },
      { depot_id: 166, fuel_type_id: 25, liters_per_day: 75000, effective_from: '2026-01-01', effective_to: null },
    ];

  } catch (error) {
    console.error('Error loading parameters:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>
