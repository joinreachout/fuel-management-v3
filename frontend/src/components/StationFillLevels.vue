<template>
  <div class="bg-white rounded-2xl shadow-lg overflow-visible">
    <!-- Header with Tabs -->
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 pt-4 pb-0">
      <h3 class="text-lg font-bold text-gray-800">
        <i class="fas fa-gas-pump text-orange-500 mr-2"></i>
        Station Fill Levels
      </h3>
      <p class="text-xs text-gray-500 mt-1">Current fuel levels by station</p>

      <!-- Station Tabs -->
      <div class="station-tabs-bar" v-if="stations.length > 0">
        <button
          v-for="station in stations"
          :key="station.station_id"
          type="button"
          class="station-tab"
          :class="{ 'active': activeStationId === station.station_id }"
          :style="getTabStyle(station)"
          @click="selectStation(station.station_id)">
          {{ station.station_name }}
        </button>
      </div>
    </div>

    <!-- Vertical Bars Display -->
    <div class="px-8 pt-8 pb-10 min-w-0">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
      </div>

      <div v-else-if="currentStationTanks.length === 0" class="text-center py-12">
        <i class="fas fa-gas-pump text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">No tank data available</p>
      </div>

      <div v-else ref="scrollContainer" @scroll="handleScroll" class="overflow-x-auto overflow-y-visible scrollbar-thin w-full min-w-0">
        <div class="flex items-end justify-around gap-6 min-h-[300px] min-w-max pb-1">
          <!-- Vertical Bar for each tank -->
          <div
            v-for="tank in currentStationTanks"
            :key="tank.tank_id"
            class="flex flex-col items-center gap-2"
            style="min-width: 80px;">

            <!-- Bar Container -->
            <div class="relative flex flex-col justify-end" style="height: 250px; width: 60px;">
              <!-- Bar Background -->
              <div class="absolute bottom-0 left-0 right-0 bg-gray-100 rounded-t-lg border border-gray-200"
                   style="height: 100%;">
              </div>

              <!-- Fill Bar with Gradient -->
              <div
                class="fill-bar animate relative rounded-t-lg transition-all duration-1000"
                :style="getBarStyle(tank)"
                :title="`${tank.product_name}: ${tank.current_stock_liters.toFixed(0)}L / ${tank.tank_capacity_liters.toFixed(0)}L (${tank.fill_percentage.toFixed(1)}%)`">

                <!-- Percentage Label on Bar -->
                <div v-if="tank.fill_percentage > 15"
                     class="absolute top-2 left-0 right-0 text-center text-white text-xs font-bold drop-shadow">
                  {{ tank.fill_percentage.toFixed(0) }}%
                </div>
              </div>

            </div>

            <!-- Label Below Bar -->
            <div class="text-center">
              <div class="text-xs font-semibold text-gray-700">{{ tank.product_name }}</div>
              <div class="text-xs font-bold" :style="{ color: getBarColor(tank.fuel_type_id) }">
                {{ formatTons(tank.current_stock_tons) }}
              </div>
              <div class="text-xs text-gray-500">{{ formatTons(tank.capacity_tons) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scroll Page Indicators -->
      <div class="flex justify-center gap-2 mt-3 min-h-[22px] py-0.5" v-if="scrollPages > 1">
        <button
          v-for="page in scrollPages"
          :key="page"
          type="button"
          class="w-2 h-2 rounded-full transition-all cursor-pointer hover:opacity-70"
          :class="currentScrollPage === page - 1 ? 'bg-orange-500 w-6' : 'bg-gray-300'"
          @click="scrollToPage(page - 1)">
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { stationsApi } from '../services/api';

const loading = ref(true);
const stations = ref([]);
const allTanks = ref([]); // Store all tanks from API
const activeStationId = ref(null);
const scrollContainer = ref(null);
const currentScrollPage = ref(0);
const scrollPages = ref(1);

// Get tanks for currently selected station from real data
// Group by fuel type and sum capacities/stocks
const currentStationTanks = computed(() => {
  if (!activeStationId.value || allTanks.value.length === 0) return [];

  const stationTanks = allTanks.value.filter(tank => tank.station_id === activeStationId.value);

  // Group by fuel type ID
  const groupedByFuel = {};

  stationTanks.forEach(tank => {
    const fuelId = tank.fuel_type_id;

    if (!groupedByFuel[fuelId]) {
      groupedByFuel[fuelId] = {
        tank_id: `grouped_${fuelId}`,
        station_id: tank.station_id,
        fuel_type_id: fuelId,
        product_name: tank.product_name,
        fuel_type_code: tank.fuel_type_code,
        tank_capacity_liters: 0,
        current_stock_liters: 0,
        capacity_tons: 0,
        current_stock_tons: 0,
        tank_count: 0
      };
    }

    groupedByFuel[fuelId].tank_capacity_liters += tank.tank_capacity_liters;
    groupedByFuel[fuelId].current_stock_liters += tank.current_stock_liters;
    groupedByFuel[fuelId].capacity_tons += tank.capacity_tons;
    groupedByFuel[fuelId].current_stock_tons += tank.current_stock_tons;
    groupedByFuel[fuelId].tank_count++;
  });

  // Convert to array and calculate fill percentage
  const grouped = Object.values(groupedByFuel).map(fuel => ({
    ...fuel,
    fill_percentage: fuel.tank_capacity_liters > 0
      ? (fuel.current_stock_liters / fuel.tank_capacity_liters) * 100
      : 0
  }));

  // Sort by fuel type name
  return grouped.sort((a, b) => a.product_name.localeCompare(b.product_name));
});

const getTabStyle = (station) => {
  if (activeStationId.value === station.station_id) {
    return {}; // Active tab uses CSS class styling
  }

  // Get all tanks for this station
  const stationTanks = allTanks.value.filter(t => t.station_id === station.station_id);
  if (stationTanks.length === 0) return { background: '#e5e7eb' };

  // Find minimum fill percentage (most critical tank)
  const minFill = Math.min(...stationTanks.map(t => parseFloat(t.fill_percentage || 100)));

  // Color based on most critical tank level
  if (minFill < 30) {
    return { background: '#fecaca', color: '#991b1b' }; // Red - critical
  } else if (minFill < 50) {
    return { background: '#fed7aa', color: '#9a3412' }; // Orange - low
  } else {
    return { background: '#d1fae5', color: '#065f46' }; // Green - normal
  }
};

const getBarStyle = (tank) => {
  const height = `${tank.fill_percentage}%`;
  let gradient = '';

  // Gradient based on fill level
  if (tank.fill_percentage < 30) {
    // Red gradient for critical
    gradient = 'linear-gradient(to top, #dc2626, #ef4444)';
  } else if (tank.fill_percentage < 50) {
    // Orange gradient for low
    gradient = 'linear-gradient(to top, #ea580c, #f97316)';
  } else if (tank.fill_percentage < 80) {
    // Yellow-green gradient for medium
    gradient = 'linear-gradient(to top, #84cc16, #a3e635)';
  } else {
    // Green gradient for good
    gradient = 'linear-gradient(to top, #16a34a, #22c55e)';
  }

  return {
    height,
    background: gradient,
    boxShadow: 'inset 0 2px 4px rgba(255,255,255,0.2), inset 0 -2px 4px rgba(0,0,0,0.1)',
  };
};

const getBarColor = (fuelTypeId) => {
  // Find a tank with this fuel type to get its fill percentage
  const tank = currentStationTanks.value.find(t => t.fuel_type_id === fuelTypeId);
  if (!tank) return '#6b7280'; // Gray fallback

  const fillPct = tank.fill_percentage;

  // Return color matching the gradient (use darker color from gradient)
  if (fillPct < 30) {
    return '#dc2626'; // Red
  } else if (fillPct < 50) {
    return '#ea580c'; // Orange
  } else if (fillPct < 80) {
    return '#84cc16'; // Yellow-green
  } else {
    return '#16a34a'; // Green
  }
};

const formatTons = (tons) => {
  if (!tons) return '0 t';
  const num = parseFloat(tons);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K t';
  }
  return num.toFixed(1) + ' t';
};

const formatLiters = (liters) => {
  if (!liters) return '0 L';
  const num = parseFloat(liters);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};

const selectStation = async (stationId) => {
  activeStationId.value = stationId;

  // Load tanks for this station if not already loaded
  const hasStationTanks = allTanks.value.some(t => t.station_id === stationId);
  if (!hasStationTanks) {
    await loadStationTanks(stationId);
  }

  // Update scroll pages after station change
  setTimeout(() => {
    updateScrollPages();
    if (scrollContainer.value) {
      scrollContainer.value.scrollLeft = 0; // Reset scroll position
    }
  }, 100);
};

const loadStationTanks = async (stationId) => {
  try {
    const response = await stationsApi.getTanks(stationId);

    if (response.data.success && response.data.data) {
      const tanks = response.data.data;

      // Transform tank data to match template expectations
      const transformedTanks = tanks.map(tank => ({
        tank_id: tank.tank_id,
        station_id: stationId,
        depot_id: tank.depot_id,
        depot_name: tank.depot_name,
        tank_number: tank.tank_number,
        fuel_type_id: tank.fuel_type_id,
        product_name: tank.fuel_type_name, // Map fuel_type_name to product_name for template
        fuel_type_code: tank.fuel_type_code,
        density: parseFloat(tank.density || 0),
        tank_capacity_liters: parseFloat(tank.capacity_liters || 0),
        current_stock_liters: parseFloat(tank.current_stock_liters || 0),
        capacity_tons: parseFloat(tank.capacity_tons || 0),
        current_stock_tons: parseFloat(tank.current_stock_tons || 0),
        fill_percentage: parseFloat(tank.fill_percentage || 0),
      }));

      // Add to allTanks (remove existing tanks for this station first)
      allTanks.value = allTanks.value.filter(t => t.station_id !== stationId);
      allTanks.value.push(...transformedTanks);
    }
  } catch (error) {
    console.error(`Error loading tanks for station ${stationId}:`, error);
  }
};

const loadData = async () => {
  try {
    loading.value = true;

    // Load all stations
    const stationsRes = await stationsApi.getAll();

    if (stationsRes.data.success) {
      const stationsData = stationsRes.data.data || [];

      // Transform stations data
      stations.value = stationsData.map(s => ({
        station_id: s.id,
        station_name: s.name,
        station_code: s.code
      }));

      // Load tanks for ALL stations to enable color indicators on tabs
      if (stations.value.length > 0) {
        activeStationId.value = stations.value[0].station_id;

        // Load all station tanks in parallel
        await Promise.all(
          stations.value.map(station => loadStationTanks(station.station_id))
        );
      }
    }
  } catch (error) {
    console.error('Error loading station fill levels:', error);
  } finally {
    loading.value = false;
  }
};

const handleScroll = () => {
  if (!scrollContainer.value) return;

  const container = scrollContainer.value;
  const scrollLeft = container.scrollLeft;
  const containerWidth = container.clientWidth;
  const scrollWidth = container.scrollWidth;

  // Calculate number of pages based on scroll width
  scrollPages.value = Math.ceil(scrollWidth / containerWidth);

  // Calculate current page
  currentScrollPage.value = Math.floor(scrollLeft / containerWidth);
};

const updateScrollPages = () => {
  if (!scrollContainer.value) return;

  const container = scrollContainer.value;
  const containerWidth = container.clientWidth;
  const scrollWidth = container.scrollWidth;

  scrollPages.value = Math.ceil(scrollWidth / containerWidth);
};

const scrollToPage = (pageIndex) => {
  if (!scrollContainer.value) return;

  const container = scrollContainer.value;
  const containerWidth = container.clientWidth;
  const scrollLeft = pageIndex * containerWidth;

  container.scrollTo({
    left: scrollLeft,
    behavior: 'smooth'
  });
};

onMounted(() => {
  loadData();

  // Update scroll pages after data loads and DOM updates
  setTimeout(() => {
    updateScrollPages();
  }, 500);
});
</script>

<style scoped>
/* Station Tabs Bar */
.station-tabs-bar {
  display: flex;
  gap: 0;
  padding: 0 24px;
  margin: 0 -24px;
  margin-top: 12px;
  margin-bottom: 0;
  overflow-x: auto;
  scrollbar-width: thin;
}

.station-tab {
  border: 1px solid transparent;
  padding: 10px 20px;
  font-size: 14px;
  color: #0f172a;
  cursor: pointer;
  margin-bottom: -1px;
  border-radius: 6px 6px 0 0;
  white-space: nowrap;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.station-tab.active {
  background: #fff !important;
  color: #0f172a !important;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  border-bottom: 1px solid #fff;
}

.station-tab:hover:not(.active) {
  opacity: 0.8;
}

/* Fill Bar Animation */
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
