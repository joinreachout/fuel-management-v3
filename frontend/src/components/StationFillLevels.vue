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

      <div v-else class="overflow-x-auto overflow-y-visible scrollbar-thin w-full min-w-0">
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
                     class="absolute top-2 left-0 right-0 text-center text-white text-xs font-bold">
                  {{ tank.fill_percentage.toFixed(0) }}%
                </div>
              </div>
            </div>

            <!-- Label Below Bar -->
            <div class="text-center">
              <div class="text-xs font-semibold text-gray-700">{{ tank.product_name }}</div>
              <div class="text-xs text-gray-500">{{ formatLiters(tank.current_stock_liters) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Dots (if needed for mobile scroll) -->
      <div class="flex justify-center gap-2 mt-3 min-h-[22px] py-0.5" v-if="stations.length > 1">
        <button
          v-for="station in stations"
          :key="station.station_id"
          type="button"
          class="w-2 h-2 rounded-full transition-all"
          :class="activeStationId === station.station_id ? 'bg-orange-500 w-6' : 'bg-gray-300'"
          @click="selectStation(station.station_id)">
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { stationsApi, dashboardApi } from '../services/api';

const loading = ref(true);
const stations = ref([]);
const tanks = ref([]);
const activeStationId = ref(null);

const currentStationTanks = computed(() => {
  if (!activeStationId.value) return [];
  return tanks.value.filter(tank => tank.station_id === activeStationId.value);
});

const getTabStyle = (station) => {
  if (activeStationId.value === station.station_id) {
    return {}; // Active tab uses CSS class styling
  }

  // Calculate average fill percentage for this station
  const stationTanks = tanks.value.filter(t => t.station_id === station.station_id);
  if (stationTanks.length === 0) return { background: '#e5e7eb' };

  const avgFill = stationTanks.reduce((sum, t) => sum + t.fill_percentage, 0) / stationTanks.length;

  // Color based on fill level (same logic as REV 2.0)
  if (avgFill < 30) {
    return { background: '#fecaca', color: '#991b1b' }; // Red
  } else if (avgFill < 50) {
    return { background: '#fed7aa', color: '#9a3412' }; // Orange
  } else {
    return { background: '#d1fae5', color: '#065f46' }; // Green
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

const formatLiters = (liters) => {
  if (!liters) return '0 L';
  const num = parseFloat(liters);
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K L';
  }
  return num.toFixed(0) + ' L';
};

const selectStation = (stationId) => {
  activeStationId.value = stationId;
};

const loadData = async () => {
  try {
    loading.value = true;

    const [stationsRes, criticalTanksRes] = await Promise.all([
      stationsApi.getAll(),
      dashboardApi.getCriticalTanks(),
    ]);

    if (stationsRes.data.success) {
      const stationsData = stationsRes.data.data || [];

      // Transform stations data to match template expectations
      stations.value = stationsData.map(s => ({
        station_id: s.id,
        station_name: s.name,
        station_code: s.code
      }));

      if (stations.value.length > 0) {
        activeStationId.value = stations.value[0].station_id;

        // Generate mock tank data for each station
        stations.value.forEach(station => {
          // Create 3 tanks per station (Diesel, Petrol 95, Petrol 98)
          const fuelTypes = [
            { id: 25, name: 'Diesel', fill: Math.random() * 70 + 30 },
            { id: 26, name: 'Petrol 95', fill: Math.random() * 60 + 20 },
            { id: 27, name: 'Petrol 98', fill: Math.random() * 80 + 10 }
          ];

          fuelTypes.forEach((fuel, idx) => {
            const capacity = 50000 + Math.random() * 50000;
            const fillPercentage = fuel.fill;
            const currentStock = capacity * (fillPercentage / 100);

            tanks.value.push({
              tank_id: `${station.station_id}-${idx}`,
              station_id: station.station_id,
              product_name: fuel.name,
              fuel_type_id: fuel.id,
              tank_capacity_liters: capacity,
              current_stock_liters: currentStock,
              fill_percentage: fillPercentage
            });
          });
        });
      }
    }
  } catch (error) {
    console.error('Error loading station fill levels:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
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
