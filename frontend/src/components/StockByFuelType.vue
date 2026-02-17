<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-visible">
    <!-- Header with Tabs -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 pt-4 pb-0">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-vial text-emerald-500 mr-2"></i>
        Stock by Fuel Type
      </h3>
      <p class="text-xs text-gray-500 mt-1">Fuel inventory by type — where each fuel is stored</p>

      <div class="flex items-center gap-4 mt-2">
        <!-- Fuel Type Tabs -->
        <div class="flex flex-wrap gap-1">
          <button
            v-for="fuel in fuelTypes"
            :key="fuel.id"
            type="button"
            class="fuel-tab"
            :class="{ 'active': activeFuelId === fuel.id }"
            :style="getFuelTabStyle(fuel)"
            @click="selectFuel(fuel.id)">
            {{ fuel.name }}
          </button>
        </div>

        <!-- View Toggle -->
        <div class="flex items-center gap-2 ml-auto">
          <span class="text-xs text-gray-500">View:</span>
          <button
            type="button"
            class="px-2 py-1 text-xs rounded font-medium transition-colors"
            :class="viewMode === 'stations' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
            @click="viewMode = 'stations'">
            Stations
          </button>
          <button
            type="button"
            class="px-2 py-1 text-xs rounded font-medium transition-colors"
            :class="viewMode === 'regions' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
            @click="viewMode = 'regions'">
            Regions
          </button>
        </div>
      </div>
    </div>

    <!-- Bars Display -->
    <div class="px-8 pt-6 pb-10 min-w-0">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
      </div>

      <div v-else class="overflow-x-auto overflow-y-visible scrollbar-thin w-full min-w-0">
        <div class="flex items-end justify-around gap-6 min-h-[280px] min-w-max pb-1">
          <!-- Vertical Bar for each location -->
          <div
            v-for="location in currentLocations"
            :key="location.id"
            class="flex flex-col items-center gap-2"
            style="min-width: 80px;">

            <!-- Bar Container -->
            <div class="relative flex flex-col justify-end" style="height: 230px; width: 60px;">
              <!-- Background -->
              <div class="absolute bottom-0 left-0 right-0 bg-gray-100 rounded-t-lg border border-gray-200"
                   style="height: 100%;">
              </div>

              <!-- Fill Bar -->
              <div
                class="fill-bar animate relative rounded-t-lg transition-all duration-1000"
                :style="getBarStyle(location)"
                :title="`${location.name}: ${location.stock.toFixed(0)}L (${location.fillPercentage.toFixed(1)}%)`">

                <!-- Percentage Label -->
                <div v-if="location.fillPercentage > 15"
                     class="absolute top-2 left-0 right-0 text-center text-white text-xs font-bold">
                  {{ location.fillPercentage.toFixed(0) }}%
                </div>
              </div>
            </div>

            <!-- Label Below Bar -->
            <div class="text-center">
              <div class="text-xs font-semibold text-gray-700">{{ location.name }}</div>
              <div class="text-xs font-bold" :style="{ color: getBarColor(location.fillPercentage) }">
                {{ formatLiters(location.stock) }}
              </div>
              <div class="text-xs text-gray-500">{{ formatLiters(location.capacity) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { fuelTypesApi } from '../services/api';

const loading = ref(true);
const fuelTypes = ref([]);
const stationStockData = ref([]); // Real stock data from API
const allFuelStockData = ref({}); // Store stock data for ALL fuel types
const activeFuelId = ref(null);
const viewMode = ref('stations'); // 'stations' or 'regions'

const currentLocations = computed(() => {
  if (!activeFuelId.value || stationStockData.value.length === 0) return [];

  // Use REAL data from API
  return stationStockData.value.map(station => {
    const stockLiters = parseFloat(station.total_stock_liters || 0);
    const capacityLiters = parseFloat(station.total_capacity_liters || 0);
    const fillPercentage = parseFloat(station.avg_fill_percentage || 0);

    return {
      id: station.station_id,
      name: station.station_name || station.station_code,
      stock: stockLiters,
      capacity: capacityLiters,
      fillPercentage: fillPercentage
    };
  });
});

const getFuelTabStyle = (fuel) => {
  if (activeFuelId.value === fuel.id) {
    return {}; // Active uses CSS class
  }

  // Get stock data for this fuel type
  const fuelStockData = allFuelStockData.value[fuel.id];
  if (!fuelStockData || fuelStockData.length === 0) {
    return { background: '#e5e7eb', color: '#6b7280' }; // Gray if no data
  }

  // Find minimum fill percentage (most critical location)
  const minFill = Math.min(...fuelStockData.map(s => parseFloat(s.avg_fill_percentage || 100)));

  // Color based on most critical location level
  if (minFill < 30) {
    return { background: '#fecaca', color: '#991b1b' }; // Red - critical
  } else if (minFill < 50) {
    return { background: '#fed7aa', color: '#9a3412' }; // Orange - low
  } else {
    return { background: '#d1fae5', color: '#065f46' }; // Green - normal
  }
};

const getBarColor = (fillPercentage) => {
  // Return color matching the gradient (use darker color from gradient)
  if (fillPercentage < 30) {
    return '#dc2626'; // Red
  } else if (fillPercentage < 50) {
    return '#ea580c'; // Orange
  } else if (fillPercentage < 80) {
    return '#84cc16'; // Yellow-green
  } else {
    return '#16a34a'; // Green
  }
};

const getBarStyle = (location) => {
  const height = `${location.fillPercentage}%`;

  // Gradient based on fill level
  let gradient = '';
  if (location.fillPercentage < 30) {
    gradient = 'linear-gradient(to top, #dc2626, #ef4444)';
  } else if (location.fillPercentage < 50) {
    gradient = 'linear-gradient(to top, #ea580c, #f97316)';
  } else if (location.fillPercentage < 80) {
    gradient = 'linear-gradient(to top, #84cc16, #a3e635)';
  } else {
    gradient = 'linear-gradient(to top, #16a34a, #22c55e)';
  }

  return {
    height,
    background: gradient,
    boxShadow: 'inset 0 2px 4px rgba(255,255,255,0.2), inset 0 -2px 4px rgba(0,0,0,0.1)',
  };
};

const formatLiters = (liters) => {
  if (!liters) return '0 L';
  const num = parseFloat(liters);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};

const selectFuel = async (fuelId) => {
  activeFuelId.value = fuelId;

  // Use already loaded data if available
  if (allFuelStockData.value[fuelId]) {
    stationStockData.value = allFuelStockData.value[fuelId];
  } else {
    // Load if not yet loaded
    await loadStockForFuel(fuelId);
  }
};

const loadStockForFuel = async (fuelId) => {
  try {
    loading.value = true;

    const response = await fuelTypesApi.getStationsByFuelType(fuelId);

    if (response.data.success) {
      const data = response.data.data || [];
      stationStockData.value = data;

      // Store in allFuelStockData for tab color calculation
      allFuelStockData.value[fuelId] = data;
    }
  } catch (error) {
    console.error(`Error loading stock for fuel type ${fuelId}:`, error);
    stationStockData.value = [];
  } finally {
    loading.value = false;
  }
};

const loadData = async () => {
  try {
    loading.value = true;

    // Load all fuel types
    const fuelTypesRes = await fuelTypesApi.getAll();

    if (fuelTypesRes.data.success) {
      fuelTypes.value = fuelTypesRes.data.data || [];

      // Load stock data for ALL fuel types to enable color indicators on tabs
      if (fuelTypes.value.length > 0) {
        activeFuelId.value = fuelTypes.value[0].id;

        // Load all fuel stock data in parallel
        await Promise.all(
          fuelTypes.value.map(fuel => loadStockForFuel(fuel.id))
        );

        // Reload current fuel to update display
        stationStockData.value = allFuelStockData.value[activeFuelId.value] || [];
      }
    }
  } catch (error) {
    console.error('Error loading fuel stock data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
/* Fuel Tabs */
.fuel-tab {
  padding: 6px 14px;
  font-size: 13px;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.fuel-tab.active {
  background: #fff !important;
  color: #0f172a !important;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.fuel-tab:hover:not(.active) {
  opacity: 0.8;
  transform: translateY(-1px);
}

/* Fill Bar */
.fill-bar {
  opacity: 0;
  min-height: 2px;
}

.fill-bar.animate {
  opacity: 1;
}

.fill-bar:hover {
  filter: brightness(1.1);
  cursor: pointer;
}
</style>
